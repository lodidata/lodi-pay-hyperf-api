<?php

declare(strict_types=1);

namespace App\Support;

use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use Aws\S3\MultipartUploader;

class AwsOss
{
    private $aws_s3;

    public function __construct()
    {
        $accessKeyId = env('AWSOSS_KEY_ID');
        $accessKeySecret = env('AWSOSS_SECRET');
        $region = env('AWSOSS_REGION');
        $debug = env('AWSOSS_DEBUG');
        $credentials = new Credentials($accessKeyId, $accessKeySecret);
        //s3客户端
        $this->aws_s3 = new S3Client([
            'version' => 'latest',
            //地区 亚太区域（新加坡）    AWS区域和终端节点： http://docs.amazonaws.cn/general/latest/gr/rande.html
            'region' => $region,
            //加载证书
            'credentials' => $credentials,
            //开启bug调试
            'debug' => $debug
        ]);
    }


    public function uploadFile($fileName, $content): array
    {
        $bucket = env('AWSOSS_BUCKNAME');
        $fileName = env('DIR', 'lodipay-test') . '/' . $fileName;
        $fileName = str_replace('//', '/', $fileName);

        try {
            $uploader = new MultipartUploader($this->aws_s3, $content, [
                //存储桶
                'bucket' => $bucket,
                //上传后的新地址
                'key' => $fileName,
                //设置访问权限  公开,不然访问不了
                'ACL' => 'public-read',
                //分段上传
                'before_initiate' => function (\Aws\Command $command) {
                    $command['CacheControl'] = 'max-age=3600';
                },
                'before_upload' => function (\Aws\Command $command) {
                    $command['RequestPayer'] = 'requester';
                },
                'before_complete' => function (\Aws\Command $command) {
                    $command['RequestPayer'] = 'requester';
                },
            ]);

            $result = $uploader->upload();
            $domain = env('AWSOSS_DOMAIN');
            return [$domain . '/' . $fileName, '/'.$fileName];
        } catch (\Throwable $e) {
            logger()->error('aws上传失败', [$e->getMessage(),'/'.$fileName]);
            return ['', '/'.$fileName];
        }
    }
}