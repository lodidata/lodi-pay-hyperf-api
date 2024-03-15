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

namespace App\Exception\Handler;

use App\Constant\ResponseCode;
use Hyperf\Contract\StdoutLoggerInterface,
    Hyperf\ExceptionHandler\ExceptionHandler,
    Hyperf\HttpMessage\Stream\SwooleStream,
    Psr\Http\Message\ResponseInterface,
    Throwable;

class AppExceptionHandler extends ExceptionHandler
{
    /**
     * @var StdoutLoggerInterface
     */
    protected $logger;

    public function __construct(StdoutLoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        $this->logger->error(sprintf('%s[%s] in %s', $throwable->getMessage(), $throwable->getLine(), $throwable->getFile()));
        $this->logger->error($throwable->getTraceAsString());

        $is_dev = config('app_env') == 'dev';

        $data = json_encode([
            'code'   => ResponseCode::SERVER_ERROR,
            'error'  => $is_dev ? $throwable->getMessage() : 'Internal Server Error.',
            'traces' => $is_dev ? $throwable->getLine() : [],
        ], JSON_UNESCAPED_UNICODE);

        return $response->withHeader('Server', 'LodiPay')->withStatus(500)->withBody(new SwooleStream($data));
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }
}
