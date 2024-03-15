<?php
declare(strict_types=1);

namespace App\Cache\Repository;

use App\Cache\Contracts\LockRedisInterface;
use Swoole\Coroutine;

/**
 * Redis Lock
 *
 * @package App\Cache\Repository
 */
final class LockRedis extends AbstractRedis implements LockRedisInterface
{
    protected $prefix = 'rds-lock';

    /**
     * 获取 Redis 锁
     *
     * @param string $key 锁标识
     * @param int $expired 过期时间/秒
     * @param int $value 锁存储值
     * @param int $timeout 获取超时/秒，默认每隔 0.1 秒获取一次锁
     * @return bool
     */
    public function lock(string $key, $expired = 1, $value = 1, int $timeout = 0): bool
    {
        $lockName = $this->getCacheKey($key);

        // 重复获取次数
        $retry = $timeout > 0 ? intdiv($timeout * 100, 10) : 1;
        do {
            $lock = $this->redis()->set($lockName, $value, ['nx', 'ex' => $expired]);
            if ($lock || $timeout === 0) {
                break;
            }

            // 默认 0.1 秒一次取锁
            Coroutine::getCid() ? Coroutine::sleep(0.1) : usleep(100000);

            $retry--;
        } while ($retry);

        return $lock;
    }

    /**
     * 释放 Redis 锁
     *
     * @param string $key
     * @param int $value
     * @return mixed
     */
    public function delete(string $key, $value = 1)
    {
        $script = <<<LAU
            if redis.call("GET", KEYS[1]) == ARGV[1] then
                return redis.call("DEL", KEYS[1])
            else
                return 0
            end
LAU;

        return $this->redis()->eval($script, [$this->getCacheKey($key), $value], 1);
    }

}
