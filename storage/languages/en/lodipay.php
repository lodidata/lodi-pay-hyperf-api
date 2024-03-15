<?php
return [
    'account_fail'=>'Merchant account error',
    'account_recharge_store'=>[
        'mer_account'=>'account',
        'user_account'=>'Gcash account',
        'sign'=>'sign',
        'order_no'=>'order no.',
        'amount'=>'transaction amount',
        'currency'=>'transaction currency',
        'pay_code'=>'payment code',
        'callback_url'=>'callback url',
        'station_url'=>'Transfer site address',
    ],
    'account_recharge_get'=>[
        'mer_account'=>'account',
        'sign'=>'sign',
        'mer_no'=>'platform order no.',
        'order_no'=>'order no.',
        'method'=>'communication type'
    ],
    'insert_order'=>'Failed to add an order',
    'order_not' => 'Order does not exist',
    'order_pay_not_exist_or_completed' => 'Pay order does not exist or has been completed',
    'collection_order_not_exist_or_completed' => 'collection order does not exist or has been completed',
    'collection_order_pay_not_exist_or_completed' => 'collection order pay not exist or completed',
    'pay_apply'=>[
        'mer_account'=>'account',
        'sign'=>'sign',
        'order_no'=>'order no.',
        'account_no' => 'Gcash account',
        'username' => 'username',
        'order_amount'=>'transaction amount',
        'currency'=>'transaction currency',
        'callback_url'=>'callback url',
    ],
    'pay_apply_check'=>[
        'mer_account'=>'account',
        'sign'=>'sign',
        'order_no'=>'order no.'
    ],
    'pay_balance_query'=>[
        'mer_account'=>'account',
        'sign'=>'sign'
    ],
    'pay_upload_sms' => [
        'mer_account'=>'account',
        'account_no' => 'Gcash account',
        'message' => 'message',
        'sign'=>'sign'
    ],
    'pay_apply_fail'=>'pay apply fail',
    'pay_apply_exist'=>'Order already exists',
    'confirm_Payment' => [
        'mer_account'=>'account',
        'sign'=>'sign',
        'order_no'=>'pay order number',
        'sub_orders_pay_sn'=>'sub order number',
        'orders_pay_sn' => 'orders pay sn'
    ],
    'server_error' => 'server error',
    'h5_upload'=>[
        'sign'=>'sign',
        'mer_no'=>'platform order no.',
        'img_url'=>'Upload credentials',
        'remark'=>'Remittance instructions',
    ],
    'h5_public'=>[
        'sign'=>'sign',
        'mer_no'=>'platform order no.',
    ],
    'callback_url_not_null'=>'callback url not null',
    'callback_data_not_null'=>'callback data not null',
    'merchant_transfer_insufficient_balance' => 'merchant transfer insufficient balance',
    'merchant_recharge_insufficient_balance' => 'merchant recharge insufficient balance',
    'callback_collection_order_not_exist' => 'callback collection order not exist',
    'callback_order_pay_not_exist' => 'callback order pay not exist',
];