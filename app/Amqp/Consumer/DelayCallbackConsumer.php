<?php

declare(strict_types=1);

namespace App\Amqp\Consumer;

use App\Amqp\Producer\DelayCallbackProducer;
use App\Exception\ServiceException;
use Hyperf\Amqp\Message\Type;
use Hyperf\Amqp\Producer;
use Hyperf\Amqp\Result;
use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Guzzle\ClientFactory;
use PhpAmqpLib\Message\AMQPMessage;
use Hyperf\Amqp\Message\ConsumerDelayedMessageTrait;
use Hyperf\Amqp\Message\ProducerDelayedMessageTrait;

/**
 * @Consumer(exchange="exchange.lodi.delay.callback", routingKey="lodi.delay.callback", queue="queue.lodi.delay.callback", name="DelayCallbackConsumer", nums=10)
 */
class DelayCallbackConsumer extends ConsumerMessage
{
    use ProducerDelayedMessageTrait;
    use ConsumerDelayedMessageTrait;

    protected $type = Type::DIRECT;

    public function consumeMessage($data, AMQPMessage $message): string
    {
        if (isset($data['retry_num']) && isset($data['params']) && isset($data['url'])) {
            try{
                $this->callback($data['retry_num'],$data['params'],$data['url']);
            } catch (\Throwable $e) {
                logger()->error('回调错误',[$data['retry_num'],$data['params'],$data['url']]);
            }
            return Result::ACK;
        }
        return Result::DROP;
    }

    private function callback(int $retry_num, $params, $url)
    {
        try{
            $client = di()->get(ClientFactory::class)->create(['timeout'=>2]);
            $response = $client->post($url, ['json' => $params]);
            if ($response->getStatusCode() == 200) {
                $res_obj = $response->getBody()->getContents();
                if ($res_obj !== 'SUCCESS') {
                    throw new ServiceException('请求异常');
                }
            } else {
                throw new ServiceException('请求异常');
            }
        } catch (\Exception $e) {
            $delay_ms = $this->getDelayTime($retry_num);
            if ($delay_ms) {
                //延迟投递
                ++$retry_num;
                $delay_message = new DelayCallbackProducer(['retry_num' => $retry_num, 'params' => $params, 'url' => $url]);

                $delay_message->setDelayMs($delay_ms);
                $producer = di()->get(Producer::class);
                $producer->produce($delay_message);
            }
        }
    }

    private function getDelayTime($retry_num): int
    {
        $time = [
            '1' => 15000,
            '2' => 30000,
            '3' => 180000,
            '4' => 300000,
        ];
        if (isset($time[$retry_num])) {
            return $time[$retry_num];
        }
        return 0;
    }
}
