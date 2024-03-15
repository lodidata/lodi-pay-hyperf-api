<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ':attribute必须接受',
    'active_url' => ':attribute必须是一个合法的URL',
    'after' => ':attribute必须是:date之后的一个日期',
    'after_or_equal' => ':attribute必须是:date之后或相同的一个日期',
    'alpha' => ':attribute只能包含字母',
    'alpha_dash' => ':attribute只能包含字母、数字、中划线或下划线',
    'alpha_num' => ':attribute只能包含字母和数字',
    'array' => ':attribute必须是一个数组',
    'before' => ':attribute必须是:date之前的一个日期',
    'before_or_equal' => ':attribute必须是:date之前或相同的一个日期',
    'between' => [
        'numeric' => ':attribute必须在:min到:max之间',
        'file' => ':attribute必须在:min到:max kb之间',
        'string' => ':attribute必须在:min到:max个字符之间',
        'array' => ':attribute必须在:min到:max项之间',
    ],
    'boolean' => ':attribute字符必须是true或false,1或0',
    'confirmed' => ':attribute二次确认不匹配',
    'date' => ':attribute必须是一个合法的日期',
    'date_format' => ':attribute与给定的格式:format不符合',
    'different' => ':attribute必须不同于:other',
    'digits' => ':attribute必须是:digits位',
    'digits_between' => ':attribute必须在:min和:max位之间',
    'dimensions' => ':attribute具有无效的图片尺寸',
    'distinct' => ':attribute字段具有重复值',
    'email' => ':attribute必须是一个合法的电子邮件地址',
    'exists' => '选定的:attribute是无效的',
    'file' => ':attribute必须是一个文件',
    'filled' => ':attribute的字段是必填的',
    'gt' => [
        'numeric' => ':attribute必须大于:value',
        'file' => ':attribute必须大于:value kb',
        'string' => ':attribute必须大于:value个字符',
        'array' => ':attribute必须大于:value项',
    ],
    'gte' => [
        'numeric' => ':attribute必须大于等于:value',
        'file' => ':attribute必须大于等于:value kb',
        'string' => ':attribute必须大于等于:value个字符',
        'array' => ':attribute必须大于等于:value项',
    ],
    'image' => ':attribute必须是jpg,jpeg,png,bmp或者gif格式的图片',
    'in' => '选定的:attribute是无效的',
    'in_array' => ':attribute字段不存在于:other',
    'integer' => ':attribute必须是个整数',
    'ip' => ':attribute必须是一个合法的IP地址',
    'ipv4' => ':attribute必须是一个合法的IPv4地址',
    'ipv6' => ':attribute必须是一个合法的IPv6地址',
    'json' => ':attribute必须是一个合法的JSON字符串',
    'lt' => [
        'numeric' => ':attribute必须小于:value',
        'file' => ':attribute必须小于:value kb',
        'string' => ':attribute必须小于:value个字符',
        'array' => ':attribute必须小于:value项',
    ],
    'lte' => [
        'numeric' => ':attribute必须小于等于:value',
        'file' => ':attribute必须小于等于:value kb',
        'string' => ':attribute必须小于等于:value个字符',
        'array' => ':attribute必须小于等于:value项',
    ],
    'max' => [
        'numeric' => ':attribute的最大值为:max',
        'file' => ':attribute的最大为:max kb',
        'string' => ':attribute的最大长度为:max字符',
        'array' => ':attribute至多有:max项',
    ],
    'mimes' => ':attribute的文件类型必须是:values',
    'mimetypes' => ':attribute的文件MIME必须是:values',
    'min' => [
        'numeric' => ':attribute的最小值为:min',
        'file' => ':attribute大小至少为:min kb',
        'string' => ':attribute的最小长度为:min字符',
        'array' => ':attribute至少有:min项',
    ],
    'not_in' => '选定的:attribute是无效的',
    'not_regex' => ':attribute不能匹配给定的正则',
    'numeric' => ':attribute必须是数字',
    'present' => ':attribute字段必须存在',
    'regex' => ':attribute格式是无效的',
    'required' => ':attribute字段是必须的',
    'required_if' => ':attribute字段是必须的当:other是:value',
    'required_unless' => ':attribute字段是必须的，除非:other是在:values中',
    'required_with' => ':attribute字段是必须的当:values是存在的',
    'required_with_all' => ':attribute字段是必须的当:values是存在的',
    'required_without' => ':attribute字段是必须的当:values是不存在的',
    'required_without_all' => ':attribute字段是必须的当没有一个:values是存在的',
    'same' => ':attribute和:other必须匹配',
    'size' => [
        'numeric' => ':attribute必须是:size',
        'file' => ':attribute必须是:size kb',
        'string' => ':attribute必须是:size个字符',
        'array' => ':attribute必须包括:size项',
    ],
    'starts_with' => ':attribute必须以:values为开头',
    'string' => ':attribute必须是一个字符串',
    'timezone' => ':attribute必须是个有效的时区',
    'unique' => ':attribute已存在',
    'uploaded' => ':attribute上传失败',
    'url' => ':attribute无效的格式',
    'uuid' => ':attribute无效的UUID格式',
    'max_if' => [
        'numeric' => '当:other为:value时:attribute不能大于:max',
        'file' => '当:other为:value时:attribute不能大于:max kb',
        'string' => '当:other为:value时:attribute不能大于:max个字符',
        'array' => '当:other为:value时:attribute最多只有:max个单元',
    ],
    'min_if' => [
        'numeric' => '当:other为:value时:attribute必须大于等于:min',
        'file' => '当:other为:value时:attribute大小不能小于:min kb',
        'string' => '当:other为:value时:attribute至少为:min个字符',
        'array' => '当:other为:value时:attribute至少有:min个单元',
    ],
    'between_if' => [
        'numeric' => '当:other为:value时:attribute必须介于:min-:max之间',
        'file' => '当:other为:value时:attribute必须介于:min-:max kb之间',
        'string' => '当:other为:value时:attribute必须介于:min-:max个字符之间',
        'array' => '当:other为:value时:attribute必须只有:min-:max个单元',
    ],
    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */

    'attributes' => [],
    'phone_number' => ':attribute必须为一个有效的电话号码',
    'telephone_number' => ':attribute必须为一个有效的手机号码',

    'chinese_word' => ':attribute必须包含以下有效字符(中文/英文,数字,下划线)',
    'sequential_array' => ':attribute必须是一个有序数组',
];
