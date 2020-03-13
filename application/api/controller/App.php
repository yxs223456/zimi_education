<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-13
 * Time: 15:31
 */

namespace app\api\controller;

use app\common\AppException;
use app\common\Constant;
use app\common\enum\OperatingSystemEnum;

class App extends Base
{
    protected $beforeActionList = [
        'checkAuth' => [
            'except' => 'checkUpdate',
        ],
    ];

    //检查更新
    public function checkUpdate()
    {
        $version = input("v");
        $os = input("os");
        if (empty($version)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        //初始化返回数据
        $returnData = [
            "current_version" => "v1.0.0",
            "is_update" => 0,
            "forced" => 0,
            "package_link" => "",
        ];

        //版本配置
        if (strtolower($os) == OperatingSystemEnum::IOS) {
            $currentVersion = Constant::PACKAGE_INFO["ios"]["current_version"];
            $historyVersion = Constant::PACKAGE_INFO["ios"]["history_version"];
            $returnData["package_link"] = config("web.self_domain") . "/static/package/ios.link";
        } elseif (strtolower($os) == OperatingSystemEnum::ANDROID) {
            $currentVersion = Constant::PACKAGE_INFO["android"]["current_version"];
            $historyVersion = Constant::PACKAGE_INFO["android"]["history_version"];
            $returnData["package_link"] = config("web.self_domain") . "/static/package/android.link";
        } else {
            return $this->jsonResponse($returnData);
        }

        //比较当前版本是否一致
        if (version_compare($version, $currentVersion, "<")) { //客户端版本小于当前版本
            foreach ($historyVersion as $v) {
                if (version_compare($version, $v["version"], ">=")) {
                    break;
                }
                if ($v["forced"] == true) {
                    $returnData["forced"] = 1;
                    break;
                }
            }

            $returnData["is_update"] = 1;
        } else {
            $returnData["current_version"] = $currentVersion;
        }
        $returnData["current_version"] = $currentVersion;

        return $this->jsonResponse($returnData);
    }
}