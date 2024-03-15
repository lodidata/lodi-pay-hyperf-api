<?php

declare (strict_types=1);
namespace App\Model\Order;

use App\Model\Model;

/**
 * @property int $id 
 * @property string $orders_collection_sn 平台订单号
 * @property int $action_type 处理方案：1-待处理，2-订单失败，3-订单完成
 * @property int $problem_source 问题归责：0，待处理，1.无法确定,2.充值方问题,3.提款方问题,4.圆满解决
 * @property int $admin_id 操作人
 * @property string $description 事件描述
 * @property string $remark 备注
 * @property \Carbon\Carbon $created_at 创建时间
 * @property \Carbon\Carbon $updated_at 更新时间
 */
class OrdersCollectionTrial extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'orders_collection_trial';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'orders_collection_sn', 'action_type', 'problem_source', 'admin_id', 'description', 'remark', 'created_at', 'updated_at'];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'action_type' => 'integer', 'problem_source' => 'integer', 'admin_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}