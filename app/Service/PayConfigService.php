<?php

namespace App\Service;

use App\Cache\Repository\LockRedis;
use Hyperf\DbConnection\Db;
use Swoole\Coroutine;

class PayConfigService
{
    public function getConfig($merchant_account, string $type = '')
    {
        $cache_key = 'pay_config:' . $merchant_account;
        if ($cache_data = redis()->get($cache_key)) {
            $data = json_decode($cache_data, true);
        } else {
            if (LockRedis::getInstance()->lock($cache_key, 20)) {
                try {
                    $data = Db::table('pay_config')->where(['merchant_account' => (string)$merchant_account, 'status' => 'enabled'])
                        ->orderByDesc('sort')->get(['name', 'type', 'partner_id', 'pay_callback_domain', 'key', 'pub_key', 'payurl', 'params'])->toArray();
                    redis()->setex($cache_key, 300, json_encode($data));
                } finally {
                    LockRedis::getInstance()->delete($cache_key);
                }
            } else {
                if ($cache_data = redis()->get($cache_key)) {
                    $data = json_decode($cache_data, true);
                } else {
                    Coroutine::sleep(0.1);
                    return $this->getConfig($merchant_account, $type);
                }
            }
        }

        if (empty($type)) {
            return isset($data[0]) ? (array)$data[0] : [];
        } else {
            foreach ($data as $item) {
                !is_array($item) && $item = (array)$item;
                if ($item['type'] === $type) {
                    return $item;
                }
            }
            return [];
        }
    }
}