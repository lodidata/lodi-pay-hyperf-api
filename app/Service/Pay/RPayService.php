<?php

namespace App\Service\Pay;

use App\Amqp\Producer\CallbackProducer;
use Hyperf\Amqp\Producer;
use Hyperf\DbConnection\Db;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Di\Annotation\Inject;

class RPayService extends AbstractPayService
{
    protected $payType = 'rpay';

    public function apply($merchant_account,$total_amount, $order_sn, $user_account, $orders_pay_sn, $is_split, $is_daifu = 0): void
    {
        $config = $this->getConfig($merchant_account);
        $data = [
            'merchant' => $config['partner_id'],
            'total_amount' => $total_amount,
            'order_id' => $order_sn,
            'bank' => 'gcash',
            'bank_card_name' => $user_account,
            'bank_card_account' => $user_account,
            'bank_card_remark' => 'no',
            'callback_url' => $config['pay_callback_domain']."/api/{$this->payType}/{$merchant_account}/callback",
        ];
        $data['sign'] = $this->sign($data, $config['key']);
        $pay_url = $config['payurl'] . '/api/daifu';

        $is_fail = false;
        try {
            $client = di()->get(ClientFactory::class)->create(['timeout' => 2]);
            $response = $client->post($pay_url, ['json' => $data]);

            $http_code = $response->getStatusCode();
            if ($http_code == 200) {
                $res_obj = $response->getBody()->getContents();
                $result = json_decode($res_obj, true);
                if ($result['code'] !== 0) {
                    $is_fail = true;
                    $pay_response = $res_obj;
                }
            } else {
                $is_fail = true;
                $pay_response = 'http_code:'.$http_code;
            }
        } catch (\Throwable $e) {
            $is_fail = true;
            $pay_response = $e->getMessage();
        }
        if ($is_fail) {
            $this->updatePayOrder($order_sn, $orders_pay_sn, $is_split, $is_daifu,'fail');
            Db::table('pay_log')->insert([
                'order_id' => $order_sn,
                'payUrl' => $pay_url,
                'pay_type' => $this->payType,
                'json' => json_encode($data),
                'response' => strlen($pay_response) > 500 ? substr($pay_response,0,500):$pay_response,
            ]);
        }

    }

    public function me($merchant_account): array
    {
        $config = $this->getConfig($merchant_account);
        $data = [
            'merchant' => $config['partner_id'],
        ];
        $data['sign'] = $this->sign($data, $config['key']);
        $client = di()->get(ClientFactory::class)->create([]);
        $pay_url = $config['payurl'] . '/api/me';
        $res = $client->post($pay_url, ['form_params' => $data]);
        return json_decode($res->getBody()->getContents(), true);
    }
}