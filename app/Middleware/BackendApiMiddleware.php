<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helper\BackendSignHelper;
use App\Support\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Hyperf\Di\Annotation\Inject;

class BackendApiMiddleware implements MiddlewareInterface
{
    /**
     * @Inject
     * @var Response
     */
    protected $response;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $client_ip = get_real_ip();
        if (empty($client_ip)) {
            return $this->response->fail('Get IP error');
        }

        if (!in_array($client_ip,explode(',',env('BACKEND_API_IPS','')))) {
            return $this->response->fail($client_ip.' not added to the api whitelist');
        }

        $params = $request->getParsedBody();
        if (isset($params['sign']) && !empty($params['sign'])) {
            if (BackendSignHelper::signVerify($params,env('BACKEND_API_KEY'))){
                return $handler->handle($request);
            }
            return $this->response->fail('signature error');
        }
        return $this->response->fail('missing signature');
    }
}