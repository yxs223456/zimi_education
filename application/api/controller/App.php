<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-13
 * Time: 15:31
 */

namespace app\api\controller;

use app\common\AppException;
use app\common\enum\OperatingSystemEnum;
use app\common\enum\PackageChannelEnum;
use app\common\helper\UmengPush;
use app\common\model\DeviceFirstOpenLogModel;
use app\common\model\PackageChannelModel;
use app\common\model\PackageConfigModel;
use think\facade\Env;

class App extends Base
{
    protected $beforeActionList = [
        'checkAuth' => [
            'only' => 'share',
        ],
    ];

    public function channelPackageInfo()
    {
        $model = new PackageChannelModel();
        $data = $model->select();

        $returnData = [];
        foreach ($data as $item) {
            $returnData[] = [
                "channel" => $item["channel"],
                "package_link" => getHttpLink($item["package_link"]),
            ];
        }

        return $this->jsonResponse($returnData);
    }

    //检查更新
    public function checkUpdate()
    {
        $version = $this->query["v"];
        $os = $this->query["os"];
        if (empty($version)) {
            throw AppException::factory(AppException::COM_INVALID);
        }

        $packageModel = new PackageConfigModel();
        $currentPackage = $packageModel->findCurrentPackageByOs($os);

        if (empty($currentPackage)) {
            throw AppException::factory(AppException::COM_APP_NOT_ONLINE);
        }
        $currentVersion = $currentPackage["version"];
        $historyVersion = $packageModel->getAllPackageOrderByVersion($os);

        //初始化返回数据
        $returnData = [
            "current_version" => $currentVersion,
            "is_update" => 0,
            "forced" => 0,
            "package_link" => getHttpLink($currentPackage["package_link"]),
            "change_log" => $currentPackage["change_log"],
        ];

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
        }

        return $this->jsonResponse($returnData);
    }

    public function feedback()
    {
        return $this->jsonResponse(new \stdClass());
    }

    public function share()
    {
        $returnData = [
            "url" => "https://www.quwan.org.cn/",
        ];

        return $this->jsonResponse($returnData);
    }

    public function firstOpen()
    {
        $header = $this->request->header();
        $v = $header["v"]??"";
        $os = $header["os"]??"";
        $deviceId = $header["device-id"]??"";
        $channel = $header["channel"]??"";

        if (!empty($v) && !empty($os) && !empty($deviceId)) {
            $deviceFirstOpenModel = new DeviceFirstOpenLogModel();
            $log = [
                "device_id" => $deviceId,
                "channel" => $channel,
                "version" => $v,
                "os" => $os,
            ];
            $deviceFirstOpenModel->save($log);
        }

        return $this->jsonResponse(new \stdClass());
    }

    public function coinDescription()
    {
        $returnData["description"] = <<<DESC
DE是DE教育旗下的通用学习积分单位，用于参加各种竞技及大赛，是衡量学员能力的一种价值体现。DE教育后面会开创更多关于DE的价值。
DE的主要获取方式如下：
1、老学员可以邀请新学员注册，新学员在注册时填入老学员的学员号即可建立邀请关系，老学员获得20DE，新学员获得10DE，后续在个人信息补填有效。
2、学员通过准星级等级测试，不同关卡会给予不同DE奖励，第一级为1DE，每级递增1DE，最高给6DE奖励。
3、学员通过综合测试获得对应称号，一星为10DE，依次递增最高奖励60DE。
4、学员参加PK获得优异成绩奖励DE，数量根据PK难度和成绩排名而定，获取PK特殊称号有额外DE奖励。
5、学员参加DE大赛获取不同数量DE奖励，获取大赛特殊称号有额外DE奖励。
6、学员通过分享DE教育每次奖励2DE，上限每天5次。
7、学员通过每日签到获取DE奖励。
8、学员通过完善所有个人信息一次性奖励10DE。
9、学员参加官方举办的活动和完成各种任务奖励不同数量DE。
DESC;

        return $this->jsonResponse($returnData);
    }
}