<?php

declare(strict_types=1);

namespace App\Amqp\Consumer;

use Hyperf\Amqp\Result;
use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\DbConnection\Db;
use PhpAmqpLib\Message\AMQPMessage;
use Hyperf\Amqp\Message\Type;

/**
 * @Consumer(exchange="dead.exc.lodi.pay", routingKey="dead.lodi.pay", queue="dead.queue.lodi.pay", name="DeadPayConsumer", nums=10)
 */
class DeadPayConsumer extends ConsumerMessage
{
    protected $type = Type::DIRECT;

    public function consumeMessage($data, AMQPMessage $message): string
    {
        if (isset($data['inner_order_sn']) && isset($data['amount']) && isset($data['currency']) && isset($data['user_account'])) {
            $amount = $data['amount'];
            $currency = $data['currency'];
            $queue_name = 'queue.lodi.internal.pay.'.$currency.'.'.(int)$amount;

            try {
                $inner_order_sn = (string)$data['inner_order_sn'];
                Db::table('orders_pay')->where('inner_order_sn', $inner_order_sn)->whereIn('status', [1, 8])->update([
                    'match_timeout_amount' => Db::raw("match_timeout_amount + {$amount}"),
                    'status' => 8,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            } catch (\Exception $e) {
                payNumDecr('lodipay:' . $currency, $queue_name);
                logger()->error('超时未匹配修改数据库失败', [$data, $e->getMessage()]);
                return Result::DROP;
            }
            payNumDecr('lodipay:' . $currency, $queue_name);
        }

        return Result::ACK;
    }
}
