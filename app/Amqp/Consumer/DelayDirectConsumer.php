<?php

declare(strict_types=1);

namespace App\Amqp\Consumer;

use App\Service\CollectionOrderService;
use Hyperf\Amqp\Message\Type;
use Hyperf\Amqp\Result;
use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\DbConnection\Db;
use PhpAmqpLib\Message\AMQPMessage;
use Hyperf\Amqp\Message\ConsumerDelayedMessageTrait;
use Hyperf\Amqp\Message\ProducerDelayedMessageTrait;
use Hyperf\Di\Annotation\Inject;

/**
 * @Consumer(exchange="exchange.lodi.delay", routingKey="lodi.delay", queue="queue.lodi.delay", name="DelayDirectConsumer", nums=10)
 */
class DelayDirectConsumer extends ConsumerMessage
{
    use ProducerDelayedMessageTrait;
    use ConsumerDelayedMessageTrait;

    protected $type = Type::DIRECT;

    /**
     * @Inject()
     * @var CollectionOrderService
     */
    protected $collectionOrderService;

    public function consumeMessage($data, AMQPMessage $message): string
    {
        try {
            if (isset($data['event']) && isset($data['orders_collection_sn'])) {
                $orders_collection_sn = $data['orders_collection_sn'];
                switch ($data['event']){
                    case 'upload_cert'://检查确认收款
                        $order = Db::table('orders_collection')->where(['inner_order_sn'=>$orders_collection_sn])->first(['status']);
                        if ($order && $order->status == 4) {//确认超时
                            $this->updateOrderStatus($orders_collection_sn,4,5);
                            //标记争议
                            $this->collectionOrderService->notReceived($orders_collection_sn);
                        }
                        break;
                    case 'match_success'://检查上传凭证
                        $order = Db::table('orders_collection')->where(['inner_order_sn'=>$orders_collection_sn])->first(['status']);
                        if ($order && $order->status == 2) {//上传凭证超时
                            $this->updateOrderStatus($orders_collection_sn,2,3);
                        }
                        break;
                    default:
                }
            }
            return Result::ACK;
        } catch (\Throwable $e){
            logger()->error('超时检测消费失败:'.$e->getMessage(),[$data]);
            return Result::DROP;
        }
    }

    private function updateOrderStatus($orders_collection_sn,$old_status,$new_status)
    {
        $updated_at = date('Y-m-d H:i:s');
        Db::table('orders_collection')->where(['inner_order_sn' => $orders_collection_sn, 'status' => $old_status])->update(['status' => $new_status, 'updated_at' => $updated_at]);
    }
}
