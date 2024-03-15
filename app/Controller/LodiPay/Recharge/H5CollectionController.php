<?php

declare(strict_types=1);

namespace App\Controller\LodiPay\Recharge;

use App\Amqp\Producer\CallbackProducer;
use App\Amqp\Producer\DelayDirectProducer;
use App\Cache\Repository\LockRedis;
use App\Constant\ResponseCode;
use App\Controller\AbstractController;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Psr\Http\Message\ResponseInterface;
use Hyperf\Amqp\Producer;
use App\Exception\ServiceException;
use App\Service\CollectionOrderService;
use App\Support\AwsOss;

class H5CollectionController extends AbstractController
{
    /**
     * @Inject()
     * @var AwsOss
     */
    protected $awsOss;
    /**
     * @Inject()
     * @var CollectionOrderService
     */
    protected $collectionOrderService;

    /**
     * 获取收款人信息
     */
    public function payeeInfo(): ResponseInterface
    {
        $data = $this->request->all();
        $rules = [
            'mer_no' => 'required|string|max:30',
        ];
        $this->checkValidation($data, $rules, trans('lodipay.h5_public'));

        $inner_order_sn = (string)$data['mer_no'];

        $order = (array)Db::table('orders_collection')->where('inner_order_sn', $inner_order_sn)
            ->first(['currency', 'amount', 'status','user_account','pay_inner_order_sn', 'created_at']);

        if (count($order) == 0 || $order['status'] == 1) {
            return $this->response->fail('Order does not exist or did not match successfully');
        }

        $user_account = $order['user_account'];
        $amount = $order['amount'];
        $currency = $order['currency'];

        //返回收款人信息
        $orders_pay = (array)Db::table('orders_pay')->where('inner_order_sn', (string)$order['pay_inner_order_sn'])->first(['user_account']);

        $upload_cert_timeout = -1;
        $countdown_second = 0;
        if ($order['status'] == 2) {
            $create_time = strtotime($order['created_at']);
            $timeout_second = time() - $create_time;
            if ($timeout_second > 720) {
                $upload_cert_timeout = 1;
                $countdown_second = 0;
            } else {
                $upload_cert_timeout = 0;
                $countdown = 720 - $timeout_second;
                $countdown_second =  $countdown > 0 ? $countdown : 0;
            }
        } elseif ($order['status'] == 3) {
            $upload_cert_timeout = 1;
        }

        return $this->response->success([
            'mer_no' => $data['mer_no'],
            'user_account' => $user_account,
            'amount' => $amount,
            'currency' => $currency,
            'account' => $orders_pay['user_account'] ?? '',
            'upload_cert_timeout' => $upload_cert_timeout,
            'countdown_second' => $countdown_second,
        ]);
    }

