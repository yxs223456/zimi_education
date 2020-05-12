<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-05-11
 * Time: 11:35
 */
namespace app\common\helper;

use think\facade\Env;

include_once(Env::get('root_path') . 'extend/umeng-push/src/notification/android/AndroidBroadcast.php');
include_once(Env::get('root_path') . 'extend/umeng-push/src/notification/android/AndroidFilecast.php');
include_once(Env::get('root_path') . 'extend/umeng-push/src/notification/android/AndroidGroupcast.php');
include_once(Env::get('root_path') . 'extend/umeng-push/src/notification/android/AndroidUnicast.php');
include_once(Env::get('root_path') . 'extend/umeng-push/src/notification/android/AndroidCustomizedcast.php');
include_once(Env::get('root_path') . 'extend/umeng-push/src/notification/ios/IOSBroadcast.php');
include_once(Env::get('root_path') . 'extend/umeng-push/src/notification/ios/IOSFilecast.php');
include_once(Env::get('root_path') . 'extend/umeng-push/src/notification/ios/IOSGroupcast.php');
include_once(Env::get('root_path') . 'extend/umeng-push/src/notification/ios/IOSUnicast.php');
include_once(Env::get('root_path') . 'extend/umeng-push/src/notification/ios/IOSCustomizedcast.php');


class UmengPush
{
    protected $appkey           = NULL;
    protected $appMasterSecret     = NULL;
    protected $timestamp        = NULL;
    protected $validation_token = NULL;

    public function __construct() {
        $umengConfig = config("account.umeng_push");
        $this->appkey = $umengConfig["app_key"];
        $this->appMasterSecret = $umengConfig["app_master_secret"];
        $this->timestamp = strval(time());
    }

    public function sendAndroidUnicast($deviceToken, $title, $content) {
        try {
            $unicast = new \AndroidUnicast();
            $unicast->setAppMasterSecret($this->appMasterSecret);
            $unicast->setPredefinedKeyValue("appkey",           $this->appkey);
            $unicast->setPredefinedKeyValue("timestamp",        $this->timestamp);
            // Set your device tokens here
            $unicast->setPredefinedKeyValue("mipush",    true);
            $unicast->setPredefinedKeyValue("mi_activity",    "com.zimi.study.module.push.UmengClickActivity");
            $unicast->setPredefinedKeyValue("device_tokens",    $deviceToken);
            $unicast->setPredefinedKeyValue("ticker",           "Android unicast ticker");
            $unicast->setPredefinedKeyValue("title",            $title);
            $unicast->setPredefinedKeyValue("text",             $content);
            $unicast->setPredefinedKeyValue("after_open",       "go_app");
            // Set 'production_mode' to 'false' if it's a test device.
            // For how to register a test device, please see the developer doc.
            $unicast->setPredefinedKeyValue("production_mode", "true");
            // Set extra fields
            $unicast->setExtraField("test", "helloworld");
            $data = $unicast->send();
            print ($data . "\r\n");
            return $data;
        } catch (\Throwable $e) {
            print("Caught exception: " . $e->getMessage());
        }
    }

    public function sendIOSUnicast($deviceToken, $content) {
        try {
            $unicast = new \IOSUnicast();
            $unicast->setAppMasterSecret($this->appMasterSecret);
            $unicast->setPredefinedKeyValue("appkey",           $this->appkey);
            $unicast->setPredefinedKeyValue("timestamp",        $this->timestamp);
            // Set your device tokens here
            $unicast->setPredefinedKeyValue("device_tokens",    $deviceToken);
            $unicast->setPredefinedKeyValue("alert", $content);
            $unicast->setPredefinedKeyValue("badge", 0);
            $unicast->setPredefinedKeyValue("sound", "chime");
            // Set 'production_mode' to 'true' if your app is under production mode
            $unicast->setPredefinedKeyValue("production_mode", "true");
            // Set customized fields
            $unicast->setCustomizedField("test", "helloworld");
            $data = $unicast->send();
            print ($data . "\r\n");
            return $data;
        } catch (\Throwable $e) {
            print("Caught exception: " . $e->getMessage());
        }
    }
}