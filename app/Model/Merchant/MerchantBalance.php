<?php

declare (strict_types=1);
namespace App\Model\Merchant;

use App\Model\Model;

/**
 * @property int $id 
 * @property int $merchant_id 商户id
 * @property string $currency 币种
 * @property string $recharge_balance 充值余额
 * @property string $transfer_balance 提款余额
 * @property \Carbon\Carbon $updated_at 修改时间
 * @property \Carbon\Carbon $created_at 创建时间
 */
class MerchantBalance extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'merchant_balance';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'merchant_id', 'currency', 'recharge_balance', 'transfer_balance', 'updated_at', 'created_at'];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'merchant_id' => 'integer', 'updated_at' => 'datetime', 'created_at' => 'datetime'];
}