    /**
     * 上传支付凭证
     */
    public function uploadCert(): ResponseInterface
    {
        $data = $this->request->all();
        $rules = [
            'mer_no' => 'required|string|max:30',
            'img_url' => 'required|array',
            'remark' => 'string|max:400'
        ];

        $this->checkValidation($data, $rules, trans('lodipay.h5_upload'));
        if (!count($data['img_url']))  return $this->response->fail('Please choose to upload an image');

        $inner_order_sn = (string)$data['mer_no'];
        $collection_order = Db::table('orders_collection')->where(['inner_order_sn' => $inner_order_sn])->first(['status','station_url']);
        if (!$collection_order) return $this->response->fail('The record does not exist');
        if (!in_array($collection_order->status,[2,3])) {
            return $this->response->fail('Please do not upload the voucher again, if you have any questions, please contact customer service');
        }

        $is_upload = Db::table('orders_attachment')->where(['inner_order_sn' => $inner_order_sn])->count();
        $update_data = [
            'inner_order_sn' => $data['mer_no'],
            'url' => json_encode($data['img_url']),
            'type' => 0
        ];
        if (isset($data['remark'])) $update_data['remark'] = $data['remark'];

        try {
            Db::beginTransaction();
            if ($is_upload) {
                Db::table('orders_attachment')->where(['inner_order_sn' => $inner_order_sn])->update($update_data);
            } else {
                Db::table('orders_attachment')->insert($update_data);
            }

            Db::table('orders_collection')->where('inner_order_sn',$inner_order_sn)->whereIn('status',[2,3])->update([
                'updated_at' => date('Y-m-d H:i:s'),
                'status' => 4
            ]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollBack();
            return $this->response->fail(trans('public.upload_h5_credentials_fail'));
        }
        //12分钟检查上传确认收款是否超时
        $delay_message = new DelayDirectProducer(['orders_collection_sn' => $inner_order_sn, 'event' => 'upload_cert']);
        $delay_message->setDelayMs(720000);
        $producer = di()->get(Producer::class);
        $producer->produce($delay_message);

        //上传凭证
        $message = new CallbackProducer(['event' => 'upload_cert','inner_order_sn' => $inner_order_sn]);
        $producer->produce($message);
        
        return $this->response->success([ 'station_url' => $collection_order->station_url]);
    }

    /**
     * 上传文件
     */
    public function upload(): ResponseInterface
    {
        $file = $this->request->file('upload');
        if ($file) {
            //获取文件大小
            $size = $file->getSize();
            if ($size > 5*1024*1024) {
                return $this->response->fail('The size of the uploaded image cannot exceed 5M');
            }
            //获取文件MIME
            $mime_type = $file->getMimeType();
            if (substr($mime_type,0,6) !== 'image/') {
                return $this->response->fail('The uploaded file type is illegal');
            }
            $fileExt = $file->getExtension();
            $filename = md5(time() . mt_rand(0, 999999)) . '.' . $fileExt;
            $file = $file->getRealPath();

            list($img_url, $path) = $this->awsOss->uploadFile($filename, $file);
            if ($img_url) {
                return $this->response->success(['img_url' => $img_url, 'path' => $path]);
            }
            return $this->response->fail();
        }
        return $this->response->fail('The uploaded file cannot be empty');
    }

    /**
     * 查看凭证
     */
    public function showCert(): ResponseInterface
    {
        $data = $this->request->all();
        $rules = [
            'mer_no' => 'required|string|max:30',
        ];
        $this->checkValidation($data, $rules, trans('lodipay.h5_upload'));

        $res = [
            'img_url' => [],
            'remark' => ''
        ];

        $orders_attachment = (array)Db::table('orders_attachment')->where(['inner_order_sn' => $data['mer_no'], 'type' => 0])->first(['url', 'remark']);
        
        if (empty($orders_attachment)) {
            return $this->response->fail('The record does not exist');
        }
        $res['img_url'] = array_map(function ($v){
            return env('AWSOSS_DOMAIN').$v;
        },json_decode($orders_attachment['url'],true));
        $res['remark'] = $orders_attachment['remark'];

        return $this->response->success($res);
        
        
    }

    public function cancelRecharge()
    {
        $data = $this->request->all();
        $rules = [
            'mer_no' => 'required|string|max:30',
        ];
        $this->checkValidation($data, $rules, trans('lodipay.h5_upload'));
        
        $inner_order_sn = (string)$data['mer_no'];
        
        $lock_key = 'cancel_recharge:'.$data['mer_no'];
        $uuid = uniqid('cancel_recharge',true);
        if (LockRedis::getInstance()->lock($lock_key,10,$uuid)) {
            try {
                $collection_order = (array)Db::table('orders_collection')->where('inner_order_sn',$inner_order_sn)->first(['status','order_status','amount','inner_order_sn','pay_inner_order_sn','currency']);
                if (empty($collection_order)) throw new ServiceException('Order does not exist',ResponseCode::FAIL);
                
                $this->collectionOrderService->cancel($collection_order);
            } catch (ServiceException $e) {
                return $this->response->fail();
            } finally {
                LockRedis::getInstance()->delete($lock_key,$uuid);
            }
            return $this->response->success();
        }

        return $this->response->fail('Do not operate frequently');

    }
}
