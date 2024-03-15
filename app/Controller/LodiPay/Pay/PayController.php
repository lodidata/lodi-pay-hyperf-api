<?php

declare(strict_types=1);

namespace App\Controller\LodiPay\Pay;

use App\Amqp\Producer\BalanceChangeProducer;
use App\Amqp\Producer\PayMatchProducer;
use App\Cache\Repository\LockRedis;
use App\Constant\ResponseCode;
use App\Controller\AbstractController;
use App\Model\Admin\AdminConfig;
use App\Service\AdminConfigService;
use App\Service\CollectionOrderService;
use App\Service\MerchantService;
use Hyperf\Amqp\Producer;
use Hyperf\Di\Annotation\Inject;
use Hyperf\DbConnection\Db;
use Psr\Http\Message\ResponseInterface;
use App\Exception\ServiceException;
use App\Model\Merchant\Merchant;
use App\Support\PayProducer;

class PayController extends AbstractController
{
    /**
     * @Inject()
     * @var MerchantService
     */
    protected $merchantService;

    /**
     * @Inject()
     * @var CollectionOrderService
     */
    protected $collectionOrderService;

    /**
     * @Inject()
     * @var AdminConfigService
     */
    protected $adminConfigService;

    /**
     * 发起代付
     */
    public function apply(): ResponseInterface
    {
        $params = $this->request->getParsedBody();

        $rules = [
            'mer_account' => 'required',
            'order_no' => 'required|string|max:60',
            'order_amount' => 'required|regex:/^[0-9]+(.[0-9]{1,2})?$/',
            'account_no' => 'required|string|regex:/^[0-9]{9,11}$/',
            'currency' => 'required|alpha_num|exists:currency,currency_type',
            'callback_url' => 'required|string|max:100',
            'sign' => 'required',
        ];
        $this->checkValidation($params, $rules, trans('lodipay.pay_apply'));

        $lock_key = 'pay:'.$params['mer_account'] . '-' . $params['order_no'];
        $uuid = uniqid('pay',true);
        if (LockRedis::getInstance()->lock($lock_key,10,$uuid)) {
            try {
                $merchant = $this->merchantService->getMerchantByAccount($params['mer_account']);
                if ($merchant['is_pay_behalf'] == 0) {
                    throw new ServiceException('Payment on behalf has been closed');
                }
                $merchant_id = (int)$merchant['id'];

                //查询订单是否存在
                $order = Db::table('orders_pay')->where(['merchant_id' => $merchant_id, 'order_sn' => $params['order_no']])->count();
                if ($order) {
                    throw new ServiceException(trans('lodipay.pay_apply_exist'));
                }

                //写入数据库
                $date = date('Y-m-d H:i:s');
                $inner_order_sn = (string)snowflakeId();
                $data = [
                    'order_sn' => $params['order_no'],
                    'inner_order_sn' => $inner_order_sn,
                    'merchant_id' => $merchant_id,
                    'merchant_account' => $params['mer_account'],
                    'user_account' => $params['account_no'],
                    'payment' => 'GCASH',
                    'currency' => $params['currency'],
                    'amount' => $params['order_amount'],
                    'callback_url' => $params['callback_url'],
                    'status' => 1,
                    'created_at' => $date,
                    'updated_at' => $date,
                ];
                //系统限制，站点可提总金额
                $config_orders = $this->adminConfigService->getConfig(['max_amount_site']);
                $total_amount = $config_orders['max_amount_site'];
                $cache_total_amount_key = 'lodipay:merchant:'.$merchant_id;
                if ($total_amount != 999999999) {
                    $cache_total_amount = redis()->get($cache_total_amount_key)??0;
                    if (($cache_total_amount + $data['amount']) > $total_amount) {
                        throw new ServiceException('The total payment amount exceeds the limit');
                    }
                }
                //判断是否内充 9000以内并且100的倍数
                $temp_amount = (int)bcmul((string)$data['amount'], '100', 0);
                $max_amount = (int)bcmul('9000', '100') ;
                if ($temp_amount > 0 && $temp_amount <= $max_amount && $temp_amount % 10000 == 0) {
                    //查询user表
                    $user = Db::table('user')->where('user_account', $params['account_no'])->first(['id','status']);
                    if (!$user) {
                        $time = date('Y-m-d H:i:s');
                        $user_id = Db::table('user')->insertGetId(['merchant_id' => $merchant_id, 'user_account' => $params['account_no'], 'username' => $params['account_no'], 'created_at' => $time, 'updated_at' => $time]);
                    } else {
                        if ($user->status == 0) throw new ServiceException('Your account has been disabled and cannot be used');
                        $user_id = $user->id;
                    }
                    $data['user_id'] = $user_id;

                    try {
                        //扣钱
                        $update_balance = Db::table('merchant_pay_balance')->where(['merchant_account'=>$params['mer_account'],'currency'=>$params['currency']])->decrement('balance',$params['order_amount']);
                    } catch (\Exception $e) {
                        throw new ServiceException('Insufficient payment balance');
                    }
                    if ($update_balance == 0) throw new ServiceException('No payment balance configured');


                    $order_pay_id = Db::table('orders_pay')->insertGetId($data);
                    if ($order_pay_id) {
                        //存入MQ等待撮合
                        unset($params['sign']);
                        try {
                            $message = new PayMatchProducer([
                                'inner_order_sn' => $inner_order_sn,
                                'amount' => $data['amount'],
                                'user_account' => $params['account_no'],
                                'currency' => $params['currency']]);
                            $routing_key = 'lodi.internal.pay.' . $params['currency'] . '.' . (int)$data['amount'];
                            $message->setRoutingKey($routing_key);
                            $message->setTtlMs(600000);
                            $producer = di()->get(PayProducer::class);
                            $producer->produce($message);
                        } catch (\Exception $e) {
                            Db::table('orders_pay')->where('id', $order_pay_id)->delete();
                            Db::table('merchant_pay_balance')->where(['merchant_account'=>$params['mer_account'],'currency'=>$params['currency']])->increment('balance',$params['order_amount']);
                            logger()->error('申请代付失败', [$e->getMessage()]);
                            throw new ServiceException(trans('lodipay.pay_apply_fail'),ResponseCode::FAIL);
                        }

                        $balance_log = new BalanceChangeProducer([
                            'inner_order_sn' => $inner_order_sn,
                            'amount' => $data['amount'],
                            'currency' => $params['currency'],
                            'merchant_account' => $params['mer_account'],
                            'order_type' => 2
                        ]);
                        $balance_producer = di()->get(Producer::class);
                        $balance_producer->produce($balance_log);

                        payNumIncr('lodipay:' . $params['currency'],'queue.lodi.internal.pay.PHP.'.(int)$data['amount']);
                        redis()->incrby($cache_total_amount_key,(int)$data['amount']);
                        if (redis()->ttl($cache_total_amount_key) < 0) {
                            $ttl = strtotime(date('Y-m-d 23:59:59')) - time();
                            $ttl > 0 && redis()->expire($cache_total_amount_key,$ttl);
                        }
                        return $this->response->success(['sys_order_no' => $inner_order_sn]);
                    } else {
                        throw new ServiceException(trans('lodipay.pay_apply_fail'),ResponseCode::FAIL);
                    }
                } else {
                    throw new ServiceException('The withdrawal amount does not meet the conditions',ResponseCode::FAIL);
                }
            } catch (ServiceException $e) {
                return $this->response->fail($e->getMessage());
            } finally {
                LockRedis::getInstance()->delete($lock_key,$uuid);
            }
        } else {
            return $this->response->fail('Do not operate frequently');
        }
    }

