<?php

namespace App\Amqp\Producer;

use Hyperf\Amqp\Annotation\Producer;
use Hyperf\Amqp\Message\ProducerMessage;
use Hyperf\Amqp\Message\Type;

/**
 * @Producer(exchange="exchange.lodi.balance_change", routingKey="lodi.balance_change")
 */
class BalanceChangeProducer extends ProducerMessage
{
    protected $type = Type::DIRECT;

    public function __construct(array $data)
    {
        $this->payload = $data;
    }

}