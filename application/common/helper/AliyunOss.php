<?php
/**
 * Created by PhpStorm.
 * User: yangxs
 * Date: 2018/10/18
 * Time: 10:29
 */
namespace app\common\helper;

include_once(\Env::get('root_path') . 'extend/aliyun-oss-php-sdk-master/autoload.php');

use OSS\OssClient;
use OSS\Core\OssException;
use app\common\helper\Redis;

class AliyunOss
{
    protected static $accessKeyId = '';
    protected static $accessKeySecret = '';
    protected static $endpoint = '';
    protected static $host = '';
    protected static $bucket = '';

    /**
     * @param $filename string 文件名称
     * @param string $content The content object
     * @return mixed
     * @throws OssException
     */
    public static function putObject($filename, $content)
    {
        $accessKeyId = self::$accessKeyId;
        $accessKeySecret = self::$accessKeySecret;
        $endpoint = self::$endpoint;
        // 存储空间名称
        $bucket= self::$bucket;

        $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
        $result = $ossClient->putObject($bucket, $filename, $content);
        $rs = [
            'url' => $result['info']['url']
        ];
        return $rs;
    }

    public static function postObjectUploadInfo()
    {
        $redis = Redis::factory();

        $ossPostObjectUploadParams = $redis->get("ossPostObjectUploadParams");

        if(empty($ossPostObjectUploadParams)) {
            $OSSAccessKeyId = self::$accessKeyId;
            $policy = base64_encode(json_encode([
                'expiration' => gmt_iso8601(time() + 10),
                'conditions' => [
                    ["bucket"=>self::$bucket],
                ],
            ]));

            $Signature = base64_encode(hash_hmac('sha1', $policy, self::$accessKeySecret, true));

            $ossPostObjectUploadParams = json_encode([
                'OSSAccessKeyId' => $OSSAccessKeyId,
                'policy' => $policy,
                'Signature' => $Signature,
                'host' => self::$host,
            ], JSON_UNESCAPED_UNICODE);

            $redis->set("ossPostObjectUploadParams", $ossPostObjectUploadParams, 5);
        }

        return json_decode($ossPostObjectUploadParams, true);
    }
}