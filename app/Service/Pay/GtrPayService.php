<?php

namespace App\Service\Pay;

use Hyperf\DbConnection\Db;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Di\Annotation\Inject;

class GtrPayService extends AbstractPayService
{
    protected $payType = 'gtrpay';

    public function apply($merchant_account,$total_amount, $order_sn, $user_account, $orders_pay_sn, $is_split, $is_daifu = 0): void
    {
        $config = $this->getConfig($merchant_account);
        $data = [
            'mchId' => $config['partner_id'],
            'passageId' => $this->getBankName(),
            'orderNo' => $order_sn,
            'account'      => $user_account,
            'userName'     => $user_account,
            'orderAmount'  => $total_amount,
            'notifyUrl' => $config['pay_callback_domain']."/api/{$this->payType}/{$merchant_account}/callback",
        ];

        $data['sign'] = $this->sign($data, $config['key']);
        $pay_url = $config['payurl'] . '/pay/create';

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

    private function getBankName()
    {
        $banks = [
            'Gcash'  => 16411
        ];
        return $banks['Gcash'];
    }
}