<?php

declare (strict_types=1);
namespace App\Model\Merchant;

use App\Model\Model;
/**
 * @property int $id 
 * @property int $merchant_id 商户id
 * @property string $merchant_key 商户私钥
 * @property string $merchant_public_key 商户公钥
 * @property string $secret_key 平台私钥
 * @property string $public_key 平台公钥
 */
class MerchantSecret extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'merchant_secret';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'merchant_id', 'merchant_key', 'merchant_public_key', 'secret_key', 'public_key'];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'merchant_id' => 'integer'];
}