<?php
/**
 * Created by PhpStorm.
 * User: yangxs
 * Date: 2018/10/18
 * Time: 10:29
 */
namespace app\common\helper;

use OSS\OssClient;
use OSS\Core\OssException;

class AliyunOss
{
    /**
     * @param $filename string 文件名称
     * @param string $content The content object
     * @return mixed
     * @throws OssException
     */
    public static function putObject($filename, $content)
    {
        $ossConfig = config("account.oss");
        $accessKeyId = $ossConfig["key_id"];
        $accessKeySecret = $ossConfig["key_secret"];
        $endpoint = $ossConfig["endpoint"];
        $bucket= $ossConfig["bucket"];

        $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
        $result = $ossClient->putObject($bucket, $filename, $content);

        if (isset($result['info']['url']) && strpos($result['info']['url'], "https") === false) {
            $result['info']['url'] = str_replace("http", "https", $result['info']['url']);
            return $result['info']['url'];
        } else {
            return "";
        }
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