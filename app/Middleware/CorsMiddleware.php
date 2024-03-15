<?php

declare(strict_types=1);

namespace App\Middleware;

use Hyperf\Utils\Context,
    Psr\Http\Message\ResponseInterface,
    Psr\Http\Message\ServerRequestInterface,
    Psr\Http\Server\MiddlewareInterface,
    Psr\Http\Server\RequestHandlerInterface,
    Hyperf\Contract\TranslatorInterface,
    Hyperf\Di\Annotation\Inject;

class CorsMiddleware implements MiddlewareInterface
{

    /**
     *
     * @Inject
     * @var TranslatorInterface
     */
    private $translator;


    /**
     * 解决跨域问题
     *
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = Context::get(ResponseInterface::class);
        $access_origin = env('ACCESS_ORIGIN');
        $response = $response->withHeader('Access-Control-Allow-Origin', $access_origin)
            ->withHeader('Access-Control-Allow-Methods', '*')
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            // Headers 可以根据实际情况进行改写。
            ->withHeader('Access-Control-Allow-Headers', 'Accept-Language,Referer,User-Agent,X-Requested-With,X-Request-Uri,Accept,Origin,DNT,Keep-Alive,User-Agent,Cache-Control,Content-Type,Accept-Api-Version,Authorization');

        Context::set(ResponseInterface::class, $response);
        if ($request->getMethod() == 'OPTIONS') {
            return $response;
        }
        return $handler->handle($request);
    }
}
