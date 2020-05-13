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

    public function sendAndroidUnicast($userUuid, $title, $content)
    {
        try {
            $customizedcast = new \AndroidCustomizedcast();
            $customizedcast->setAppMasterSecret($this->appMasterSecret);
            $customizedcast->setPredefinedKeyValue("appkey",           $this->appkey);
            $customizedcast->setPredefinedKeyValue("timestamp",        $this->timestamp);
            // Set your alias here, and use comma to split them if there are multiple alias.
            // And if you have many alias, you can also upload a file containing these alias, then
            // use file_id to send customized notification.
            $customizedcast->setPredefinedKeyValue("alias",            $userUuid);
            $customizedcast->setPredefinedKeyValue("alias_type",       "DE_education");
            $custom = json_encode([
                "is_single_user"=>true,
                "userid"=>$userUuid,
            ]);
            $customizedcast->setPredefinedKeyValue("custom",       $custom);
            $customizedcast->setPredefinedKeyValue("mipush",       true);
            $customizedcast->setPredefinedKeyValue("mi_activity", "com.zimi.study.module.push.UmengClickActivity");

            $customizedcast->setPredefinedKeyValue("ticker",           "Android customizedcast ticker");
            $customizedcast->setPredefinedKeyValue("title",            $title);
            $customizedcast->setPredefinedKeyValue("text",             $content);
            $customizedcast->setPredefinedKeyValue("after_open",       "go_app");
            return $customizedcast->send();
        } catch (\Throwable $e) {
            return ("Caught exception: " . $e->getMessage());
        }
    }

    public function sendIOSUnicast($userUuid, $content) {
        try {
            $customizedcast = new \IOSCustomizedcast();
            $customizedcast->setAppMasterSecret($this->appMasterSecret);
            $customizedcast->setPredefinedKeyValue("appkey",           $this->appkey);
            $customizedcast->setPredefinedKeyValue("timestamp",        $this->timestamp);

            // Set your alias here, and use comma to split them if there are multiple alias.
            // And if you have many alias, you can also upload a file containing these alias, then
            // use file_id to send customized notification.
            $customizedcast->setPredefinedKeyValue("alias",            "userid");
            // Set your alias_type here
            $customizedcast->setPredefinedKeyValue("alias_type",       "DE_education");

            $customizedcast->setPredefinedKeyValue("userid",       $userUuid);
            $customizedcast->setPredefinedKeyValue("alert", "IOS 个性化测试");
            $customizedcast->setPredefinedKeyValue("badge", 0);
            $customizedcast->setPredefinedKeyValue("sound", "chime");
            // Set 'production_mode' to 'true' if your app is under production mode
            $customizedcast->setPredefinedKeyValue("production_mode", "false");
            return $customizedcast->send();
        } catch (\Throwable $e) {
            return ("Caught exception: " . $e->getMessage());
        }
    }
}