<?php

declare(strict_types=1);

namespace App\Model\Order;

use App\Model\Model,
    App\Exception\ServiceException,
    Hyperf\DbConnection\Db,
    App\Model\Order\OrdersCollectionPay,
    App\Model\Order\OrdersPay,
    App\Model\Order\OrdersCollection;

/**
 * @property int $id
 * @property string $inner_order_sn 内部订单号
 * @property string $url 图片地址
 * @property int $type 0是order_collection表数据 1=是ordes_pay数据
 * @property string $remark 备注
 * @property \Carbon\Carbon $created_at
 */
class OrdersAttachment extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'orders_attachment';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'inner_order_sn', 'url', 'type', 'remark', 'created_at'];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'type' => 'integer', 'created_at' => 'timestamp:Y-m-d H:i:s'];

}
