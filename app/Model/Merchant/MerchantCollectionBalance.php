<?php

declare (strict_types=1);
namespace App\Model\Merchant;

use App\Model\Model;

/**
 * @property int $id 
 * @property string $merchant_account 商户号
 * @property string $currency 币种
 * @property string $balance 充值余额
 * @property \Carbon\Carbon $created_at 创建时间
 * @property \Carbon\Carbon $updated_at 修改时间
 */
class MerchantCollectionBalance extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'merchant_collection_balance';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'merchant_account', 'currency', 'balance', 'created_at', 'updated_at'];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}