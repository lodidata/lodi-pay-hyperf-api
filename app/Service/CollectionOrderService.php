<?php

namespace App\Service;

use App\Amqp\Producer\BalanceChangeProducer;
use App\Amqp\Producer\CallbackProducer;
use App\Amqp\Producer\PayMatchProducer;
use App\Amqp\Producer\PayMatchSplitProducer;
use App\Exception\ServiceException;
use App\Support\PayProducer;
use App\Support\PaySplitProducer;
use Hyperf\Amqp\Message\Type;
use Hyperf\Amqp\Producer;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class CollectionOrderService
{
    /**
     * @Inject()
     * @var MerchantService
     */
    protected $merchantService;

    public function confirmPayment($collection_merchant_account,$currency,$collection_inner_order_sn,$collection_amount,$pay_inner_order_sn)
    {
        //查询主订单
        $orderPayInfo = (array)Db::table('orders_pay')->where(['inner_order_sn' => $pay_inner_order_sn])->first(['amount']);
        if (empty($orderPayInfo)) {
            throw new ServiceException(trans('lodipay.order_pay_not_exist_or_completed'));
        }
        $pay_amount = $orderPayInfo['amount'];
        try {
            DB::beginTransaction();

            //查找全部子订单判断是否完成,并且剩余匹配金额为0，完成了就修改主订单状态
            $paid_orders_amount = Db::table('orders_collection')->where(['pay_inner_order_sn' => $pay_inner_order_sn, 'status' => 6])->sum('amount');
            $total_amount = bcadd((string)$paid_orders_amount, (string)$collection_amount, 2);
            logger()->info('订单成功金额对比',[$paid_orders_amount,$collection_amount,$total_amount,$pay_amount,$pay_inner_order_sn]);
            $date_time = date('Y-m-d H:i:s');
            if (bccomp((string)$total_amount,(string)$pay_amount,2) == 0) {
                Db::table('orders_pay')->where(['inner_order_sn' => $pay_inner_order_sn])->update(['status' => 6, 'pay_status' => 'success', 'updated_at' => $date_time]);
            }
            //修改代收（支付）状态
            Db::table('orders_collection')->where('inner_order_sn', $collection_inner_order_sn)->update(['status' => 6, 'order_status' => 'success', 'updated_at' => $date_time]);
            //商户余额
            if (!empty($collection_merchant_account)) {
                Db::table('merchant_collection_balance')->where(['merchant_account' => $collection_merchant_account, 'currency' => $currency])->increment('balance', $collection_amount);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollBack();
            throw new ServiceException($e->getMessage());
        }

        //确认收款
        $message = new CallbackProducer(['event' => 'confirm_payment','inner_order_sn' => $collection_inner_order_sn]);
        $producer = di()->get(Producer::class);
        $producer->produce($message);
        //余额变更日志
        $balance_log = new BalanceChangeProducer([
            'inner_order_sn' => $collection_inner_order_sn,
            'amount' => $collection_amount,
            'currency' => $currency,
            'merchant_account' => $collection_merchant_account,
            'order_type' => 1
        ]);
        $producer->produce($balance_log);
    }

    public function notReceived($orders_collection_sn)
    {
        $trialInfo = Db::table('orders_collection_trial')->where(['orders_collection_sn'=>$orders_collection_sn])->count();
        if (empty($trialInfo)) {
            $insert_res = Db::table('orders_collection_trial')->insert([
                'orders_collection_sn' => $orders_collection_sn,
                'description' => '未到账'
            ]);
            //修改中间表数据状态为完成
            if (!$insert_res) {
                throw new ServiceException(trans('lodipay.server_error'));
            }
        }
    }

    public function cancel($collection_order)
    {
        if ($collection_order['status'] == 10) {
            throw new ServiceException('The order has been cancelled');
        }
        if (!in_array($collection_order['status'],[2,3])) {
            throw new ServiceException('The order cannot be cancelled');
        }
        $pay_inner_order_sn = (string)$collection_order['pay_inner_order_sn'];
        $pay_order = (array)Db::table('orders_pay')->where('inner_order_sn',$pay_inner_order_sn)->first(['amount','user_account']);
        $msg_data = [
            'inner_order_sn' => $pay_inner_order_sn,
            'amount' => $collection_order['amount'],
            'user_account' => $pay_order['user_account'],
            'currency' => $collection_order['currency']
        ];
        $currency = $collection_order['currency'];
        $amount = $collection_order['amount'];
        $collection_inner_order_sn = (string)$collection_order['inner_order_sn'];
        try {
            Db::table('orders_collection')->where(['inner_order_sn'=>$collection_inner_order_sn,'status'=>$collection_order['status']])->update(['status'=>10,'order_status'=>'canceled','updated_at'=>date('Y-m-d H:i:s')]);
            if (bccomp((string)$collection_order['amount'],(string)$pay_order['amount'],2) == 0) {//未拆单
                $message = new PayMatchProducer($msg_data);
                $routing_key = 'lodi.internal.pay.' . $currency . '.' . (int)$amount;
                $message->setRoutingKey($routing_key);
                $message->setTtlMs(300000);
                $producer = di()->get(PayProducer::class);
            } else {
                $message = new PayMatchSplitProducer($msg_data);
                $routing_key = 'lodi.internal.pay.default.' . $currency . '.' . (int)$amount;
                $message->setRoutingKey($routing_key);
                $message->setType(Type::DIRECT);
                $message->setTtlMs(600000);
                $producer = di()->get(PaySplitProducer::class);
            }
            $producer->produce($message);
            payNumIncr('lodipay:' . $currency,'queue.'.$routing_key);
        } catch (\Exception $e) {
            Db::table('orders_collection')->where(['inner_order_sn',$collection_inner_order_sn,'status'=>10])->update(['status'=>$collection_order['status'],'order_status'=>$collection_order['order_status']]);
            throw new ServiceException('Cancel failed');
        }

        //取消充值
        $msg = new CallbackProducer(['event' => 'cancel_recharge','inner_order_sn' => $collection_inner_order_sn]);
        $callback_producer = di()->get(Producer::class);
        $callback_producer->produce($msg);
    }

    public function fail($collection_order)
    {
        if ($collection_order['status'] == 9) {
            throw new ServiceException('The order has been processed');
        }
        $collection_inner_order_sn = (string)$collection_order['inner_order_sn'];

        $res = Db::table('orders_collection')->where(['inner_order_sn'=>$collection_inner_order_sn,'status'=>$collection_order['status'],'order_type'=>1])->update(['status'=>9,'order_status'=>'fail','updated_at'=>date('Y-m-d H:i:s')]);

        if ($res) {
            //代收失败
            $msg = new CallbackProducer(['event' => 'recharge_fail','inner_order_sn' => $collection_inner_order_sn]);
            $callback_producer = di()->get(Producer::class);
            $callback_producer->produce($msg);
        }
    }
}