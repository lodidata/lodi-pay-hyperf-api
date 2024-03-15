<?php

namespace App\Service;

use App\Cache\Repository\LockRedis;
use App\Constant\ResponseCode;
use App\Exception\ServiceException;
use Hyperf\DbConnection\Db;
use Swoole\Coroutine;

class MerchantService
{
    public function updateTransferBalance($inner_order_sn,$mer_account,$currency,$order_amount)
    {
    }

    public function updateCollectionBalance($collection_inner_order_sn,$mer_account,$currency,$order_amount)
    {

    }

    public function getMerchantSecret($merchant_id): array
    {
        $cache_key = 'merchant_secret:'.$merchant_id;
        if ($cache_data = redis()->get($cache_key)) {
            $data = json_decode($cache_data,true);
        } else {
            if (LockRedis::getInstance()->lock($cache_key,20)) {
                try{
                    $data = (array)Db::table('merchant_secret')->where('merchant_id', (int)$merchant_id)->first(['secret_key','merchant_public_key']);
                    redis()->setex($cache_key,300,json_encode($data));
                } finally {
                    LockRedis::getInstance()->delete($cache_data);
                }
            } else {
                if ($cache_data = redis()->get($cache_key)) {
                    $data = json_decode($cache_data,true);
                } else {
                    Coroutine::sleep(0.1);
                    return $this->getMerchantSecret($merchant_id);
                }
            }
        }
        return $data;
    }

    public function getMerchantByAccount($account): array
    {
        $cache_key = 'merchant:'.$account;
        if ($cache_data = redis()->get($cache_key)) {
            $data = json_decode($cache_data,true);
        } else {
            if (LockRedis::getInstance()->lock($cache_key,20)) {
                try {
                    $data = (array)Db::table('merchant')->where('account',(string)$account)
                        ->first(['id','deleted_at','is_pay_behalf','is_collection_behalf','recharge_waiting_limit','ip_white_list']);
                    redis()->setex($cache_key,300,json_encode($data));
                } finally {
                    LockRedis::getInstance()->delete($cache_key);
                }
            } else {
                if ($cache_data = redis()->get($cache_key)) {
                    $data = json_decode($cache_data,true);
                } else {
                    Coroutine::sleep(0.1);
                    return $this->getMerchantByAccount($account);
                }
            }
        }
        return $data;
    }

}