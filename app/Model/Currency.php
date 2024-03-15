<?php

declare (strict_types=1);
namespace App\Model;

use App\Model\Model;
/**
 * @property int $id
 * @property string $currency_type 货币类型(货币简码)
 * @property string $currency_name 货币名称
 * @property int $status 状态 0：下架； 1：上架(默认)
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at 更新时间|维护时间
 */
class Currency extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'currency';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'currency_type', 'currency_name', 'status', 'created_at', 'updated_at'];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'status' => 'integer', 'created_at' => 'timestamp:Y-m-d H:i:s', 'updated_at' => 'timestamp:Y-m-d H:i:s'];

    public static function getCurrencyId($currency_type)
    {
        return self::where(['currency_type' => $currency_type])->value('id');
    }

    public static function getCurrencyType($currency_id)
    {
        return self::where(['id' => $currency_id])->value('currency_type');
    }
}