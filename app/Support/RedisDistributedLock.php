<?php

declare(strict_types=1);

namespace App\Support;

use Hyperf\Redis\Redis;

class RedisDistributedLock
{
    private $redis;
    private $lockKey;
    private $lockTimeout;
    private $lockValue;

    public function __construct(Redis $redis, string $lockKey, int $lockTimeout)
    {
        $this->redis = $redis;
        $this->lockKey = $lockKey;
        $this->lockTimeout = $lockTimeout;
    }

    public function lock(): bool
    {
        $this->lockValue = uniqid();
        $is_locked = $this->redis->set($this->lockKey, $this->lockValue, ['NX', 'EX' => $this->lockTimeout]);
        return (bool)$is_locked;
    }

    public function unlock():void
    {
        $script = <<<LUA
if redis.call('get',KEYS[1]) == ARGV[1] then
    return redis.call('del',KEYS[1])
else
    return 0
end
LUA;
        $this->redis->eval($script,[$this->lockKey,$this->lockValue],1);
    }
}
