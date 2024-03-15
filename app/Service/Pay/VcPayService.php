<?php

namespace App\Service\Pay;

use Hyperf\DbConnection\Db;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Di\Annotation\Inject;

class VcPayService extends AbstractPayService
{
    protected $payType = 'vcpay';

    public function apply($merchant_account,$total_amount, $order_sn, $user_account, $orders_pay_sn, $is_split, $is_daifu = 0): void
    {
        $config = $this->getConfig($merchant_account);
        $data = [
            'app_id' => $config['partner_id'],
            'nonce_str' => randStr(2,'abcdefghijklmnopqrstuvwxyz').randStr(12,'0123456789'),
            'trade_type'    => 'PHT001',
            'order_amount'  => $total_amount,
            'out_trade_no' => $order_sn,
            'bank_code'     => '000000',
            'bank_owner'    => 'NONE',
            'bank_account'  => 'NONE',
            'identity_type' => 'PHONE',
            'identity'      => $user_account,
            'notify_url' => $config['pay_callback_domain']."/api/{$this->payType}/{$merchant_account}/callback",
        ];

        $data['sign'] = $this->sign($data, $config['key']);
        $pay_url = $config['payurl'] . '/wd/save';

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

    protected function sign($data, $secret_key)
    {
        if (isset($data['sign'])) {
            unset($data['sign']);
        }
        ksort($data);
        reset($data);

        $str = '';
        foreach ($data as $k => $v) {
            if (is_null($v) || $v === '') continue;
            $str .= $k . '=' . $v . '&';
        }
        $str = trim($str, '&');

        $sign_str = $str . '&key=' . $secret_key;
        return strtoupper(md5($sign_str));
    }

    //验证回调签名
    public function verifySign($data, $secret_key): bool
    {
        if (!isset($data['sign'])) return false;

        $sign = $data['sign'];
        unset($data['sign']);
        ksort($data);
        reset($data);

        $str = '';
        foreach ($data as $k => $v) {
            if (is_null($v) || $v === '') continue;
            $str .= $k . '=' . $v . '&';
        }
        $str = trim($str, '&');

        $sign_str = $str . '&key=' . $secret_key;
        $sign_new = strtoupper(md5($sign_str));

        if ($sign === $sign_new) {
            return true;
        }
        return false;
    }
}