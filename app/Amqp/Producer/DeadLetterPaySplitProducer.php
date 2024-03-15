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
class DeadLetterPaySplitProducer extends ProducerMessage
{
    protected $exchange = 'dead.exc.lodi.pay.split';
    protected $routingKey = 'dead.lodi.pay.split';
    protected $type = Type::DIRECT;

    public function __construct(array $data)
    {
        $this->payload = $data;
    }
}