    /**
     * 查询代付结果
     */
    public function applyCheck(): ResponseInterface
    {
        $params = $this->request->getParsedBody();

        $rules = [
            'mer_account' => 'required',
            'order_no' => 'required|string',
            'sign' => 'required',
        ];
        $this->checkValidation($params, $rules, trans('lodipay.pay_apply_check'));

        $merchant_id = (int)Merchant::getAccountId($params['mer_account']);

        //查询代付订单
        $order = (array)Db::table('orders_pay')->where(['merchant_id' => $merchant_id, 'order_sn' => $params['order_no']])
            ->first(['currency', 'amount', 'pay_status as result_status', 'inner_order_sn as sys_order_no']);
        if (!$order) {
            return $this->response->fail(trans('lodipay.order_not'), 1500);
        }
        $order['mer_account'] = $params['mer_account'];
        $order['order_no'] = $params['order_no'];

        return $this->response->success($order);
    }

    /**
     * 查询余额
     */
    public function balanceQuery(): ResponseInterface
    {
        $params = $this->request->getParsedBody();

        $rules = [
            'mer_account' => 'required',
            'sign' => 'required',
        ];
        $this->checkValidation($params, $rules, trans('lodipay.pay_balance_query'));
        //查询各个币种的余额
        $balance = Db::table('merchant_pay_balance')
            ->where('merchant_account', $params['mer_account'])
            ->pluck('balance', 'currency')
            ->toArray();

        $result = [
            'currency' => (object)$balance,
            'mer_account' => $params['mer_account']
        ];

        return $this->response->success($result);
    }

