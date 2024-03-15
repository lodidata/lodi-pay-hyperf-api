<?php

declare(strict_types=1);

namespace App\Model\Order;

use App\Model\Model,
    Hyperf\Di\Annotation\Inject,
    App\Model\Merchant\Merchant,
    App\Model\Merchant\User,
    App\Exception\ServiceException,
    App\Support\Response,
    Exception;


/**
 * @property int $id
 * @property string $order_sn 代收单号
 * @property string $inner_order_sn 内部单号
 * @property int $merchant_id 商户站点
 * @property int $merchant_account 商户号
 * @property string $payment 代收支付方式
 * @property string $user_id 用户id
 * @property string $user_account 用户账号
 * @property string $amount 代收金额
 * @property int $admin_id 处理人
 * @property string $remark 代付备注
 * @property int $match_order_id 匹配的代付id,默认为0是未匹配
 * @property int $status 状态  1=待匹配 2=待上传凭证 3=上传凭证超时 4=待确认 5=确认超时 6=订单完成 7=订单异常
 * @property string $call_back_status  0=未回调，1=支付上传凭证回调成功，2=收款确认回调成功，3=失败
 * @property string $callback_url  回调地址
 * @property string $station_url  跳转到站点地址
 * @property \Carbon\Carbon $created_at 创建时间
 * @property \Carbon\Carbon $updated_at 更新时间
 */
class OrdersCollection extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'orders_collection';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'order_sn', 'inner_order_sn', 'merchant_id', 'merchant_account', 'payment', 'user_id', 'user_account', 'currency', 'amount', 'admin_id', 'remark',  'order_status', 'status',  'created_at', 'updated_at', 'call_back_status', 'callback_url', 'station_url'];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'merchant_id' => 'integer',  'admin_id' => 'integer', 'match_order_id' => 'integer', 'status' => 'integer', 'created_at' => 'timestamp:Y-m-d H:i:s', 'updated_at' => 'timestamp:Y-m-d H:i:s'];


    /**
     * @Inject
     * @var Response
     */
    protected $response;


    public static function index($data)
    {
        $result = self::where(
            [
                'order_sn' => $data['order_no'],
                'merchant_account' => $data['mer_account']
            ]
        )->first();

        return !isset($result->inner_order_sn) ? [
            'mer_no' => (string)$result->inner_order_sn,
            'order_no' => (string)$data['order_no'],
            'amount' => $result->amount,
            'result_status' => $result->order_status,
        ] : [];
    }
}
