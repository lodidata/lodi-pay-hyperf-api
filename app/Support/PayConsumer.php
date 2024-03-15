<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Support;

use Hyperf\Amqp\AMQPConnection;
use Hyperf\Amqp\Message\ConsumerMessageInterface;
use Hyperf\Amqp\Message\Type;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Hyperf\Amqp\Builder;
use Hyperf\Amqp\ConnectionFactory;
use Throwable,
    Hyperf\Amqp\Message\MessageInterface,
    PhpAmqpLib\Channel\AMQPChannel,
    Hyperf\Amqp\Exception\MessageException;

class PayConsumer extends Builder
{
    /**
     * @var bool
     */
    protected $status = true;


    public function __construct(
        ContainerInterface $container,
        ConnectionFactory $factory
    ) {
        parent::__construct($container, $factory);
    }

    public function consumerOne(ConsumerMessageInterface $consumerMessage)
    {
        $connection = $this->factory->getConnection($consumerMessage->getPoolName());
        try {
            $channel = $connection->getConfirmChannel();

            $this->declare($consumerMessage, $channel);

            $message = $channel->basic_get($consumerMessage->getQueue());
            if ($message->body) {
                $message->ack();
            }
            $connection->releaseChannel($channel, true);
            return $message;
        } catch (\Throwable $exception) {
            isset($channel) && $channel->close();
            throw $exception;
        }
    }

    public function getChannel(ConsumerMessageInterface $consumerMessage)
    {
        $connection = $this->factory->getConnection($consumerMessage->getPoolName());
        try {
            $channel = $connection->getConfirmChannel();
            $this->declare($consumerMessage, $channel);
        } catch (\Throwable $exception) {
            isset($channel) && $channel->close();
            throw $exception;
        }
        return [$connection, $channel];
    }

    public function putChannel(AMQPConnection $connection, $channel)
    {
        $connection->releaseChannel($channel, true);
    }

    public function declare(MessageInterface $message, ?AMQPChannel $channel = null, bool $release = false): void
    {
        if (! $message instanceof ConsumerMessageInterface) {
            throw new MessageException('Message must instanceof ' . ConsumerMessageInterface::class);
        }

        if (! $channel) {
            $connection = $this->factory->getConnection($message->getPoolName());
            $channel = $connection->getChannel();
        }

        parent::declare($message, $channel);

        $builder = $message->getQueueBuilder();

        $channel->queue_declare($builder->getQueue(), $builder->isPassive(), $builder->isDurable(), $builder->isExclusive(), $builder->isAutoDelete(), $builder->isNowait(), $builder->getArguments(), $builder->getTicket());

        $routineKeys = (array) $message->getRoutingKey();
        foreach ($routineKeys as $routingKey) {
            $channel->queue_bind($message->getQueue(), $message->getExchange(), $routingKey);
        }

        if (empty($routineKeys) && $message->getType() === Type::FANOUT) {
            $channel->queue_bind($message->getQueue(), $message->getExchange());
        }

        if (is_array($qos = $message->getQos())) {
            $size = $qos['prefetch_size'] ?? null;
            $count = $qos['prefetch_count'] ?? null;
            $global = $qos['global'] ?? null;
            $channel->basic_qos($size, $count, $global);
        }
    }
}
