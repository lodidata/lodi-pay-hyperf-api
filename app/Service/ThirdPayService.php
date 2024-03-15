<?php

namespace App\Service;

use App\Constant\ResponseCode;
use App\Exception\ServiceException;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use function _PHPStan_b8e553790\React\Promise\Stream\first;

class ThirdPayService
{
    /**
     * @Inject()
     * @var PayConfigService
     */
    protected $payConfigService;

    public function pay($pay_inner_order_sn,$collection_amount,$pay_type='')
    {
        $pay_order = (array)Db::table('orders_pay')->where('inner_order_sn',$pay_inner_order_sn)->first(['merchant_id', 'merchant_account','amount','currency','user_account']);
        if (!$pay_order) {
            throw new ServiceException("代付订单:{$pay_inner_order_sn}不存在");
        }
        $merchant_account = $pay_order['merchant_account'];
        $pay_amount = $pay_order['amount'];
        $currency = $pay_order['currency'];
        $user_account = $pay_order['user_account'];

        //获取商户支付配置
        $pay_config = $this->payConfigService->getConfig($merchant_account,$pay_type);
        $pay_name = $pay_config['name']??'';
        if (empty($pay_name)) {
            throw new ServiceException("商户:{$merchant_account}没有可用代付");
        }

        $classname = 'App\Service\Pay\\'.$pay_name.'Service';
        if (class_exists($classname)) {
            $payService = make($classname);
        } else {
            throw new ServiceException("商户:{$merchant_account}代付类:{$classname}不存在");
        }
        $inner_order_sn = (string)snowflakeId();
        $result = Db::table('orders_collection')->insert([
            'order_sn' => date('YmdHis').mt_rand(10000,99999),
            'inner_order_sn' => $inner_order_sn,
            'pay_inner_order_sn' => $pay_inner_order_sn,
            'payment' => $payService->getPayType(),
            'amount' => $collection_amount,
            'currency' => $currency,
            'merchant_id' => $pay_order['merchant_id'],
            'merchant_account' => $pay_order['merchant_account'],
            'status' => 8,
            'order_type' => 2,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        if ($result) {
            $is_split = bccomp((string)$collection_amount,(string)$pay_amount,2) == 0 ? 0 : 1;
            $payService->apply($merchant_account, $collection_amount, $inner_order_sn, $user_account,$pay_inner_order_sn,$is_split);
        }

        return $inner_order_sn;
    }

    public function payFailAmount($pay_inner_order_sn, $pay_type = '')
    {
        $pay_order = (array)Db::table('orders_pay')->where('inner_order_sn',(string)$pay_inner_order_sn)->first(['id','merchant_id','merchant_account','status','amount','match_timeout_amount','currency','user_account']);
        if (!$pay_order) {
            throw new ServiceException("The order does not exist");
        }

        $merchant_account = $pay_order['merchant_account'];
        $pay_amount = $pay_order['amount'];
        $match_timeout_amount = $pay_order['match_timeout_amount'];
        $currency = $pay_order['currency'];
        $user_account = $pay_order['user_account'];

        $third_pay_order = Db::table('orders_collection')->where(['pay_inner_order_sn'=>(string)$pay_inner_order_sn,'order_type'=>3])->first(['id','status','amount']);
        if ($third_pay_order) {
            if ($third_pay_order->status == 8) {//已经存在三方代付，且还在进行中
                throw new ServiceException("There is an unfinished third-party payment order");
            }
            if ($third_pay_order->status == 6) {//已经存在三方代付，且成功
                throw new ServiceException("Order has been paid");
            }
            $fail_amount = $third_pay_order->amount;
        } else {
            //计算失败金额 match_timeout_amount + 订单失败
            $collection_amount = Db::table('orders_collection')
                ->where('pay_inner_order_sn',(string)$pay_inner_order_sn)
                ->whereIn('status', [6, 9])
                ->whereIn('order_type',[1,2])
                ->get(['status','amount'])->toArray();
            $collection_fail_amount = '0';
            $collection_success_amount = '0';
            foreach ($collection_amount as $item){
                switch ($item->status){
                    case 9://失败
                        $collection_fail_amount = bcadd((string)$collection_fail_amount,(string)$item->amount,2);
                        break;
                    case 6://成功
                        $collection_success_amount = bcadd((string)$collection_success_amount,(string)$item->amount,2);
                        break;
                }
            }
            $fail_amount = bcadd((string)$collection_fail_amount,(string)$match_timeout_amount,2);

            $total_amount = bcadd($fail_amount,$collection_success_amount,2);
            if (bccomp($total_amount,(string)$pay_amount,2) != 0) {
                throw new ServiceException("There is an ongoing sub-order that cannot be paid");
            }
        }



        //获取商户支付配置
        $pay_config = $this->payConfigService->getConfig($merchant_account,$pay_type);
        $pay_name = $pay_config['name']??'';
        if (empty($pay_name)) {
            throw new ServiceException("商户:{$merchant_account}没有可用代付");
        }

        $classname = 'App\Service\Pay\\'.$pay_name.'Service';
        if (class_exists($classname)) {
            $payService = make($classname);
        } else {
            throw new ServiceException("商户:{$merchant_account}代付类:{$classname}不存在");
        }
        //代付订单只创建一单
        $inner_order_sn = (string)snowflakeId();
        if (!$third_pay_order) {
            $result = Db::table('orders_collection')->insert([
                'order_sn' => date('YmdHis').mt_rand(10000,99999),
                'inner_order_sn' => $inner_order_sn,
                'pay_inner_order_sn' => $pay_inner_order_sn,
                'payment' => $payService->getPayType(),
                'amount' => $fail_amount,
                'currency' => $currency,
                'merchant_id' => $pay_order['merchant_id'],
                'merchant_account' => $pay_order['merchant_account'],
                'status' => 8,
                'order_type' => 3,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        } else {
            if ($third_pay_order->status == 8) {//已经存在三方代付，且还在进行中
                throw new ServiceException("There is an unfinished third-party payment order");
            } else {
                $result = Db::table('orders_collection')->where('id',(int)$third_pay_order->id)->update([
                    'order_sn' => date('YmdHis').mt_rand(10000,99999),
                    'inner_order_sn' => $inner_order_sn,
                    'payment' => $payService->getPayType(),
                    'amount' => $fail_amount,
                    'status' => 8,
                    'order_type' => 3,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }

        }


        if ($result) {
            $is_split = bccomp((string)$fail_amount,(string)$pay_amount,2) == 0 ? 0 : 1;
            if ($match_timeout_amount > 0) {
                Db::table('orders_pay')->where('id',(int)$pay_order['id'])->decrement('match_timeout_amount',$match_timeout_amount);
            }
            $payService->apply($merchant_account, $fail_amount, $inner_order_sn, $user_account,$pay_inner_order_sn,$is_split,0);
        } else {
            throw new ServiceException('Database operation failed');
        }

        return $inner_order_sn;
    }


}