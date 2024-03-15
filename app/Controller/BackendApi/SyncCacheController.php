<?php

declare(strict_types=1);

namespace App\Controller\BackendApi;

use App\Controller\AbstractController;

class SyncCacheController extends AbstractController
{
    public function adminConfig()
    {
        redis()->del('admin_config');
        return $this->response->success();
    }

    public function payConfig()
    {
        $params = $this->request->getParsedBody();
        $merchant_account = $params['merchant_account'];
        if (empty($merchant_account)) return $this->response->fail('merchant_account can not be empty');

        redis()->del('pay_config:'.$merchant_account);
        return $this->response->success();
    }

    public function merchantSecret()
    {
        $params = $this->request->getParsedBody();
        $merchant_id = $params['merchant_id'];
        if (empty($merchant_id)) return $this->response->fail('merchant_id can not be empty');

        redis()->del('merchant_secret:'.$merchant_id);
        return $this->response->success();
    }

    public function merchant()
    {
        $params = $this->request->getParsedBody();
        $merchant_account = $params['merchant_account'];
        if (empty($merchant_account)) return $this->response->fail('merchant_account can not be empty');

        redis()->del('merchant:'.$merchant_account);
        return $this->response->success();
    }
}