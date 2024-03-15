<?php

declare(strict_types=1);

namespace App\Amqp\Producer;

use Hyperf\Amqp\Annotation\Producer;
use Hyperf\Amqp\Message\ProducerMessage;
use Hyperf\Amqp\Message\Type;
use Hyperf\Amqp\Message\ProducerDelayedMessageTrait;

/**
 * @Producer(exchange="exchange.lodi.delay", routingKey="lodi.delay")
 */
class DelayDirectProducer extends ProducerMessage
{
    use ProducerDelayedMessageTrait;

    protected $type = Type::DIRECT;

    public function __construct($data)
    {
        $this->payload = $data;
    }
}
