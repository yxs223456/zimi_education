<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-12
 * Time: 20:43
 */

namespace app\api\service;

use app\common\Constant;
use app\common\enum\UserCoinAddTypeEnum;
use app\common\helper\Redis;
use app\common\model\UserCoinLogModel;

class TaskService extends Base
{
    public function list($user)
    {
        $userUuid = $user["uuid"];
        $userCoinLogModel = new UserCoinLogModel();
        $userService = new UserService();
        $redis = Redis::factory();

        //初始化返回数据
        $returnData = [
            "user_info" => [
                "is_finish" => (int) $userService->checkUserInfoComplete($user),
                "reward_coin" => Constant::TASK_COIN_NUM["user_info"],
                "is_receive" => 0,
            ],
            "parent_invite_code" => [
                "is_finish" => (int) !!$user["parent_invite_code"],
                "reward_coin" => Constant::TASK_COIN_NUM["parent_invite_code"],
                "is_receive" => 0,
            ],
            "bind_we_chat" => [
                "is_finish" => (int) !!$user["mobile_openid"],
                "reward_coin" => Constant::TASK_COIN_NUM["bind_we_chat"],
                "is_receive" => 0,
            ],
            "share" => [
                "daily_times" => Constant::TASK_SHARE_DAILY_TIMES,
                "reward_coin" => Constant::TASK_COIN_NUM["share"],
                "today_finish_times" => 0,
            ],
        ];

        //一次性任务完成情况
        $oneTimeTasks = [
            UserCoinAddTypeEnum::USER_INFO,
            UserCoinAddTypeEnum::PARENT_INVITE_CODE,
            UserCoinAddTypeEnum::BIND_WE_CHAT,
        ];

        $userOneTimeTaskInfo = $userCoinLogModel->getByUserUuidAndAddTypes($userUuid, $oneTimeTasks);
        foreach ($userOneTimeTaskInfo as $item) {
            switch ($item["add_type"]) {
                case UserCoinAddTypeEnum::USER_INFO:
                    $returnData["user_info"]["is_receive"] = 1;
                    break;
                case UserCoinAddTypeEnum::PARENT_INVITE_CODE:
                    $returnData["parent_invite_code"]["is_receive"] = 1;
                    break;
                case UserCoinAddTypeEnum::BIND_WE_CHAT:
                    $returnData["bind_we_chat"]["is_receive"] = 1;
                    break;
            }
        }

        //今日通过分享获取书币次数
        $returnData["share"]["today_finish_times"] = userGetCoinByShareTimes($userUuid, $redis);

        return $returnData;
    }

    public function receiveCoin($user, $type)
    {
        switch ($type) {
            case UserCoinAddTypeEnum::USER_INFO:
                $reward = Constant::TASK_COIN_NUM["user_info"];
                break;
            case UserCoinAddTypeEnum::PARENT_INVITE_CODE:
                $reward = Constant::TASK_COIN_NUM["parent_invite_code"];
                break;
            case UserCoinAddTypeEnum::BIND_WE_CHAT:
                $reward = Constant::TASK_COIN_NUM["bind_we_chat"];
                break;
            case UserCoinAddTypeEnum::SHARE:
                $userCoinLogModel = new UserCoinLogModel();
                $finishCount = $userCoinLogModel->todayCountFromShare($user["uuid"]);
                if ($finishCount >= Constant::TASK_SHARE_DAILY_TIMES) {
                    $reward = 0;
                } else {
                    $reward = Constant::TASK_COIN_NUM["share"];
                }
                break;
            default:
                $reward = 0;
        }

        $redis = Redis::factory();
        pushAddTaskList($user["uuid"], $type, $redis);

        return [
            "coin" => $user["coin"] + $reward,
        ];
    }
}