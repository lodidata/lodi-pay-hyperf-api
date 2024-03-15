<?php

namespace App\Amqp\Consumer;

use App\Amqp\Producer\DelayCallbackProducer;
use App\Exception\ServiceException;
use App\Helper\SignHelper;
use App\Service\MerchantService;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\Type;
use Hyperf\Amqp\Producer;
use Hyperf\Amqp\Result;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Guzzle\ClientFactory;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * @Consumer(exchange="exchange.lodi.callback", routingKey="lodi.callback", queue="queue.lodi.callback", name="CallbackConsumer", nums=20)
 */
class CallbackConsumer extends ConsumerMessage
{
    protected $type = Type::DIRECT;

    /**
     * @Inject()
     * @var MerchantService
     */
    protected $merchantService;

    public function consumeMessage($data, AMQPMessage $message): string
    {
        try {
            if (empty($data) || !isset($data['event']) || !isset($data['inner_order_sn'])) {
                throw new ServiceException('Callback data is invalid');
            }
            $event = $data['event'];
            if ($event === 'pay_reject') {//提现驳回
                $pay_inner_order_sn = (string)$data['inner_order_sn'];
                $pay_order = (array)Db::table('orders_pay')->where('inner_order_sn', $pay_inner_order_sn)
                    ->first(['amount', 'order_sn', 'pay_status', 'callback_url', 'merchant_id']);
                $this->rejectCallbackWithdraw($pay_inner_order_sn, $pay_order);
            } else {
                $collection_inner_order_sn = (string)$data['inner_order_sn'];
                $collection_order = (array)Db::table('orders_collection')->where('inner_order_sn', $collection_inner_order_sn)
                    ->first(['pay_inner_order_sn', 'order_sn', 'amount', 'currency', 'order_status', 'status', 'order_type', 'created_at', 'updated_at', 'callback_url', 'user_account', 'merchant_id']);
                if (empty($collection_order)) throw new ServiceException('orders_collection table record does not exist');
                $pay_inner_order_sn = (string)$collection_order['pay_inner_order_sn'];
                $pay_order = (array)Db::table('orders_pay')->where('inner_order_sn', $pay_inner_order_sn)
                    ->first(['amount', 'order_sn', 'pay_status', 'callback_url', 'merchant_id']);
                if (empty($pay_order)) throw new ServiceException('orders_pay table record does not exist');

                switch ($event) {
                    case 'match_success':
                        $this->callbackWithdraw($collection_order, $collection_inner_order_sn, $pay_order);
                        break;
                    case 'third_pay':
                        $this->callbackWithdraw($collection_order, $collection_inner_order_sn, $pay_order,1);
                        break;
                    case 'upload_cert':
                    case 'confirm_payment':
                    case 'recharge_fail':
                    case 'cancel_recharge':
                        $this->callbackRecharge($collection_order, $collection_inner_order_sn);
                        $this->callbackWithdraw($collection_order, $collection_inner_order_sn, $pay_order);
                        break;
                    default:
                }
            }

        } catch (\Throwable $e) {
            logger()->error('回调消费失败', [$e->getMessage(), $data]);
            return Result::DROP;
        }
        return Result::ACK;
    }

    protected function callbackRecharge($collection_order, $collection_inner_order_sn)
    {
        $params = [
            'mer_no' => $collection_inner_order_sn,
            'amount' => $collection_order['amount'],
            'order_sn' => $collection_order['order_sn'],
            'order_status' => $collection_order['order_status'],
            'status' => $collection_order['status']
        ];
        $merchant = $this->merchantService->getMerchantSecret($collection_order['merchant_id']);
        if (empty($merchant)) throw new ServiceException('Merchant does not exist');
        $secret_key = $merchant['secret_key'];

        $params['sign'] = SignHelper::sign($params, $secret_key);
        $url = $collection_order['callback_url'];

        $this->callbackRequest($params, $url);
    }

    protected function callbackWithdraw($collection_order, $collection_inner_order_sn, $pay_order,$is_third_pay = 0)
    {
        if ($is_third_pay == 1) {
            if ($pay_order['pay_status'] == 'fail' || $collection_order['order_status'] == 'fail'){
                return;
            }
        }
        $subOrderData[] = [
            'mer_no' => $collection_inner_order_sn,   //平台单号
            'amount' => $collection_order['amount'],  //交易金额
            'currency' => $collection_order['currency'],
            'order_status' => $collection_order['order_status'], //平台订单状态
            'status' => $collection_order['status'],
            'order_type' => $collection_order['order_type'],
            'user_account' => $collection_order['user_account'],
            'create_time' => $collection_order['created_at'],
            'update_time' => $collection_order['updated_at'],
        ];
        $params = [
            'mer_no' => $collection_order['pay_inner_order_sn'],
            'amount' => $pay_order['amount'],
            'order_sn' => $pay_order['order_sn'],
            'order_status' => $pay_order['pay_status'],
            'info' => json_encode($subOrderData),
        ];

        $merchant = $this->merchantService->getMerchantSecret($pay_order['merchant_id']);
        if (empty($merchant)) throw new ServiceException('Merchant does not exist');
        $secret_key = $merchant['secret_key'];

        $params['sign'] = SignHelper::sign($params, $secret_key);
        $url = $pay_order['callback_url'];

        $this->callbackRequest($params, $url);
    }

    protected function  rejectCallbackWithdraw($pay_inner_order_sn,$pay_order)
    {
        $params = [
            'mer_no' => $pay_inner_order_sn,
            'amount' => $pay_order['amount'],
            'order_sn' => $pay_order['order_sn'],
            'order_status' => $pay_order['pay_status'],
            'info' => json_encode([]),
        ];

        $merchant = $this->merchantService->getMerchantSecret($pay_order['merchant_id']);
        if (empty($merchant)) throw new ServiceException('Merchant does not exist');
        $secret_key = $merchant['secret_key'];

        $params['sign'] = SignHelper::sign($params, $secret_key);
        $url = $pay_order['callback_url'];

        $this->callbackRequest($params, $url);
    }

    protected function callbackRequest($params, $url)
    {
        try {
            $client = di()->get(ClientFactory::class)->create(['timeout' => 2]);
            $response = $client->post($url, ['json' => $params]);

            $response_code = $response->getStatusCode();
            $response_content = $response->getBody()->getContents();
        } catch (\Throwable $e) {
            $response_code = $e->getCode();
            $response_content = $e->getMessage();
        }

        if ($response_code != 200 || $response_content != 'SUCCESS') {
            //投递延迟 15s
            $delay_message = new DelayCallbackProducer(['retry_num' => 1, 'params' => $params, 'url' => $url]);
            $delay_message->setDelayMs(15000);
            $producer = di()->get(Producer::class);
            $producer->produce($delay_message);

            logger()->error('商户接口响应', [
                'params' => $params,
                'url' => $url,
                'response_code' => $response_code,
                'response_content' => $response_content
            ]);
        }

    }
}