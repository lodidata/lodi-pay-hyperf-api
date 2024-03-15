<?php

declare(strict_types=1);

namespace App\Controller\BackendApi;

use App\Amqp\Producer\BalanceChangeProducer;
use App\Amqp\Producer\CallbackProducer;
use App\Amqp\Producer\DelayDirectProducer;
use App\Cache\Repository\LockRedis;
use App\Controller\AbstractController;
use App\Exception\ServiceException;
use App\Service\CollectionOrderService;
use Hyperf\Amqp\Producer;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class OrderController extends AbstractController
{
    /**
     * @Inject()
     * @var CollectionOrderService
     */
    protected $collectionOrderService;

    public function changeStatus()
    {
        $params = $this->request->getParsedBody();

        $rules = [
            'inner_order_sn' => 'required|string|max:60',
            'status' => 'required|in:6,9,10'
        ];
        $this->checkValidation($params, $rules);

        $status = $params['status'];
        try {
            $collection_order = (array)Db::table('orders_collection')->where('inner_order_sn',$params['inner_order_sn'])->first(['merchant_account','status','order_status','amount','inner_order_sn','pay_inner_order_sn','currency']);
            if (empty($collection_order)) throw new ServiceException('The order does not exist');
            switch ($status) {
                case 6://订单完成
                    $this->collectionOrderService->confirmPayment($collection_order['merchant_account'],$collection_order['currency'],$collection_order['inner_order_sn'],$collection_order['amount'],$collection_order['pay_inner_order_sn']);
                    break;
                case 9://订单失败
                    $this->collectionOrderService->fail($collection_order);
                    break;
                case 10://订单取消
                    $this->collectionOrderService->cancel($collection_order);
                    break;
                default:
            }
        } catch (\Exception $e) {
            return $this->response->fail($e->getMessage());
        }
        return $this->response->success();
    }


    public function reject()
    {
        $params = $this->request->getParsedBody();

        $rules = [
            'inner_order_sn' => 'required|string|max:60',
        ];
        $this->checkValidation($params, $rules);

        $lock_key = 'backend_api:reject:'.$params['inner_order_sn'];
        $uuid = uniqid('backendapi_reject',true);
        if (LockRedis::getInstance()->lock($lock_key,20,$uuid)) {
            try {
                $inner_order_sn = (string)$params['inner_order_sn'];
                $pay_order = (array)Db::table('orders_pay')->where('inner_order_sn',(string)$inner_order_sn)->first(['amount','match_timeout_amount','currency','status','merchant_account']);
                $amount = $pay_order['amount'];
                $match_timeout_amount = $pay_order['match_timeout_amount'];

                if (empty($pay_order)) {
                    throw new ServiceException('The order does not exist');
                }

                if (in_array($pay_order['status'],[6,11])) {
                    throw new ServiceException('The order cannot be rejected');
                }

                if (bccomp((string)$amount,(string)$pay_order['match_timeout_amount'],2) != 0) {
                    //子单所有失败总金额
                    $third_pay_order = Db::table('orders_collection')->where(['pay_inner_order_sn'=>(string)$inner_order_sn,'order_type'=>3])->first(['status','amount']);
                    if ($third_pay_order) {
                        if ($third_pay_order->status != 9) {
                            throw new ServiceException('The order cannot be rejected 1');
                        }
                        if (bccomp((string)$amount,(string)$third_pay_order->amount,2) != 0) {
                            throw new ServiceException('The order cannot be rejected 2');
                        }
                    } else {
                        $fail_amount = Db::table('orders_collection')->where(['pay_inner_order_sn' => $inner_order_sn,'status'=>9])->sum('amount');
                        $fail_total_amount = bcadd((string)$match_timeout_amount,(string)$fail_amount,2);
                        if (bccomp((string)$amount,(string)$fail_total_amount,2) != 0) {
                            throw new ServiceException('The order cannot be rejected 3');
                        }
                    }

                }
                $merchant_account = (string)$pay_order['merchant_account'];
                $currency = $pay_order['currency'];
                try {
                    DB::beginTransaction();
                    Db::table('orders_pay')
                        ->where('inner_order_sn', $params['inner_order_sn'])
                        ->whereNotIn('status',[6,11])
                        ->update([
                            'pay_status' => 'fail',
                            'status' => 11,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);

                    Db::table('merchant_pay_balance')->where(['merchant_account' => $merchant_account, 'currency' => $currency])->increment('balance', $amount);

                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollBack();
                    throw new ServiceException($e->getMessage());
                }

                //确认收款
                $message = new CallbackProducer(['event' => 'pay_reject','inner_order_sn' => $inner_order_sn]);
                $producer = di()->get(Producer::class);
                $producer->produce($message);

                //余额变更日志
                $balance_log = new BalanceChangeProducer([
                    'inner_order_sn' => $inner_order_sn,
                    'amount' => $pay_order['amount'],
                    'currency' => $pay_order['currency'],
                    'merchant_account' => $pay_order['merchant_account'],
                    'transaction_type' => 6,
                    'order_type' => 2
                ]);
                $producer->produce($balance_log);

            } catch (\Exception $e) {
                return $this->response->fail($e->getMessage());
            } finally {
                LockRedis::getInstance()->delete($lock_key,$uuid);
            }
            return $this->response->success();
        }
        return $this->response->fail('Do not operate frequently');
    }

    public function uploadCert()
    {
        $params = $this->request->getParsedBody();

        $rules = [
            'inner_order_sn' => 'required|string|max:60',
        ];
        $this->checkValidation($params, $rules);
        $inner_order_sn = (string)$params['inner_order_sn'];
        try{
            $collection_order = Db::table('orders_collection')->where(['inner_order_sn' => $inner_order_sn])->first(['status','order_type']);
            if (!$collection_order) throw new ServiceException('The order does not exist');

            if ($collection_order->order_type != 1 || $collection_order->status != 4) {
                throw new ServiceException('The order does not allow uploading certificates');
            }

            $delay_message = new DelayDirectProducer(['orders_collection_sn' => $inner_order_sn, 'event' => 'upload_cert']);
            $delay_message->setDelayMs(720000);
            $producer = di()->get(Producer::class);
            $producer->produce($delay_message);

            //上传凭证
            $message = new CallbackProducer(['event' => 'upload_cert','inner_order_sn' => $inner_order_sn]);
            $producer->produce($message);
        } catch (\Exception $e) {
            return $this->response->fail($e->getMessage());
        }

        return $this->response->success();

    }
}