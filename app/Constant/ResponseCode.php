<?php
declare(strict_types=1);

namespace App\Constant;

/**
 * HTTP 响应状态码枚举
 *
 * @package App\Constants
 */
class ResponseCode
{
    const SUCCESS = 0;   // 接口处理成功
    const FAIL    = 1;   // 接口处理失败

    /**
     * Server Error！
     */
    const SERVER_ERROR = 500;

    /**
     * 请求数据验证失败！
     */
    const VALIDATION_ERROR = 400;
}
