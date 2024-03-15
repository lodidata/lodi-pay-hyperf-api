<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Support\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Hyperf\Di\Annotation\Inject;

/**
 * 代付签名
 * Class PaymentSignMiddleware
 * @package App\Middleware
 */
class H5CollectionMiddleware implements MiddlewareInterface
{
    /**
     * @Inject
     * @var Response
     */
    protected $response;


    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $params = array_merge($request->getParsedBody(),$request->getQueryParams());
        //获取商户号
        if (isset($params['token']) && !empty($params['token'])) {
            $json = decrypt($params['token'],env('H5_TOKEN_SALT'));
            $arr = json_decode($json,true);
            if (is_array($arr)) {
                if (!isset($arr['mer_account']) || !isset($arr['expire']) || !isset($arr['mer_no'])) {
                    return $this->response->fail('Please close the page and enter again from the deposit record');
                }
                if (isset($params['mer_no']) && ($params['mer_no'] != $arr['mer_no'])) {
                    return $this->response->fail('Please close the page and enter again from the deposit record');
                }
                $expire_time = $arr['expire'];
                if (time() > $expire_time) return $this->response->fail('Please close the page and enter again from the deposit record');

                return $handler->handle($request);
            }
            return $this->response->fail('Please close the page and enter again from the deposit record');
        }
        return $this->response->fail('Missing parameter token');
    }
}
