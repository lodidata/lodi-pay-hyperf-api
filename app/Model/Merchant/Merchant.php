<?php

declare(strict_types=1);

namespace App\Model\Merchant;

use App\Model\Model;

/**
 * @property int $id
 * @property string $name 名称
 * @property string $account 账号
 * @property int $is_pay_behalf 是否开启代付
 * @property int $pay_behalf_level 代付等级
 * @property string $pay_behalf_point 代付点位
 * @property int $is_collection_behalf 是否开启代收
 * @property int $collection_pay_level 代收等级
 * @property string $collection_pay_point 代收点位
 * @property string $office_url 官网地址
 * @property string $pay_callback_url 支付回调地址
 * @property string $collect_callback_url 收款回调地址
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 */
class Merchant extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'merchant';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'name', 'account', 'is_pay_behalf', 'pay_behalf_level', 'pay_behalf_point', 'is_collection_behalf', 'collection_pay_level', 'collection_pay_point', 'office_url', 'pay_callback_url', 'collect_callback_url', 'created_at', 'updated_at', 'deleted_at'];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'is_pay_behalf' => 'integer', 'pay_behalf_level' => 'integer', 'is_collection_behalf' => 'integer', 'collection_pay_level' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];


    public static function getAccountId($account)
    {
        return self::where(['account' => $account])->value('id');
    }
}
