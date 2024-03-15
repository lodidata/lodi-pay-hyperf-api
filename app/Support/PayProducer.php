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

use Hyperf\Amqp\Message\ProducerMessageInterface;
use Hyperf\Di\Annotation\AnnotationCollector;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Hyperf\Amqp\Builder;
use Hyperf\Amqp\Annotation;
use Throwable;

class PayProducer extends Builder
{
    public function produce(ProducerMessageInterface $producerMessage, bool $confirm = false, int $timeout = 5): bool
    {
        return \retry(1, function () use ($producerMessage, $confirm, $timeout) {
            return $this->produceMessage($producerMessage, $confirm, $timeout);
        });
    }

    private function produceMessage(ProducerMessageInterface $producerMessage, bool $confirm = false, int $timeout = 5)
    {
        $result = false;

        $this->injectMessageProperty($producerMessage);

        $message = new AMQPMessage($producerMessage->payload(), $producerMessage->getProperties());
        $connection = $this->factory->getConnection($producerMessage->getPoolName());

        try {
            if ($confirm) {
                $channel = $connection->getConfirmChannel();
            } else {
                $channel = $connection->getChannel();
            }
            $channel->set_ack_handler(function () use (&$result) {
                $result = true;
            });

            $builder = $producerMessage->getExchangeBuilder();
            //声明交换机
            $channel->exchange_declare($builder->getExchange(), $builder->getType(), $builder->isPassive(), $builder->isDurable(), $builder->isAutoDelete(), $builder->isInternal(), $builder->isNowait(), $builder->getArguments(), $builder->getTicket());
            //声明队列
            $queue_name = 'queue.'.$producerMessage->getRoutingKey();
            $channel->queue_declare($queue_name,false,true,false,false,false,new AMQPTable([
                // 消息过期时间
                'x-message-ttl' => 7200000,
                // 死信交换机
                'x-dead-letter-exchange' => 'dead.exc.lodi.pay',
                // 死信路由键
                'x-dead-letter-routing-key' => 'dead.lodi.pay'
            ]));
            $channel->queue_bind($queue_name, $producerMessage->getExchange(), $producerMessage->getRoutingKey());
            $channel->basic_publish($message, $producerMessage->getExchange(), $producerMessage->getRoutingKey());
            $channel->wait_for_pending_acks_returns($timeout);
        } catch (\Throwable $exception) {
            isset($channel) && $channel->close();
            throw $exception;
        }

        if ($confirm) {
            $connection->releaseChannel($channel, true);
        } else {
            $result = true;
            $connection->releaseChannel($channel);
        }

        return $result;
    }

    private function injectMessageProperty(ProducerMessageInterface $producerMessage)
    {
        if (class_exists(AnnotationCollector::class)) {
            /** @var null|\Hyperf\Amqp\Annotation\Producer $annotation */
            $annotation = AnnotationCollector::getClassAnnotation(get_class($producerMessage), Annotation\Producer::class);
            if ($annotation) {
                $annotation->routingKey && $producerMessage->setRoutingKey($annotation->routingKey);
                $annotation->exchange && $producerMessage->setExchange($annotation->exchange);
            }
        }
    }
}
