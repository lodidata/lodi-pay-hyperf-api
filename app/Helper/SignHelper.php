<?php
declare(strict_types=1);

namespace App\Helper;

class SignHelper
{
    /**
     * 签名
     * @param array $data
     * @param  $private_key
     * @return string
     */
    public static function sign(array $data, $private_key): string
    {
        if (isset($data['sign'])){
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

        $prikey = "-----BEGIN PRIVATE KEY-----\n" . wordwrap($private_key, 64, "\n", true) . "\n-----END PRIVATE KEY-----";
        $key = openssl_get_privatekey($prikey);
        openssl_sign($str, $sign, $key, OPENSSL_ALGO_SHA256);

        $sign = base64_encode($sign);
        return $sign;
    }

    /**
     * 验证签名
     * @param array $data
     * @param string $public_key
     * @return bool
     */
    public static function signVerify(array $data, string $public_key): bool
    {
        if (isset($data['sign']) && !empty($data['sign'])) {
            $sign = base64_decode($data['sign']);
            unset($data['sign']);
            ksort($data);
            reset($data);

            $str = '';
            foreach ($data as $k => $v) {
                if (is_null($v) || $v === '') continue;
                $str .= $k . '=' . $v . '&';
            }
            $str = trim($str, '&');

            $pay_public_key = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($public_key, 64, "\n", true) . "\n-----END PUBLIC KEY-----";

            $key = openssl_get_publickey($pay_public_key);
            if (openssl_verify($str, $sign, $key, OPENSSL_ALGO_SHA256) === 1) {
                return true;
            }
        }
        return false;
    }
}