    /**
     * 确认收款/未到账
     */
    public function confirmStatus(): ResponseInterface
    {
        try {
            //获取参数
            $params = $this->request->getParsedBody();
            $rules = [
                'mer_account' => 'required|string',
                'sub_orders_pay_sn' => 'required|string|max:60',
                'sign' => 'required|string',
                'status' => 'required|in:0,1',
                'orders_pay_sn' => 'required|string|max:60',
            ];

            //验证
            $this->checkValidation($params, $rules, trans('lodipay.confirm_Payment'));

            //根据查询订单，收款订单
            $innerOrderId = (string)$params['orders_pay_sn'];//平台订单号
            $subOrdersPayId = (string)$params['sub_orders_pay_sn'];//平台子订单号
            $status = (string)$params['status'];//平台争议状态

            //查询代收（支付）订单列表
            $collectionInfo = (array)Db::table('orders_collection')->where('inner_order_sn', $subOrdersPayId)->first(['merchant_account','order_status','currency','status','amount']);
            if (empty($collectionInfo) || $collectionInfo['order_status'] == 'success' || $collectionInfo['order_status'] == 'fail') {
                throw new ServiceException(trans('lodipay.collection_order_not_exist_or_completed'));
            }
            if ($collectionInfo['order_status'] == 'canceled') {
                throw new ServiceException('order has been canceled');
            }

            //未收到款操作，争议订单
            if ($status == 0) {
                $this->collectionOrderService->notReceived($subOrdersPayId);
                return $this->response->success();
            }

            $this->collectionOrderService->confirmPayment($collectionInfo['merchant_account'],$collectionInfo['currency'],$subOrdersPayId,$collectionInfo['amount'],$innerOrderId);

            return $this->response->success();
        } catch (\Exception $e) {
            return $this->response->fail($e->getMessage());
        }
    }

    /**
     * 上传sms
     */
    public function uploadSms(): ResponseInterface
    {
        $params = $this->request->getParsedBody();

        $rules = [
            'mer_account' => 'required',
            'account_no' => 'required|string|regex:/^[0-9]{9,11}$/',
            'message' => 'required|string|max:500',
            'sign' => 'required',
        ];
        $this->checkValidation($params, $rules, trans('lodipay.pay_upload_sms'));

        $merchant = $this->merchantService->getMerchantByAccount($params['mer_account']);
        $merchant_id = (int)$merchant['id'];
        //查询用户id
        $user = Db::table('user')->where('user_account', $params['account_no'])->first(['id']);
        if (!$user) {
            $time = date('Y-m-d H:i:s');
            $user_id = Db::table('user')->insertGetId(['merchant_id' => $merchant_id, 'user_account' => $params['account_no'], 'username' => $params['account_no'], 'created_at' => $time, 'updated_at' => $time]);
        } else {
            $user_id = $user->id;
        }
        $res = Db::table('user_sms')->insert([
            'user_id' => $user_id,
            'merchant_account' => $params['account_no'],
            'message' => $params['message']
        ]);

        if ($res) {
            return $this->response->success();
        }

        return $this->response->fail();
    }

}
