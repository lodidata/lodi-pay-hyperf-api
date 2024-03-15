<?php

namespace App\Service\Pay;

use Hyperf\DbConnection\Db;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Di\Annotation\Inject;

class BPayService extends AbstractPayService
{
    protected $payType = 'bpay';

    public function apply($merchant_account,$total_amount, $order_sn, $user_account, $orders_pay_sn, $is_split, $is_daifu = 0): void
    {
        $config = $this->getConfig($merchant_account);
        $data = [
            'merchantNo' => $config['partner_id'],
            'merchantOrderNo' => $order_sn,
            'paymentAmount' => $total_amount,
            'countryCode' => '',
            'currencyCode' => '',
            'paymentType' => '',
            'feeDeduction' => '1',
            'remark' => 'transfer',
            'extendedParams' => '',
            'notifyUrl' => $config['pay_callback_domain']."/api/{$this->payType}/{$merchant_account}/callback",
        ];
        $config_params = !empty($config['params']) ? json_decode($config['params'],true) : [];
        if(!empty($config_params) && isset($config_params['countryCode'])){
            $data['countryCode'] = $config_params['countryCode'];
        }
        if(!empty($config_params) && isset($config_params['currencyCode'])){
            $data['currencyCode'] = $config_params['currencyCode'];
        }
        if(!empty($config_params) && isset($config_params['paymentType'])){
            $data['paymentType'] = $config_params['paymentType'];
        }
        if($data['countryCode'] == 'PHL'){
            $bank = $this->PHLBabnkName();
            $params['extendedParams'] = "bankAccount^{$user_account}|bankCode^{$bank['bankCode']}";
            $params['transferType']   = $bank['transferType'];

        }
        $data['sign'] = $this->sign($data, $config['key']);
        $pay_url = $config['payurl'] . '/transfer/order/create';

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

    private function PHLBabnkName(){
        $banks=[
            'Gcash'=>[
                'bankCode'=>'GCASH',
                'transferType'=>902410175001
            ],
            'Paymaya Philippines, Inc.'=>[
                'bankCode'=>'PAYMAYA',
                'transferType'=>902410175002
            ]
        ];
        return $banks['Gcash'];
    }

    //验证回调签名
    public function sign($data, $secret_key)
    {
        $str = '';
        ksort($data);

        foreach($data as $k => $v) {
            $str .= $k . "=" . $v . "&";
        }
        $str    = rtrim($str, '&');
        $prikey = "-----BEGIN RSA PRIVATE KEY-----\n" . wordwrap($this->key, 64, "\n", true) . "\n-----END RSA PRIVATE KEY-----";
        $key    = openssl_get_privatekey($prikey);
        openssl_sign($str, $sign, $key, OPENSSL_ALGO_MD5);
        openssl_free_key($key);
        return base64_encode($sign);
    }


    //验证回调签名
    public function verifySign($data, $secret_key): bool
    {
        $sign = base64_decode($data['sign']);
        unset($data['sign']);
        ksort($data);

        $str = '';
        foreach($data as $k => $v) {
            $str .= $k . "=" . $v . "&";
        }
        $str    = rtrim($str, '&');
        $pubkey = "-----BEGIN PUBLIC KEY-----\n".wordwrap($this->pubKey, 65, "\n", true)."\n-----END PUBLIC KEY-----";
        $key = openssl_get_publickey($pubkey);
        if(openssl_verify($str, $sign, $key, OPENSSL_ALGO_MD5)){
            return true;
        }
        return false;
    }
}