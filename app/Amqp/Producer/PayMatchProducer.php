<?php

declare(strict_types=1);

namespace App\Amqp\Producer;

use Hyperf\Amqp\Annotation\Producer;
use Hyperf\Amqp\Message\ProducerMessage;
use Hyperf\Amqp\Message\Type;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * @Producer()
 */
class PayMatchProducer extends ProducerMessage
{
    protected $exchange = 'exchange.lodi.internal.pay';

    protected $type = Type::DIRECT;

    public function __construct(array $data)
    {
        $this->payload = $data;
    }

    /**
     * Set the delay time.
     * @return $this
     */
    public function setTtlMs(int $millisecond): self
    {
        $this->properties['expiration'] = $millisecond;
        return $this;
    }
}
