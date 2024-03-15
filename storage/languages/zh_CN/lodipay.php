<?php
return [
    'account_fail'=>'商户号错误',
    'account_recharge_store'=>[
        'mer_account'=>'商户号',
        'user_account'=>'GCASH账号',
        'sign'=>'sign',
        'order_no'=>'订单号',
        'amount'=>'充值金额',
        'currency'=>'币种',
        'pay_code'=>'payment code',
        'callback_url'=>'交易结果接收地址',
        'station_url'=>'Transfer site address',
    ],
    'account_recharge' => [
        'mer_account' => '商户号',
        'order_sn' => '订单号',
        'sign' => '签名',
    ],
    'order_not'=>'订单不存在',
    'order_pay_not_exist_or_completed' => '代付订单不存在或者已完成,请检查订单状态',
    'collection_order_not_exist_or_completed' => '代收订单不存在或者已完成,请检查订单状态',
    'collection_order_pay_not_exist_or_completed' => '代收拆弹订单不存在或者已完成，请检查订单状态',
    'pay_apply'=>[
        'mer_account'=>'商户号',
        'sign'=>'签名',
        'order_no'=>'订单号',
        'account_no' => 'GCASH账号',
        'username' => '用户名',
        'order_amount'=>'申请出款的资金',
        'currency'=>'币种',
        'callback_url'=>'交易结果接收地址',
    ],
    'pay_apply_check'=>[
        'mer_account'=>'商户号',
        'sign'=>'签名',
        'order_no'=>'订单号'
    ],
    'pay_balance_query'=>[
        'mer_account'=>'商户号',
        'sign'=>'签名'
    ],
    'pay_upload_sms' => [
        'mer_account'=>'商户号',
        'account_no' => 'GCASH账号',
        'message' => '消息',
        'sign'=>'签名'
    ],
    'pay_apply_fail'=>'代付失败',
    'pay_apply_exist'=>'订单已经存在',
    'confirm_Payment' => [
        'mer_account'=>'商户号',
        'sign'=>'签名',
        'order_no'=>'付款订单号',
        'orders_pay_sn' => '充值订单号'
    ],
    'server_error' => '系统错误，请联系客服',
    'callback_url_not_null'=>'回调地址不能为空',
    'callback_data_not_null'=>'回调数据不能为空',
    'merchant_transfer_insufficient_balance' => '商户提现余额不足',
    'merchant_recharge_insufficient_balance' => '商户充值余额不足',
    'callback_collection_order_not_exist' => '回调充值订单未找到',
    'callback_order_pay_not_exist' => '回调提现订单未找到',
];