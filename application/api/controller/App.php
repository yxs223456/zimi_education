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
use app\common\model\PackageConfigModel;

class App extends Base
{
    protected $beforeActionList = [
        'checkAuth' => [
            'except' => 'submitPackage,checkUpdate,feedback',
        ],
    ];

    public function submitPackage()
    {
        $os = input("os", "");
        $version = input("version", "");
        $forced = (int) input("forced");
        $packageLink = input("package_link", "");
        $changeLog = input("change_log", "");

        if (!in_array($os, [OperatingSystemEnum::ANDROID, OperatingSystemEnum::IOS]) ||
            empty($version) ||
            !in_array($forced, [0, 1]) ||
            empty($packageLink) ||
            empty($changeLog)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $packageModel = new PackageConfigModel();
        $package = $packageModel->findByOsAndVersion($os, $version);
        if ($package) {
            //版本存在更新版本信息
            $package->forced = $forced;
            $package->package_link = $packageLink;
            $package->change_log = $changeLog;
            $package->update_time = time();
            $package->save();
        } else {
            //版本不存在添加版本信息
            $data = [
                "os" => $os,
                "version" => $version,
                "forced" => $forced,
                "package_link" => $packageLink,
                "change_log" => $changeLog,
                "create_time" => time(),
                "update_time" => time(),
            ];
            $packageModel->insert($data);
        }

        return $this->jsonResponse(new \stdClass());
    }

    //检查更新
    public function checkUpdate()
    {
        $version = $this->query["v"];
        $os = $this->query["os"];
        if (empty($version)) {
            throw AppException::factory(AppException::COM_INVALID);
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

    public function feedback()
    {
        return $this->jsonResponse(new \stdClass());
    }
}