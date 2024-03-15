<?php

namespace App\Service\Pay;

use Hyperf\DbConnection\Db;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Di\Annotation\Inject;

class LPayService extends AbstractPayService
{
    protected $payType = 'lpay';

    public function apply($merchant_account,$total_amount, $order_sn, $user_account, $orders_pay_sn, $is_split, $is_daifu = 0): void
    {
        $config = $this->getConfig($merchant_account);
        $params = [
            'merchant_ref' => $order_sn,
            'product' => 'PayloroPayout',
            'amount'  => $total_amount,
            'extra' => [
                'account_name' => $user_account,
                'account_no' => $user_account,
                'bank_code' => 'PH_GCASH',
            ]
        ];
        $data = [
            'merchant_no' => $config['partner_id'],
            'timestamp' => time(),
            'sign_type' => 'MD5',
            'params' => json_encode($params),
        ];

        $data['sign'] = $this->sign($data, $config['key']);
        $pay_url = $config['payurl'] . '/api/gateway/withdraw';

        $is_fail = false;
        try {
            $client = di()->get(ClientFactory::class)->create(['timeout' => 2]);
            $response = $client->post($pay_url, ['form_params' => $data]);

            $http_code = $response->getStatusCode();
            if ($http_code == 201) {
                $res_obj = $response->getBody()->getContents();
                $result = json_decode($res_obj, true);
                if ($result['code'] != 200) {
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

    protected function sign($data, $secret_key)
    {
        ksort($data);
        $str = '';
        foreach ($data as $v) {
            $str .= $v;
        }
        $sign_str = $str. $secret_key;
        $sign = md5($sign_str);
        return $sign;
    }

    //验证回调签名
    public function verifySign($data, $secret_key): bool
    {
        if (!isset($data['sign'])) return false;

        $sign = $data['sign'];
        unset($data['sign']);
        ksort($data);

        $str = '';
        foreach ($data as $v) {
            $str .= $v;
        }
        $sign_str = $str. $secret_key;
        $sign_new = md5($sign_str);
        if ($sign === $sign_new) {
            return true;
        }
        return false;
    }

}