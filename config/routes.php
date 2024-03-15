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

use Hyperf\HttpServer\Router\Router,
    App\Middleware\Pay\PayMiddleware,
    App\Middleware\IpAuthMiddleware,
    App\Middleware\H5CollectionMiddleware;

Router::get('/favicon.ico', function () {
    return '';
});

Router::addGroup('/api', function () {
        Router::get('/index', 'App\Controller\IndexController@index');
        Router::addGroup('/kpay', function () {
                //充值接口
                Router::addGroup('/recharge', function () {
                        Router::post('/list', 'App\Controller\LodiPay\Recharge\RechargeController@index');
                        Router::post('', 'App\Controller\LodiPay\Recharge\RechargeController@store');
                        Router::post('/upload_cert', 'App\Controller\LodiPay\Recharge\RechargeController@uploadCert');
                        Router::post('/show_cert', 'App\Controller\LodiPay\Recharge\RechargeController@showCert');
                        Router::post('/show_match', 'App\Controller\LodiPay\Recharge\RechargeController@showMatch');
                        Router::post('/cancel', 'App\Controller\LodiPay\Recharge\RechargeController@cancel');
                    }
                );
                //提款接口
                Router::addGroup('/pay', function () {
                        Router::post('/apply', 'App\Controller\LodiPay\Pay\PayController@apply');
                        Router::post('/apply_check', 'App\Controller\LodiPay\Pay\PayController@applyCheck');
                        Router::post('/balance_query', 'App\Controller\LodiPay\Pay\PayController@balanceQuery');
                        Router::post('/upload_sms', 'App\Controller\LodiPay\Pay\PayController@uploadSms');
                        Router::post('/confirm_status', 'App\Controller\LodiPay\Pay\PayController@confirmStatus');
                    }
                );
            },
            ['middleware' => [IpAuthMiddleware::class, PayMiddleware::class]]
        );
        //h5充值匹配
        Router::addGroup('/kpay/h5', function () {
                Router::get('/payee_info', 'App\Controller\LodiPay\Recharge\H5CollectionController@payeeInfo');
                Router::post('/send_credentials', 'App\Controller\LodiPay\Recharge\H5CollectionController@uploadCert');
                Router::get('/show_credentials', 'App\Controller\LodiPay\Recharge\H5CollectionController@showCert');
                Router::post('/upload', 'App\Controller\LodiPay\Recharge\H5CollectionController@upload');
            },
            ['middleware' => [H5CollectionMiddleware::class]]
        );
        Router::post('/kpay/h5/cancel_recharge', 'App\Controller\LodiPay\Recharge\H5CollectionController@cancelRecharge');
        Router::addGroup('/backend', function () {
                Router::post('/order/change_status', 'App\Controller\BackendApi\OrderController@changeStatus');
                Router::post('/order/reject', 'App\Controller\BackendApi\OrderController@reject');
                Router::post('/order/upload_cert', 'App\Controller\BackendApi\OrderController@uploadCert');
                Router::post('/order/pay', 'App\Controller\BackendApi\PayController@apply');
                Router::post('/sync_cache/admin_config', 'App\Controller\BackendApi\SyncCacheController@adminConfig');
                Router::post('/sync_cache/pay_config', 'App\Controller\BackendApi\SyncCacheController@payConfig');
                Router::post('/sync_cache/merchant_secret', 'App\Controller\BackendApi\SyncCacheController@merchantSecret');
                Router::post('/sync_cache/merchant', 'App\Controller\BackendApi\SyncCacheController@merchant');
            },
            ['middleware' => [\App\Middleware\BackendApiMiddleware::class]]
        );
        Router::post('/apepay/{merchant_account}/callback', 'App\Controller\Callback\ApePayController@callbackResult');
        Router::post('/bpay/{merchant_account}/callback', 'App\Controller\Callback\BPayController@callbackResult');
        Router::post('/caipay/{merchant_account}/callback', 'App\Controller\Callback\CaiPayController@callbackResult');
        Router::post('/cloudpay/{merchant_account}/callback', 'App\Controller\Callback\CloudPayController@callbackResult');
        Router::post('/gtrpay/{merchant_account}/callback', 'App\Controller\Callback\GtrPayController@callbackResult');
        Router::post('/hpay/{merchant_account}/callback', 'App\Controller\Callback\HPayController@callbackResult');
        Router::post('/htpay/{merchant_account}/callback', 'App\Controller\Callback\HtPayController@callbackResult');
        Router::post('/lpay/{inner_order_sn}/callback', 'App\Controller\Callback\LPayController@callbackResult');
        Router::post('/mbpay/{merchant_account}/callback', 'App\Controller\Callback\MbPayController@callbackResult');
        Router::post('/qgpay/{merchant_account}/callback', 'App\Controller\Callback\QgPayController@callbackResult');
        Router::post('/qxpay/{merchant_account}/callback', 'App\Controller\Callback\QxPayController@callbackResult');
        Router::post('/rpay/{merchant_account}/callback', 'App\Controller\Callback\RPayController@callbackResult');
        Router::post('/tigermayapay/{merchant_account}/callback', 'App\Controller\Callback\TigerMayaPayController@callbackResult');
        Router::post('/tigerpay/{merchant_account}/callback', 'App\Controller\Callback\TigerPayController@callbackResult');
        Router::post('/ubpay/{merchant_account}/callback', 'App\Controller\Callback\UbPayController@callbackResult');
        Router::post('/vcpay/{merchant_account}/callback', 'App\Controller\Callback\VcPayController@callbackResult');
        Router::post('/yypay/{merchant_account}/callback', 'App\Controller\Callback\YyPayController@callbackResult');

    }
);
