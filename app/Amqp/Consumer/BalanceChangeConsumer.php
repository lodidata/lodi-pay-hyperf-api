<?php

namespace App\Amqp\Consumer;


use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\Type;
use Hyperf\Amqp\Result;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * @Consumer(exchange="exchange.lodi.balance_change", routingKey="lodi.balance_change", queue="queue.lodi.balance_change", name="BalanceChangeConsumer", nums=1)
 */
class BalanceChangeConsumer extends ConsumerMessage
{
    protected $type = Type::DIRECT;


    public function consumeMessage($data, AMQPMessage $message): string
    {
        try {
            if (isset($data['merchant_account']) && isset($data['currency']) && isset($data['order_type']) && isset($data['amount'])) {
                $merchant_account = (string)$data['merchant_account'];
                $currency = $data['currency'];
                $order_type = $data['order_type'];
                $transaction_type = isset($data['transaction_type']) ? $data['transaction_type'] : $order_type;
                $amount = (string)$data['amount'];
                if (in_array($order_type,[1,2])) {
                    $old_balance = Db::table('merchant_balance_change_log')->where(['merchant_account'=>$merchant_account,'currency'=>$currency,'order_type'=>$order_type])->orderByDesc('id')->value('change_after');
                    if (empty($old_balance)) {
                        if ($order_type == 1) {
                            $new_balance = Db::table('merchant_collection_balance')->where(['merchant_account'=>$merchant_account,'currency'=>$currency])->value('balance');
                            $old_balance = bcsub((string)$new_balance,$amount,2);
                        } else {
                            $new_balance = Db::table('merchant_pay_balance')->where(['merchant_account'=>$merchant_account,'currency'=>$currency])->value('balance');
                            if ($transaction_type == 6) {//驳回
                                $old_balance = bcsub((string)$new_balance,$amount,2);
                            } else {
                                $old_balance = bcadd((string)$new_balance,$amount,2);
                            }

                        }

                    } else {
                        if ($order_type == 1) {
                            $new_balance = bcadd((string)$old_balance,$amount,2);
                        } else {
                            if ($transaction_type == 6) {
                                $new_balance = bcadd((string)$old_balance,$amount,2);
                            } else {
                                $new_balance = bcsub((string)$old_balance,$amount,2);
                            }

                        }
                    }
                    Db::table('merchant_balance_change_log')->insert([
                        'merchant_account' => $merchant_account,
                        'currency' => $currency,
                        'transaction_type' => $transaction_type,
                        'order_type' => $order_type,
                        'order_sn' => $data['inner_order_sn'],
                        'change_after' => $new_balance,
                        'change_before' => $old_balance
                    ]);
                }
            }
            return Result::ACK;

        } catch (\Exception $e) {
            logger()->error('商户余额变更消费异常',[$e->getMessage()]);
        }
    }


}