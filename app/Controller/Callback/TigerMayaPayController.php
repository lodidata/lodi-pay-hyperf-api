<?php

namespace App\Controller\Callback;

use App\Controller\AbstractController;
use App\Exception\ServiceException;
use App\Service\Pay\TigerMayaPayService;
use Hyperf\DbConnection\Db;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Di\Annotation\Inject;

class TigerMayaPayController extends AbstractController
{
    protected $payType = 'tigermayapay';
    /**
     * @Inject()
     * @var TigerMayaPayService
     */
    private $payService;

    public function callbackResult($merchant_account)
    {
        $data = $this->request->getParsedBody();
        try {
            $config = $this->payService->getConfig($merchant_account);
            if (empty($config)) throw new ServiceException($this->payType.' callback error config is empty');

            if (!$this->payService->verifySign($data, $config['key'].$config['pub_key'])) {
                throw new ServiceException('sign error');
            }

            $order_sn = (string)$data['out_trade_no'];
            //查询订单
            $order = (array)Db::table('orders_collection')->where(['inner_order_sn' => $order_sn, 'order_type' => 3])->first(['status', 'amount', 'pay_inner_order_sn']);
            if (!$order) throw new ServiceException('order does not exist');
            if ($order['status'] == 6) {
                return 'SUCCESS';
            }
            $orders_pay_sn = $order['pay_inner_order_sn'];
            $pay_order = (array)Db::table('orders_pay')->where(['inner_order_sn' => $orders_pay_sn])->first(['amount']);
            $is_split = bccomp((string)$order['amount'], (string)$pay_order['amount'], 2) == 0 ? 0 : 1;
            if ($data['state'] == 'completed') {//成功
                $this->payService->updatePayOrder($order_sn, $orders_pay_sn, $is_split, 1,  'success');
            } elseif(in_array($data['state'],['reject','failed','refund'])) {//失败
                $this->payService->updatePayOrder($order_sn, $orders_pay_sn, $is_split, 1,  'fail');
            }
            return 'SUCCESS';
        } catch (\Exception $e) {
            logger()->error($this->payType.'回调错误',[$e->getMessage(),$merchant_account]);
            return 'FAIL';
        }
    }

}