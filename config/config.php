<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
use Hyperf\Contract\StdoutLoggerInterface;
use Psr\Log\LogLevel;

$app_env = env('APP_ENV', 'dev');

$log_level = [
    LogLevel::ALERT,
    LogLevel::CRITICAL,
    LogLevel::EMERGENCY,
    LogLevel::ERROR,
    LogLevel::INFO,
    LogLevel::NOTICE,
    LogLevel::WARNING,
];

$app_env == 'dev' && $log_level[] = LogLevel::DEBUG;

return [
    'keys' => [
        //数据加密配置
        'data' => [
            'secret' => env('KEYS_DATA_SECRET'),
            'enable' => env('KEYS_DATA_ENABLE')   //是否启用 1启用 0不启用
        ]
    ],
    'app_name' => env('APP_NAME', 'skeleton'),
    'app_env' => $app_env,
    'scan_cacheable' => env('SCAN_CACHEABLE', false),
    StdoutLoggerInterface::class => [
        'log_level' => $log_level,
    ],
];
