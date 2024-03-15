<?php

namespace App\Service\Pay;

use Hyperf\DbConnection\Db;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Di\Annotation\Inject;

class ApePayService extends AbstractPayService
{
    protected $payType = 'apepay';

    public function apply($merchant_account,$total_amount, $order_sn, $user_account, $orders_pay_sn, $is_split, $is_daifu = 0): void
    {
        $config = $this->getConfig($merchant_account);
        $data = [
            'user' => $config['partner_id'],
            'orderId' => $order_sn,
            'amount' => $total_amount,
            'account' => $user_account,
            'name' => $user_account,
            "accountMark"    => "GCASH",
            "accountType"    => 2,//账户类型,1代表银行卡,2GCASH,3代表微信
            'noticeUrl' => $config['pay_callback_domain'] . "/api/{$this->payType}/{$merchant_account}/callback",
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

    public function sign($data, $secret_key)
    {
        if (isset($data['sign'])) {
            unset($data['sign']);
        }
        ksort($data);

        $str = '';
        foreach($data as $k => $v) {
            if(is_null($v) || $v === '') continue;
            $str .= $k . '=' . $v . '&';
        }
        $sign_str = $str . 'token=' . $secret_key;

        return strtoupper(md5($sign_str));
    }

    public function verifySign($data, $secret_key): bool
    {
        if (!isset($data['sign'])) return false;

        $sign = $data['sign'];
        unset($data['sign']);
        ksort($data);

        $str = '';
        foreach ($data as $k => $v) {
            if (is_null($v) || $v === '') continue;
            $str .= $k . '=' . $v . '&';
        }
        $sign_str = $str . 'token=' . $secret_key;
        $sign_new = strtoupper(md5($sign_str));
        if ($sign === $sign_new) {
            return true;
        }
        return false;
    }
}