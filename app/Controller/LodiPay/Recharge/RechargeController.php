<?php

declare(strict_types=1);

namespace App\Controller\LodiPay\Recharge;

use App\Amqp\Producer\CallbackProducer;
use App\Amqp\Producer\DelayDirectProducer;
use App\Cache\Repository\LockRedis;
use App\Constant\ResponseCode;
use App\Controller\AbstractController;
use App\Exception\ServiceException;
use App\Model\Admin\AdminConfig;
use App\Service\AdminConfigService;
use App\Service\CollectionOrderService;
use App\Service\MerchantService;
use App\Service\TradeService;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Amqp\Producer;

class RechargeController extends AbstractController
{
    /**
     * @Inject()
     * @var TradeService
     */
    protected $tradeService;

    /**
     * @Inject()
     * @var CollectionOrderService
     */
    protected $collectionOrderService;

    /**
     * @Inject()
     * @var MerchantService
     */
    protected $merchantService;

    /**
     * @Inject()
     * @var AdminConfigService
     */
    protected $adminConfigService;


    /**
     * 充值匹配
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Throwable
     */
    public function store()
    {
        $data = $this->request->all();
        $rules = [
            'mer_account' => 'required',
            'user_account' => 'required|string|regex:/^[0-9]{9,11}$/',
            'pay_code' => 'string|max:20',
            'sign' => 'required',
            'order_no' => 'required|string|max:30',
            'amount' => 'required|integer',
            'currency' => 'required|string|exists:currency,currency_type',
            'callback_url' => 'required|url',
            'station_url' => 'url|string|max:100',
        ];
        $this->checkValidation($data, $rules, trans('lodipay.account_recharge_store'));

        $lock_key = 'recharge:'.$data['mer_account'] . '-' . $data['order_no'];
        $uuid = uniqid('recharge',true);
        if (LockRedis::getInstance()->lock($lock_key,10,$uuid)) {
            try {
                //金额限制
                $temp_amount = (int)bcmul((string)$data['amount'], '100', 0);
                if($temp_amount < 10000 || $temp_amount % 10000 != 0) {
                    throw new ServiceException('Amount must be a multiple of 100');
                }
                //系统限制
                $config_orders = $this->adminConfigService->getConfig(['min_recharge_amount']);
                $min_recharge_amount = $config_orders['min_recharge_amount'];
                if (bccomp((string)$data['amount'],(string)$min_recharge_amount,2) == -1) {
                    throw new ServiceException('The collection amount cannot be less than '.$min_recharge_amount, ResponseCode::FAIL);
                }
                //判断商户号是否开启
                $merchant = $this->merchantService->getMerchantByAccount($data['mer_account']);
                if ($merchant['is_collection_behalf'] == 0) {
                    throw new ServiceException(trans('public.account_rechange_close'), ResponseCode::FAIL);
                }
                $merchant_id = (int)$merchant['id'];

                //查询是否存在订单
                $collection_order = Db::table('orders_collection')->where(['order_sn' => $data['order_no'], 'merchant_id' => $merchant_id])->count();
                if ($collection_order) {
                    throw new ServiceException(trans('public.order_account_exists'), ResponseCode::FAIL);
                }
                //查询是否存在GCASH
                $date = date('Y-m-d H:i:s');
                $user = Db::table('user')->where(['user_account' => $data['user_account']])->first(['id','status']);
                if ($user) {
                    if ($user->status == 0) throw new ServiceException('Your account has been disabled and cannot be used',ResponseCode::FAIL);
                    $user_id = $user->id;
                } else {
                    $user_id = Db::table('user')->insertGetId([
                        'merchant_id' => $merchant_id,
                        'user_account' => $data['user_account'],
                        'username' => $data['user_account'],
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);
                }

                //判断waiting单数
                $waiting_num = Db::table('orders_collection')->where(['merchant_id'=>$merchant_id,'user_id'=>$user_id,'order_status'=>'waiting'])
                    ->where('created_at','>',date('Y-m-d H:i:s',time() - 7200))->count();
                if ($waiting_num >= $merchant['recharge_waiting_limit']) throw new ServiceException('You have multiple recharge orders in progress, please recharge after processing',ResponseCode::FAIL);

                $amount = $data['amount'];
                $currency = $data['currency'];
                $inner_order_sn = (string)snowflakeId();
                //撮合
                list($pay_inner_order_sn) = $this->tradeService->RechargeMatch($inner_order_sn,$amount,$currency);

                $result = Db::table('orders_collection')->insert([
                    'order_sn' => $data['order_no'],
                    'inner_order_sn' => $inner_order_sn,
                    'merchant_id' => $merchant_id,
                    'merchant_account' => $data['mer_account'],
                    'payment' => isset($data['pay_code']) ? $data['pay_code'] : '',
                    'currency' => $data['currency'],
                    'amount' => $data['amount'],
                    'order_status' => 'waiting',
                    'user_id' => $user_id,
                    'user_account' => $data['user_account'],
                    'status' => 2,
                    'order_type' => 1,
                    'pay_inner_order_sn' => $pay_inner_order_sn,
                    'created_at' => $date,
                    'updated_at' => $date,
                    'callback_url' => $data['callback_url'],
                    'station_url' => isset($data['station_url']) ? $data['station_url'] : '',
                ]);
                if (!$result) {
                    throw new ServiceException(trans('lodipay.insert_order'), ResponseCode::FAIL);
                }

                //12分钟检查上传凭证是否超时
                $delay_message = new DelayDirectProducer(['orders_collection_sn' => $inner_order_sn, 'event' => 'match_success']);
                $delay_message->setDelayMs(720000);
                $producer = di()->get(Producer::class);
                $producer->produce($delay_message);

                //代付
                $pay_msg = new CallbackProducer(['event' => 'match_success','inner_order_sn' => $inner_order_sn]);
                $producer->produce($pay_msg);

                $token = encrypt(json_encode(['mer_account'=>$data['mer_account'],'mer_no'=>$inner_order_sn,'expire'=>time() + 1800]),env('H5_TOKEN_SALT'));
                return $this->response->success(['jump_url' => env('H5_JUMP_HOST') . '?mer_no=' . $inner_order_sn.'&token='.$token]);
            } catch (ServiceException $e) {
                logger()->error('充值匹配失败',[$e->getMessage()]);
                return $this->response->fail($e->getMessage());
            } finally {
                LockRedis::getInstance()->delete($lock_key,$uuid);
            }
        }
        return $this->response->fail('Do not operate frequently');
    }

    /**
     * 列表
     */
    public function index()
    {
        $data = $this->request->all();
        $rules = [
            'mer_account' => 'required',
            'sign' => 'required',
            'order_no' => 'required|string'
        ];
        $this->checkValidation($data, $rules, trans('lodipay.account_recharge_get'));

        $order = (array)Db::table('orders_collection')->where(['order_sn' => $data['order_no'], 'merchant_account' => $data['mer_account']])->first(['inner_order_sn','amount','order_status']);
        if (!$order) {
            return $this->response->fail(trans('lodipay.order_not'),1500);
        } else {
            $result = [
                'mer_no' => $order['inner_order_sn'],
                'order_no' => (string)$data['order_no'],
                'amount' => $order['amount'],
                'result_status' => $order['order_status'],
            ];
        }

        return $this->response->success($result);
    }


    public function uploadCert()
    {
        $data = $this->request->all();
        $rules = [
            'mer_account' => 'required',
            'order_no' => 'required|string|max:30'
        ];

        $this->checkValidation($data, $rules, trans('lodipay.h5_upload'));

        $order = Db::table('orders_collection')->where(['order_sn'=>$data['order_no'],'merchant_account'=>$data['mer_account']])->first(['inner_order_sn']);
        if (!$order) return $this->response->fail('Order does not exist');
        $inner_order_sn = $order->inner_order_sn;

        $token = encrypt(json_encode(['mer_account'=>$data['mer_account'],'mer_no'=>$inner_order_sn,'expire'=>time() + 1800]),env('H5_TOKEN_SALT'));
        return $this->response->success(['jump_url' => env('H5_JUMP_UPLOAD_CERT') . '?mer_no=' . $inner_order_sn.'&token='.$token]);
    }

    public function showCert()
    {
        $data = $this->request->all();
        $rules = [
            'mer_account' => 'required',
            'order_no' => 'required|string|max:30'
        ];

        $this->checkValidation($data, $rules, trans('lodipay.h5_upload'));

        $order = Db::table('orders_collection')->where(['order_sn'=>$data['order_no'],'merchant_account'=>$data['mer_account']])->first(['inner_order_sn']);
        if (!$order) return $this->response->fail('Order does not exist');
        $inner_order_sn = $order->inner_order_sn;

        $token = encrypt(json_encode(['mer_account'=>$data['mer_account'],'mer_no'=>$inner_order_sn,'expire'=>time() + 1800]),env('H5_TOKEN_SALT'));
        return $this->response->success(['jump_url' => env('H5_JUMP_SHOW_CERT') . '?mer_no=' . $inner_order_sn.'&token='.$token]);
    }

    public function showMatch()
    {
        $data = $this->request->all();
        $rules = [
            'mer_account' => 'required',
            'order_no' => 'required|string|max:30'
        ];

        $this->checkValidation($data, $rules, trans('lodipay.h5_upload'));

        $order = Db::table('orders_collection')->where(['order_sn'=>$data['order_no'],'merchant_account'=>$data['mer_account']])->first(['inner_order_sn']);
        if (!$order) return $this->response->fail('Order does not exist');
        $inner_order_sn = $order->inner_order_sn;

        $token = encrypt(json_encode(['mer_account'=>$data['mer_account'],'mer_no'=>$inner_order_sn,'expire'=>time() + 1800]),env('H5_TOKEN_SALT'));
        return $this->response->success(['jump_url' => env('H5_JUMP_HOST') . '?mer_no=' . $inner_order_sn.'&token='.$token]);
    }

    public function cancel()
    {
        $data = $this->request->all();
        $rules = [
            'mer_account' => 'required',
            'order_no' => 'required|string|max:30'
        ];

        $this->checkValidation($data, $rules, trans('lodipay.h5_upload'));
        try {
            $collection_order = (array)Db::table('orders_collection')->where(['order_sn'=>$data['order_no'],'merchant_account'=>$data['mer_account']])->first(['status','order_status','amount','inner_order_sn','pay_inner_order_sn','currency']);
            if (empty($collection_order)) throw new ServiceException('Order does not exist',ResponseCode::FAIL);
            $this->collectionOrderService->cancel($collection_order);
        } catch (ServiceException $e) {
            return $this->response->fail();
        }
        return $this->response->success();
    }
}
