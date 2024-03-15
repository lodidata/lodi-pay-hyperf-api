<?php

declare(strict_types=1);

namespace App\Amqp\Consumer;

use Hyperf\Amqp\Builder\QueueBuilder;
use PhpAmqpLib\Message\AMQPMessage,
    Hyperf\Amqp\Message\Type,
    Hyperf\Amqp\Message\ConsumerMessage,
    Hyperf\Amqp\Annotation\Consumer;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * @Consumer(nums=1, enable=false)
 */
class PayMatchSplitConsumer extends ConsumerMessage
{
    protected $exchange='exchange.lodi.internal.pay.default';
    protected $type=Type::DIRECT;
    protected $queue = '';
    /**
     * Overwrite.
     */
    public function getQueueBuilder(): QueueBuilder
    {
        return (new QueueBuilder())->setQueue((string) $this->getQueue())
            ->setArguments(new AMQPTable([
                // 消息过期时间
                'x-message-ttl' => 7200000,
                // 死信交换机
                'x-dead-letter-exchange' => 'dead.exc.lodi.pay.split',
                // 死信路由键
                'x-dead-letter-routing-key' => 'dead.lodi.pay.split'
            ]));
    }
}
