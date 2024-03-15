<?php
declare(strict_types=1);

namespace App\Helper;

class BackendSignHelper
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

        $sign = md5(md5($str).$private_key);
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

            $new_sign = md5(md5($str).$public_key);
            if ($sign == $new_sign) {
                return true;
            }
        }
        return false;
    }
}
