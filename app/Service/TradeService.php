<?php

namespace App\Service;

use App\Amqp\Consumer\PayMatchConsumer;
use App\Amqp\Consumer\PayMatchSplitConsumer;
use App\Amqp\Producer\DeadLetterPaySplitProducer;
use App\Amqp\Producer\PayMatchSplitProducer;
use App\Constant\ResponseCode;
use App\Exception\ServiceException;
use App\Model\Admin\AdminConfig;
use App\Support\PayConsumer;
use App\Support\PaySplitProducer;
use Hyperf\Amqp\Message\Type;
use Hyperf\Amqp\Producer;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class TradeService
{
    /**
     * @Inject()
     * @var AdminConfigService
     */
    protected $adminConfigService;
    /**
     * 充值匹配
     * @param $inner_order_sn
     * @param $amount
     * @param $currency
     * @return mixed
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Throwable
     */
    public function RechargeMatch($inner_order_sn, $amount, $currency)
    {
        //获取订单金额配置

        $config_orders = $this->adminConfigService->getConfig(['order_threshold', 'is_system_pay']);
        $min_amount = $config_orders['order_threshold'];//最小拆单金额
        $is_system_pay = $config_orders['is_system_pay'] ?? 0;

        $queue_arr = redis()->zRangeByScore('lodipay:' . $currency, '1', '+inf');
        if (empty($queue_arr)) {
            throw new ServiceException('No matching orders 1', ResponseCode::FAIL);
        }
        sort($queue_arr);

        //需要匹配的拆单队列
        $split_order_all_queue = [];
        $start = (int)$amount;
        $end = $start * 2;
        if ($is_system_pay == 1) {
            for ($i = $start; $i <= $end; $i += 100) {
                $split_order_all_queue[] = 'queue.lodi.internal.pay.default.' . $currency . '.' . $i;
            }
        } else {
            $split_order_all_queue[] = 'queue.lodi.internal.pay.default.' . $currency . '.' . $start;
        }

        $consumer = di()->get(PayConsumer::class);
        $split_order_queue = array_intersect($split_order_all_queue, $queue_arr);

        $is_match_split = false;
        if (!empty($split_order_queue)) {
            $consumerMessage = make(PayMatchSplitConsumer::class);
            foreach ($split_order_queue as $split_queue) {
                $consumerMessage->setRoutingKey(substr($split_queue, 6));
                $consumerMessage->setQueue($split_queue);
                list($connection, $channel) = $consumer->getChannel($consumerMessage);
                $msg_obj = $channel->basic_get($consumerMessage->getQueue());
                if ($msg_obj) {
                    $msg_data = json_decode($msg_obj->body, true);

                    var_dump('拆单', $msg_data);
                    if (!isset($msg_data['inner_order_sn']) || !isset($msg_data['amount']) || !isset($msg_data['user_account'])) {
                        $msg_obj->reject(false);
                        $consumer->putChannel($connection, $channel);
                        payNumDecr('lodipay:' . $currency, $split_queue);
                        continue;
                    }
                    try {
                        $this->updateData($msg_data, $amount, $inner_order_sn, $currency,1);
                    } catch (\Exception $e) {
                        if ($e->getCode() == -2) {//入库失败
                            $consumer->putChannel($connection, $channel);
                            continue;
                        }
                    }
                    $msg_obj->ack();
                    $consumer->putChannel($connection, $channel);
                    payNumDecr('lodipay:' . $currency, $split_queue);
                    $user_account = $msg_data['user_account'];
                    $pay_inner_order_sn = $msg_data['inner_order_sn'];
                    $is_match_split = true;
                    break;
                }
            }
        }

        if ($is_match_split === false) {//拆单没有匹配到
            $order_all_queue = [];
            if ($start < $min_amount) {//不能拆单
                $order_all_queue[] = 'queue.lodi.internal.pay.' . $currency . '.' . $start;
                for ($i = $min_amount; $i <= $end; $i += 100) {
                    $order_all_queue[] = 'queue.lodi.internal.pay.' . $currency . '.' . $i;
                }
            } else {//可以拆单
                for ($i = $start; $i <= $end; $i += 100) {
                    $order_all_queue[] = 'queue.lodi.internal.pay.' . $currency . '.' . $i;
                }
            }

            $order_queue = array_intersect($order_all_queue, $queue_arr);
            if (empty($order_queue)) {
                throw new ServiceException('No matching orders 2', ResponseCode::FAIL);
            }
            $consumerMessage = make(PayMatchConsumer::class);
            foreach ($order_queue as $queue) {
                $consumerMessage->setRoutingKey(substr($queue, 6));
                $consumerMessage->setQueue($queue);
                list($connection, $channel) = $consumer->getChannel($consumerMessage);
                $msg_obj = $channel->basic_get($consumerMessage->getQueue());
                if ($msg_obj) {
                    $msg_data = json_decode($msg_obj->body, true);
                    var_dump('未拆单', $msg_data);
                    if (!isset($msg_data['inner_order_sn']) || !isset($msg_data['amount']) || !isset($msg_data['user_account'])) {
                        $msg_obj->reject(false);
                        $consumer->putChannel($connection, $channel);
                        payNumDecr('lodipay:' . $currency, $queue);
                        continue;
                    }
                    try {
                        $this->updateData($msg_data, $amount, $inner_order_sn, $currency);
                    } catch (\Exception $e) {
                        if ($e->getCode() == -2) {//入库失败
                            $consumer->putChannel($connection, $channel);
                            continue;
                        }
                    }

                    $msg_obj->ack();
                    $consumer->putChannel($connection, $channel);
                    payNumDecr('lodipay:' . $currency, $queue);
                    $user_account = $msg_data['user_account'];
                    $pay_inner_order_sn = $msg_data['inner_order_sn'];
                    break;
                }
            }
        }

        if (isset($pay_inner_order_sn)) {
            return [$pay_inner_order_sn];
        }
        throw new ServiceException('No matching orders 3', ResponseCode::FAIL);
    }

    protected function updateData(array $msg_data, $collection_amount, $collection_order_sn, $currency,$is_split=0)
    {
        $pay_amount = $msg_data['amount'];
        $pay_inner_order_sn = $msg_data['inner_order_sn'];
        $orders_pay_balance = bcsub((string)$pay_amount, (string)$collection_amount, 2);

        if ($orders_pay_balance < 0) {
            logger()->error('充值匹配订单余额异常', [$msg_data, $collection_order_sn]);
            throw new \Exception('充值匹配订单余额异常', -1);
        }

        try {
            $update_res = Db::table('orders_pay')->where(['inner_order_sn' => $pay_inner_order_sn])->whereIn('status', [1, 2])->update([
                'status' => 8,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            if (!$update_res) {
                logger()->error('充值匹配更新数据影响行数:'.$update_res, [$msg_data, $collection_order_sn]);
            }
        } catch (\Throwable $e) {
            logger()->error('充值匹配更新数据失败'.$e->getMessage(), [$msg_data, $collection_order_sn]);
            throw new \Exception('充值匹配更新数据库失败', -2);
        }

        if ($orders_pay_balance > 0) {
            $params = [
                'inner_order_sn' => $msg_data['inner_order_sn'],
                'amount' => $orders_pay_balance,
                'user_account' => $msg_data['user_account'],
                'currency' => $currency
            ];

            try {
                if (!$is_split) {
                    //拆单
                    $message = new PayMatchSplitProducer($params);
                    $routing_keys = 'lodi.internal.pay.default.' . $currency . '.' . (int)$orders_pay_balance;
                    $message->setRoutingKey($routing_keys);
                    $message->setType(Type::DIRECT);
                    $message->setTtlMs(600000);
                    $producer = di()->get(PaySplitProducer::class);
                } else {
                    //兜底
                    $message = new DeadLetterPaySplitProducer($params);
                    $producer = di()->get(Producer::class);
                }

                $res = $producer->produce($message);
                logger()->info('充值匹配投递mq', [$res, $message->getExchange(), $message->getRoutingKey()]);
                if ($res && strpos($message->getRoutingKey(),'lodi.internal.pay.default.') !== false) {
                    payNumIncr('lodipay:' . $currency, 'queue.' . $message->getRoutingKey());
                }
            } catch (\Exception $e) {
                logger()->error("充值匹配投递错误：", [$e->getMessage(), $params]);
                throw new \Exception('充值匹配投递拆单失败', -3);
            }
        }
    }


}