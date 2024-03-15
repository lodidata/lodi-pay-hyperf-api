<?php

declare (strict_types=1);
namespace App\Model\Merchant;

use App\Model\Model;
/**
 * @property int $id 
 * @property string $user_account 用户支付/收款账号
 * @property string $username 用户姓名
 * @property int $merchant_id 
 * @property int $status 
 * @property \Carbon\Carbon $created_at 创建时间
 * @property \Carbon\Carbon $updated_at 
 * @property string $deleted_at 
 */
class User extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'user_account', 'username', 'merchant_id', 'status', 'created_at', 'updated_at', 'deleted_at'];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'merchant_id' => 'integer', 'status' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}