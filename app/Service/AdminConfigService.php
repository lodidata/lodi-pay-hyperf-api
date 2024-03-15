<?php

namespace App\Service;

use App\Cache\Repository\LockRedis;
use Hyperf\DbConnection\Db;
use Swoole\Coroutine;

class AdminConfigService
{
    public function getConfig(array $keys): array
    {
        $cache_key = 'admin_config';
        if ($cache_data = redis()->get($cache_key)) {
            $data = json_decode($cache_data, true);
        } else {
            if (LockRedis::getInstance()->lock($cache_key, 20)) {
                try {
                    $data = Db::table('admin_config')->pluck('default_config', 'key')->toArray();
                    redis()->setex($cache_key, 300, json_encode($data));
                } finally {
                    LockRedis::getInstance()->delete($cache_key);
                }
            } else {
                if ($cache_data = redis()->get($cache_key)) {
                    $data = json_decode($cache_data, true);
                } else {
                    Coroutine::sleep(0.1);
                    return $this->getConfig($keys);
                }
            }
        }

        if (empty($keys)) {
            return [];
        } else {
            $result = [];
            foreach ($keys as $item) {
                if (isset($data[$item])) {
                    $default = json_decode($data[$item], true);
                    $result[$item] = $default['value'] ?? 0;
                } else {
                    $result[$item] = 0;
                }
            }
            return $result;
        }
    }
}