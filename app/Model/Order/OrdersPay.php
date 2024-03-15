<?php

declare(strict_types=1);

namespace App\Model\Order;

use App\Model\Model;

/**
 * @property int $id
 * @property string $order_sn 代付单号
 * @property string $inner_order_sn 内部单号
 * @property int $merchant_id 代付站点
 * @property string $payment 代付支付方式
 * @property string $user_id 用户id
 * @property int $currency_id 货币
 * @property string $amount 代付金额
 * @property string $balance 剩余余额
 * @property int $admin_id 处理人
 * @property string $remark 代付备注
 * @property string $pay_status 代付状态：success:出款成功，fail:出款失败，waiting:处理中
 * @property int $status 状态：1待匹配 2进行中 3待上传凭证 4上传凭证超时 5待确认 6待确认超时 7订单成功 8订单失败
 * @property \Carbon\Carbon $created_at 创建时间
 * @property \Carbon\Carbon $updated_at 更新时间
 * @property string $callback_url
 * @property int $call_back_status 0=未回调，1=回调成功，2=回调失败
 */
class OrdersPay extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'orders_pay';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'order_sn', 'inner_order_sn', 'merchant_id', 'merchant_account','payment', 'user_id', 'amount', 'balance', 'admin_id', 'remark',  'pay_status', 'status', 'created_at', 'updated_at','callback_url', 'call_back_status'];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'merchant_id' => 'integer',  'admin_id' => 'integer',  'status' => 'integer', 'created_at' => 'timestamp:Y-m-d H:i:s', 'updated_at' => 'timestamp:Y-m-d H:i:s', 'call_back_status' => 'integer'];
}
