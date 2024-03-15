<?php

namespace App\Amqp\Producer;

use Hyperf\Amqp\Annotation\Producer;
use Hyperf\Amqp\Message\ProducerMessage;
use Hyperf\Amqp\Message\Type;

/**
 * @Producer(exchange="exchange.lodi.callback", routingKey="lodi.callback")
 */
class CallbackProducer extends ProducerMessage
{
    protected $type = Type::DIRECT;

    public function __construct(array $data)
    {
        $this->payload = $data;
    }

}