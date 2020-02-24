<?php
// +----------------------------------------------------------------------
// | AliOss
// +----------------------------------------------------------------------
// +----------------------------------------------------------------------
namespace taobao;
use OSS\OssClient;
use OSS\Core\OssException;

class AliOss {

    private $ossClient;

    /**
     * 构造函数
     * Oss constructor.
     */
    private function __construct() {
        try {
            $this->ossClient = new OssClient(config("oss.KeyId"), config("oss.KeySecret"), config("oss.Endpoint"), config("oss.UseCname"));
        } catch (OssException $e) {
            print $e->getMessage();
        }
    }

    public static function uploadFile($filePath,$object,$bucket="") {
        try {
            $oss = new AliOss();
            return $oss->ossClient->uploadFile(isNullOrEmpty($bucket) ? config("oss.Bucket") : $bucket, $object, $filePath);
        } catch (OssException $e) {
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            return false;
        }
    }

    public static function uploadContent($content,$object,$bucket="") {
        try {
            $oss = new AliOss();
            return $oss->ossClient->putObject("lin-shoes", $object, $content);
        } catch (OssException $e) {
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            return false;
        }
    }

    public static function getFileUrl($object) {
        $url = 'http://' .config("oss.Cname"). '/' .$object;
        return $url;
    }

}