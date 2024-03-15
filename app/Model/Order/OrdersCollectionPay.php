<?php

declare (strict_types=1);
namespace App\Model\Order;

use App\Model\Model;
/**
 * @property int $id
 * @property string $orders_collection_sn 代收订单号
 * @property string $orders_pay_sn 代付订单号
 * @property string $orders_collection_amount 代收金额
 * @property string $orders_pay_balance 代付剩余余额
 * @property string $status 支付状态 1=待匹配 2=待上传凭证 3=上传凭证超时 4=待确认 5=确认超时 6=订单完成 7=订单异常
 * @property string $order_type  订单类型：1内充订单 2兜底订单
 * @property string $is_controversial 是否存在争议 1 是
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class OrdersCollectionPay extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'orders_collection_pay';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'orders_collection_sn', 'orders_pay_sn', 'orders_collection_amount', 'orders_pay_balance', 'status', 'order_type','created_at', 'updated_at','is_controversial'];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'created_at' => 'timestamp:Y-m-d H:i:s', 'updated_at' => 'timestamp:Y-m-d H:i:s'];
}