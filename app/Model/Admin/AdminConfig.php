<?php

declare (strict_types=1);
namespace App\Model\Admin;

use App\Model\Model;
/**
 * @property int $id
 * @property int $parent_id
 * @property string $name 名称
 * @property string $key 编码
 * @property string $default_config
 * @property string $info 备注
 */
class AdminConfig extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'admin_config';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'parent_id', 'name','key', 'default_config', 'info'];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'parent_id' => 'integer'];

    public static function getData(array $name){
        $result=self::whereIn('key',$name)->get(['key','default_config']);
        $list=[];
        if ($result) {
            foreach($result as $k=>$v){
                $default=json_decode($v->default_config,true);
                $list[$v->key]=$default['value']??0;
            }
        }
        return $list;
    }
}