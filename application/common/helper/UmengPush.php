<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-05-11
 * Time: 11:35
 */
namespace app\common\helper;

use app\common\enum\ActivityNewsTargetPageTypeEnum;
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
        $this->timestamp = strval(time());
    }

    public function sendAndroidUnicast($userUuid, $title, $content)
    {
        try {
            $umengConfig = config("account.android_umeng_push");
            $this->appkey = $umengConfig["app_key"];
            $this->appMasterSecret = $umengConfig["app_master_secret"];
            $customizedcast = new \AndroidCustomizedcast();
            $customizedcast->setAppMasterSecret($this->appMasterSecret);
            $customizedcast->setPredefinedKeyValue("appkey",           $this->appkey);
            $customizedcast->setPredefinedKeyValue("timestamp",        $this->timestamp);
            // Set your alias here, and use comma to split them if there are multiple alias.
            // And if you have many alias, you can also upload a file containing these alias, then
            // use file_id to send customized notification.
            $customizedcast->setPredefinedKeyValue("alias",            $userUuid);
            $customizedcast->setPredefinedKeyValue("alias_type",       "DE_education");
            $customizedcast->setPredefinedKeyValue("production_mode", "true");
            $custom = [
                "is_single_user" => true,
                "module" => "system_message",
                "userid" => $userUuid,
            ];
            $customizedcast->setPredefinedKeyValue("custom",       $custom);
            $customizedcast->setPredefinedKeyValue("mipush",       true);
            $customizedcast->setPredefinedKeyValue("mi_activity", "com.zimi.study.module.push.UmengClickActivity");

            $customizedcast->setPredefinedKeyValue("ticker",           "Android customizedcast ticker");
            $customizedcast->setPredefinedKeyValue("title",            $title);
            $customizedcast->setPredefinedKeyValue("text",             $content);
            $customizedcast->setPredefinedKeyValue("after_open",       "go_custom");
            return $customizedcast->send();
        } catch (\Throwable $e) {
            return "Caught exception: " . $e->getMessage();
        }
    }

    public function sendIOSUnicast($userUuid, $content) {
        try {
            $umengConfig = config("account.ios_umeng_push");
            $this->appkey = $umengConfig["app_key"];
            $this->appMasterSecret = $umengConfig["app_master_secret"];
            $customizedcast = new \IOSCustomizedcast();
            $customizedcast->setAppMasterSecret($this->appMasterSecret);
            $customizedcast->setPredefinedKeyValue("appkey",           $this->appkey);
            $customizedcast->setPredefinedKeyValue("timestamp",        $this->timestamp);

            // Set your alias here, and use comma to split them if there are multiple alias.
            // And if you have many alias, you can also upload a file containing these alias, then
            // use file_id to send customized notification.
            $customizedcast->setPredefinedKeyValue("alias",           $userUuid);
            // Set your alias_type here
            $customizedcast->setPredefinedKeyValue("alias_type",       "DE_education");

            $custom = json_encode([
                "is_single_user"=>true,
                "module" => "system_message",
                "userid"=>$userUuid,
            ]);
            $customizedcast->setPredefinedKeyValue("custom",       $custom);
            $customizedcast->setPredefinedKeyValue("alert", $content);
            $customizedcast->setPredefinedKeyValue("badge", 0);
            $customizedcast->setPredefinedKeyValue("sound", "chime");
            // Set 'production_mode' to 'true' if your app is under production mode
            $customizedcast->setPredefinedKeyValue("production_mode", "true");
            return $customizedcast->send();
        } catch (\Throwable $e) {
            return "Caught exception: " . $e->getMessage();
        }
    }

    public function sendAndroidBroadcast($title, $content, $targetPageType, array $pageConfig)
    {
        try {
            $umengConfig = config("account.android_umeng_push");
            $this->appkey = $umengConfig["app_key"];
            $this->appMasterSecret = $umengConfig["app_master_secret"];
            $brocast = new \AndroidBroadcast();
            $brocast->setAppMasterSecret($this->appMasterSecret);
            $brocast->setPredefinedKeyValue("appkey",           $this->appkey);
            $brocast->setPredefinedKeyValue("timestamp",        $this->timestamp);
            $brocast->setPredefinedKeyValue("ticker",           "Android broadcast ticker");
            $brocast->setPredefinedKeyValue("title",            $title);
            $brocast->setPredefinedKeyValue("text",             $content);
            // Set 'production_mode' to 'false' if it's a test device.
            // For how to register a test device, please see the developer doc.
            $brocast->setPredefinedKeyValue("production_mode", "true");

            switch ($targetPageType) {
                case ActivityNewsTargetPageTypeEnum::H5:
                    $custom = [
                        "is_single_user" => false,
                        "module" => "activity_message",
                        "target_page_type" => $targetPageType,
                        "after_open" => "open_h5",
                        "h5_page_params" => [
                            "title" => $pageConfig["title"],
                            "url" => $pageConfig["url"]
                        ],
                    ];
                    $brocast->setPredefinedKeyValue("after_open",       "go_custom");
                    break;
                default:
                    $custom = [
                        "is_single_user" => false,
                        "module" => "activity_message",
                        "target_page_type" => $targetPageType,
                        "after_open" => "open_app",
                    ];
                    $brocast->setPredefinedKeyValue("after_open",       "go_custom");
                    break;

            }
            $brocast->setPredefinedKeyValue("custom",       $custom);
            $brocast->setPredefinedKeyValue("mipush",       true);
            $brocast->setPredefinedKeyValue("mi_activity", "com.zimi.study.module.push.UmengClickActivity");

            return $brocast->send();
        } catch (\Throwable $e) {
            return "Caught exception: " . $e->getMessage();
        }
    }

    function sendIOSBroadcast($content, $targetPageType, array $pageParams) {
        try {
            $umengConfig = config("account.ios_umeng_push");
            $this->appkey = $umengConfig["app_key"];
            $this->appMasterSecret = $umengConfig["app_master_secret"];
            $brocast = new \IOSBroadcast();
            $brocast->setAppMasterSecret($this->appMasterSecret);
            $brocast->setPredefinedKeyValue("appkey",           $this->appkey);
            $brocast->setPredefinedKeyValue("timestamp",        $this->timestamp);

            $brocast->setPredefinedKeyValue("alert", $content);
            $brocast->setPredefinedKeyValue("badge", 0);
            $brocast->setPredefinedKeyValue("sound", "chime");
            // Set 'production_mode' to 'true' if your app is under production mode
            $brocast->setPredefinedKeyValue("production_mode", "false");

            switch ($targetPageType) {
                case ActivityNewsTargetPageTypeEnum::H5:
                    $custom = [
                        "is_single_user" => false,
                        "module" => "activity_message",
                        "target_page_type" => $targetPageType,
                        "after_open" => "open_h5",
                        "h5_page_params" => [
                            "title" => $pageParams["title"],
                            "url" => $pageParams["url"]
                        ],
                    ];
                    break;
                default:
                    $custom = [
                        "is_single_user" => false,
                        "module" => "activity_message",
                        "target_page_type" => $targetPageType,
                        "after_open" => "open_app",
                    ];
                    break;

            }
            $brocast->setPredefinedKeyValue("custom",       $custom);

            return $brocast->send();
        } catch (\Throwable $e) {
            return "Caught exception: " . $e->getMessage();
        }
    }
}