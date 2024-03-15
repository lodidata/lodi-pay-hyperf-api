<?php

declare(strict_types=1);

namespace App\Support;

use OSS\OssClient;

class AliOss
{
    private $ossClient;

    public function __construct()
    {
        $accessKeyId = env('ALIOSS_KEY_ID');
        $accessKeySecret = env('ALIOSS_SECRET');
        $endpoint = env('ALIOSS_ENDPOINT');
        $this->ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
    }


    public function uploadFile($fileName, $content): array
    {
        $bucket = env('ALIOSS_BUCKNAME');
        $fileName = env('ALI_DIR', 'lodipay-test') . '/' . $fileName;
        $fileName = str_replace('//', '/', $fileName);
        $res = $this->ossClient->uploadFile($bucket, $fileName, $content);
        if (isset($res['info']) && isset($res['info']['url'])) {
            $url = $res['info']['url'];
        } else {
            $url = '';
        }
        return [$url,  '/' . $fileName];
    }
}