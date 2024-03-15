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

namespace App\Controller;

use App\Constant\ResponseCode;
use App\Support\Response,
    Hyperf\Di\Annotation\Inject,
    Hyperf\HttpServer\Contract\RequestInterface,
    Psr\Container\ContainerInterface,
    App\Exception\ValidateException,
    Hyperf\Validation\Contract\ValidatorFactoryInterface;

abstract class AbstractController
{
    /**
     * @Inject
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @Inject
     * @var RequestInterface
     */
    protected $request;

    /**
     * @Inject
     * @var Response
     */
    protected $response;

    /**
     * @Inject()
     * @var ValidatorFactoryInterface
     */
    protected $validationFactory;

    public function __call($name, $arguments)
    {
        $msg = trans('public.call_message') . $name . implode(', ', $arguments) . trans('public.call_message_text');
        return $this->response->success([], $msg);
    }

    public function checkValidation($data, $rules,  $attribute = [], $message = [])
    {
        //校验请求参数是否存在
//        foreach ($data as $k => $v) {
//            if (!in_array($k, array_keys($attribute))) {
//                throw new ValidateException(trans('public.response_params_fail'), 0);
//            }
//        }
        //验证请求参数格式
        $validator = $this->validationFactory->make($data, $rules, $message, $attribute);
        if ($validator->fails()) {
            throw new ValidateException($validator->errors()->first(), ResponseCode::VALIDATION_ERROR);
        }
    }
}
