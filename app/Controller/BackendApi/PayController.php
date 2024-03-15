<?php

declare(strict_types=1);

namespace App\Controller\BackendApi;

use App\Cache\Repository\LockRedis;
use App\Constant\ResponseCode;
use App\Controller\AbstractController;
use App\Exception\ServiceException;
use App\Service\ThirdPayService;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class PayController extends AbstractController
{
    /**
     * @Inject()
     * @var ThirdPayService
     */
    protected $thirdPayService;

    public function apply()
    {
        $params = $this->request->getParsedBody();

        $rules = [
            'order_type' => 'required|string|in:1,2',
            'inner_order_sn' => 'required|string|max:60',
            'pay_type' => 'required|string|max:20',
        ];
        $this->checkValidation($params, $rules);

        $lock_key = 'backend_api:third_pay:'.$params['inner_order_sn'];
        $uuid = uniqid('backendapi_thirdpay',true);
        if (LockRedis::getInstance()->lock($lock_key,20,$uuid)) {
            try {
                if ($params['order_type'] == 1) {//充值单
                    throw new ServiceException('接口取消');
                } else {//提现单
                    $pay_inner_order_sn = $params['inner_order_sn'];
                    $third_inner_order_sn = $this->thirdPayService->payFailAmount($pay_inner_order_sn,$params['pay_type']);
                }
            } catch (\Exception $e) {
                return $this->response->fail($e->getMessage());
            } finally {
                LockRedis::getInstance()->delete($lock_key,$uuid);
            }
            return $this->response->success(['inner_order_sn' => $third_inner_order_sn]);
        }
        return $this->response->fail('Do not operate frequently');
    }
}