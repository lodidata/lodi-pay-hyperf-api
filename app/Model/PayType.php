<?php

declare (strict_types=1);
namespace App\Model;

use App\Model\Model;
/**
 * @property int $id
 * @property int $parent_id 父级id
 * @property string $name 支付名称
 * @property int $name_code 支付编码
 */
class PayType extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pay_type';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'parent_id', 'name', 'name_code'];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'parent_id' => 'integer', 'name_code' => 'integer'];

}