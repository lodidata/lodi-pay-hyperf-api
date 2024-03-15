<?php

declare (strict_types=1);
namespace App\Model\Merchant;

use App\Model\Model;

/**
 * @property int $id 
 * @property int $merchant_account 商户id
 * @property int $transaction_type 交易类型：1=充值，2=提现，3=点位扣除金额
 * @property int $order_type 订单类型：1=充值，2=提现
 * @property string $order_sn 交易订单号
 * @property \Carbon\Carbon $created_at 创建时间
 * @property string $change_after 余额变动之后
 * @property string $change_before 余额变动之前
 */
class MerchantBalanceChangeLog extends Model
{
    public $timestamps = false;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'merchant_balance_change_log';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'merchant_account', 'transaction_type', 'order_type', 'order_sn', 'created_at', 'change_after', 'change_before'];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'merchant_account' => 'string', 'transaction_type' => 'integer', 'order_type' => 'integer', 'created_at' => 'datetime'];
}