<?php

declare(strict_types=1);

namespace App\Middleware\Pay;

use App\Helper\SignHelper;
use App\Service\MerchantService;
use App\Support\Response;
use Hyperf\DbConnection\Db;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface,
    Hyperf\Di\Annotation\Inject;

/**
 * 代付签名
 * Class PaymentSignMiddleware
 * @package App\Middleware
 */
class PayMiddleware implements MiddlewareInterface
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
        $params = $request->getParsedBody();
        //获取商户号
        if (isset($params['mer_account']) && !empty($params['mer_account'])) {
            //查询商户号信息
            $merchant = $this->merchantService->getMerchantByAccount($params['mer_account']);
            if(!$merchant || !empty($merchant['deleted_at'])) {
                return $this->response->fail('Merchant account does not exist');
            }
            $merchant_secret = $this->merchantService->getMerchantSecret($merchant['id']);
            if (!$merchant_secret) {
                return $this->response->fail('Merchant secret does not exist');
            }
            //查询商户公钥
            $public_key = (string)$merchant_secret['merchant_public_key'];
            if (SignHelper::signVerify($params, $public_key)) {
                return $handler->handle($request);
            }
            return $this->response->fail(trans('public.sign_fail'));
        }
        return $this->response->fail(trans('public.account_fail'));
    }
}
