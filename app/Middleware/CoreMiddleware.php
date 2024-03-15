<?php

declare(strict_types=1);

namespace App\Middleware;

use Hyperf\Utils\Contracts\Arrayable,
  Psr\Http\Message\ResponseInterface,
  Psr\Http\Message\ServerRequestInterface,
  Hyperf\HttpServer\CoreMiddleware as Core,
  Hyperf\HttpServer\Router\Dispatched,
  Hyperf\Utils\Context,
  App\Support\Response,
  Hyperf\Contract\TranslatorInterface,
  Hyperf\Di\Annotation\Inject;

class CoreMiddleware extends Core
{

  /**
   * @Inject
   * @var Response
   */
  protected $response;

  /**
   *
   * @Inject
   * @var TranslatorInterface
   */
  private $translator;

  /**
   * Handle the response when cannot found any routes.
   *
   * @return array|Arrayable|mixed|ResponseInterface|string
   */
  protected function handleNotFound(ServerRequestInterface $request)
  {
    // 重写路由找不到的处理逻辑
    return $this->response->error(trans('public.404_fail'), 404);
  }

  /**
   * Handle the response when the routes found but doesn't match any available methods.
   *
   * @return array|Arrayable|mixed|ResponseInterface|string
   */
  protected function handleMethodNotAllowed(array $methods, ServerRequestInterface $request)
  {
    // 重写 HTTP 方法不允许的处理逻辑
    return $this->response->error(trans('public.405_fail'), 405);
  }

  /**
   *
   *组装接口及请求头
   * @param ServerRequestInterface $request
   * @return ServerRequestInterface
   */
  public function dispatch(ServerRequestInterface $request): ServerRequestInterface
  {
    //配置临时语言
    $lang = $request->getHeaderLine('Accept-Language') ?: 'en';
    $this->translator->setLocale($lang);
    //解析接口版本
    $request = $this->resetVersionApi($request);
    Context::set(ServerRequestInterface::class, $request);
    $routes = $this->dispatcher->dispatch($request->getMethod(), $request->getUri()->getPath());
    $dispatched = new Dispatched($routes);
    return Context::set(ServerRequestInterface::class, $request->withAttribute(Dispatched::class, $dispatched));
  }

  /**
   * 根据请求头信息跳转指定版本接口
   *
   * @param [type] $request
   * @return void
   */
  private function resetVersionApi($request)
  {
    $version = $request->getHeaderLine('Accept-Api-Version') ?: 'v1';
    $old_url = $request->getUri()->getPath();
    $arr_url = explode('api', $old_url);
    list($start, $end) = $arr_url;
    $new_url = '/api' . $start . $version . $end;
    $req_url = $request->getUri()->withPath($new_url);
    $request = $request->withUri($req_url);

    return $request;
  }
}
