<?php

namespace App\Service\Pay;

use App\Amqp\Producer\BalanceChangeProducer;
use App\Amqp\Producer\CallbackProducer;
use App\Service\PayConfigService;
use Hyperf\Amqp\Producer;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

abstract class AbstractPayService
{
    protected $payType = '';
    /**
     * @Inject()
     * @var PayConfigService
     */
    protected $payConfigService;

    public function getPayType(): string
    {
        return $this->payType;
    }

    public function getConfig($merchant_account): array
    {
        return $this->payConfigService->getConfig($merchant_account,$this->getPayType());
    }

    abstract public function apply($merchant_account, $total_amount, $order_sn, $user_account, $orders_pay_sn, $is_split, $is_daifu);

    public function updatePayOrder($orders_collection_sn, $orders_pay_sn, $is_split, $is_daifu, $status = 'fail'): void
    {
        if ($status == 'success') {
            $status = 6;
            $pay_status = 'success';
        } else {
            $status = 9;
            $pay_status = 'fail';
        }
        $updated_time = date('Y-m-d H:i:s');

        $collection_order = (array)Db::table('orders_collection')->where(['inner_order_sn' => $orders_collection_sn])->first(['id','amount','merchant_account','currency']);
        if (!empty($collection_order)) {
            Db::table('orders_collection')->where('id',(int)$collection_order['id'])->whereIn('status',[1,8])->update(['status' => $status, 'order_status'=> $pay_status,'updated_at' => $updated_time]);
            if (!$is_split) {
                Db::table('orders_pay')->where(['inner_order_sn' => $orders_pay_sn])->whereIn('status',[1,8])->update(['status' => $status, 'pay_status' => $pay_status, 'updated_at' => $updated_time]);
            } else {
                if ($status == 6) {
                    $pay_amount = Db::table('orders_pay')->where(['inner_order_sn' => $orders_pay_sn])->value('amount');
                    $paid_orders_amount = Db::table('orders_collection')->where(['pay_inner_order_sn' => $orders_pay_sn, 'status' => 6])->sum('amount');
                    if (bcsub((string)$paid_orders_amount,(string)$pay_amount,2) >= 0) {
                        Db::table('orders_pay')->where(['inner_order_sn' => $orders_pay_sn])->whereIn('status',[1,8])->update(['status' => 6, 'pay_status' => 'success', 'updated_at' => $updated_time]);
                    }
                }
            }

            if ($status == 6) {
                //兜底支付
                $message = new CallbackProducer(['event' => 'third_pay','inner_order_sn' => $orders_collection_sn]);
                $producer = di()->get(Producer::class);
                $producer->produce($message);

                Db::table('merchant_pay_balance')->where(['merchant_account' => (string)$collection_order['merchant_account'], 'currency' => $collection_order['currency']])->increment('balance', $collection_order['amount']);
                //余额变更日志
                $balance_log = new BalanceChangeProducer([
                    'inner_order_sn' => $orders_collection_sn,
                    'amount' => $collection_order['amount'],
                    'currency' => $collection_order['currency'],
                    'merchant_account' => $collection_order['merchant_account'],
                    'transaction_type' => 7,
                    'order_type' => 2
                ]);
                $producer->produce($balance_log);
            }

            if ($is_daifu == 1) {
                $tranfer_status = $status == 6 ?  2 :  0;
                Db::table('transfer_record')->where(['inner_order_sn' => (string)$orders_collection_sn, 'status' => 1])->update(['status' => $tranfer_status]);
            }
        } else {
            logger()->error("三方代付回调",["订单不存在orders_collection表{$orders_collection_sn}"]);
        }

    }

    protected function sign($data, $secret_key)
    {
        if (isset($data['sign'])) {
            unset($data['sign']);
        }
        ksort($data);

        $str = '';
        foreach ($data as $k => $v) {
            if (is_null($v) || $v === '') continue;
            $str .= $k . '=' . $v . '&';
        }
        $sign_str = $str . 'key=' . $secret_key;
        return md5($sign_str);
    }

    //验证回调签名
    public function verifySign($data, $secret_key): bool
    {
        if (!isset($data['sign'])) return false;

        $sign = $data['sign'];
        unset($data['sign']);
        ksort($data);

        $str = '';
        foreach ($data as $k => $v) {
            if (is_null($v) || $v === '') continue;
            $str .= $k . '=' . $v . '&';
        }
        $sign_str = $str . 'key=' . $secret_key;
        $sign_new = md5($sign_str);
        if ($sign === $sign_new) {
            return true;
        }
        return false;
    }
}