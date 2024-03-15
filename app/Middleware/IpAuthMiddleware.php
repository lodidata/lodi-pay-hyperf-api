<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Service\MerchantService;
use App\Support\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface,
    Hyperf\Di\Annotation\Inject,
    Hyperf\DbConnection\Db;

class IpAuthMiddleware implements MiddlewareInterface
{
    /**
     * @Inject
     * @var Response
     */
    protected $response;

    /**
     * @Inject
     * @var MerchantService
     */
    protected $merchantService;

    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $client_ip = get_real_ip();
        if (!empty($client_ip)) {
            $params = $request->getParsedBody();
            if (isset($params['mer_account']) && !empty($params['mer_account'])) {
                $merchant = $this->merchantService->getMerchantByAccount($params['mer_account']);
                $ip_white_list = $merchant['ip_white_list']??'';
                if (!empty($ip_white_list)) {
                    $allow_ips = explode(',', $ip_white_list);
                    if (in_array($client_ip, $allow_ips)) {
                        return $handler->handle($request);
                    } else {
                        return $this->response->fail($client_ip.trans('public.whitelist_fail'));
                    }
                } else {
                    return $this->response->fail($client_ip.trans('public.whitelist_fail'));
                }
            }
            return $this->response->fail(trans('public.account_fail'));
        } else {
            return $this->response->fail('Get IP error');
        }
    }
}
