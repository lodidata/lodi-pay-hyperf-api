<?php

declare(strict_types=1);

use Hyperf\Contract\StdoutLoggerInterface,
    Hyperf\HttpServer\Contract\ResponseInterface,
    Hyperf\Logger\LoggerFactory,
    Hyperf\Server\ServerFactory,
    Hyperf\Utils\ApplicationContext,
    Psr\Http\Message\ServerRequestInterface,
    Hyperf\Redis\Redis,
    Psr\Container\ContainerInterface,
    Psr\Log\LoggerInterface,
    Hyperf\Snowflake\IdGeneratorInterface;


/**
 * 容器实例
 *
 * @return ContainerInterface
 */
function di(): ContainerInterface
{
    return ApplicationContext::getContainer();
}

/**
 * Redis 客户端实例
 *
 * @return Redis|mixed
 */
function redis()
{
    return di()->get(Redis::class);
}

/**
 * Server 实例 基于 Swoole Server
 *
 * @return \Swoole\Coroutine\Server|\Swoole\Server
 */
function server()
{
    return di()->get(ServerFactory::class)->getServer()->getServer();
}

/**
 * 缓存实例 简单的缓存
 *
 * @return mixed|\Psr\SimpleCache\CacheInterface
 */
function cache()
{
    return di()->get(Psr\SimpleCache\CacheInterface::class);
}

/**
 * Dispatch an event and call the listeners.
 *
 * @return mixed|\Psr\EventDispatcher\EventDispatcherInterface
 */
function event()
{
    return di()->get(Psr\EventDispatcher\EventDispatcherInterface::class);
}

/**
 * 控制台日志
 *
 * @return StdoutLoggerInterface|mixed
 */
function stdout_log()
{
    return di()->get(StdoutLoggerInterface::class);
}

/**
 * 文件日志
 *
 * @param string $name
 * @return LoggerInterface
 */
function logger(string $name = 'APP'): LoggerInterface
{
    return di()->get(LoggerFactory::class)->get($name);
}

/**
 * Http 请求实例
 *
 * @return mixed|ServerRequestInterface
 */
function request()
{
    return di()->get(ServerRequestInterface::class);
}

/**
 * 请求响应
 *
 * @return ResponseInterface|mixed
 */
function response()
{
    return di()->get(ResponseInterface::class);
}

/**
 * 获取客户端你真实IP
 *
 * @return mixed|string
 */
function get_real_ip(): string
{
    if ($ip = request()->getHeaderLine('x-real-ip')) {
        return $ip;
    } else if ($ip = request()->getHeaderLine('x-forwarded-for')) {
        return $ip;
    }

    return request()->getServerParams()['remote_addr'] ?? '';
}


/**
 * 加密
 * @param string $string
 * @param string $key
 * @param string $method
 * @return string
 */
function encrypt(string $string, string $key = "", $method = 'AES-256-CFB8')
{
    $key = $key ?: config('keys.data.secret');

    $iv = substr($key,-16);
    return urlSafeB64encode(openssl_encrypt($string,  $method, $key, OPENSSL_RAW_DATA, $iv));
}

/**
 * 解密
 * @param $string
 * @param string $key
 * @param string $method
 * @return bool|string
 */
function decrypt($string, $key = "", $method = 'AES-256-CFB8')
{
    $key = $key ?: config('keys.data.secret');
    $iv = substr($key,-16);
    return  openssl_decrypt(urlSafeB64decode($string),  $method, $key, OPENSSL_RAW_DATA, $iv);
}

/**
 * 雪花算法获取id
 */
function snowflakeId()
{
    $container = ApplicationContext::getContainer();
    $generator = $container->get(IdGeneratorInterface::class);
    return $generator->generate();
}

function payNumIncr($key,$member)
{
    redis()->zIncrBy($key,1,$member);
}

function payNumDecr($key,$member)
{
    $script = <<<LAU
            local score = redis.call("ZSCORE", KEYS[1], ARGV[1])
            if score == false or score < '1' then
                redis.call("ZADD", KEYS[1], 0, ARGV[1])
            else
                redis.call("ZINCRBY", KEYS[1], -1, ARGV[1])
            end
LAU;
    redis()->eval($script, [$key, $member], 1);
}

function urlSafeB64encode($string)
{
    $data = base64_encode($string);
    $data = str_replace(array('+','/','='),array('-','_',''),$data);
    return $data;
}

function urlSafeB64decode($string)
{
    $data = str_replace(array('-','_'),array('+','/'),$string);
    $mod4 = strlen($data) % 4;
    if ($mod4) {
        $data .= substr('====', $mod4);
    }
    return base64_decode($data);
}

function randStr(int $length = 16, string $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ012345679') {
    $chars_length = strlen($chars);
    $string = '';
    while ($length--) {
        $string .= substr($chars, mt_rand(0, $chars_length - 1), 1);
    }

    return $string;
}