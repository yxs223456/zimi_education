<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-12
 * Time: 21:57
 */

namespace app\command;

use app\api\service\UserService;
use app\common\Constant;
use app\common\enum\UserCoinAddTypeEnum;
use app\common\helper\Redis;
use app\common\model\NewsModel;
use app\common\model\UserBaseModel;
use app\common\model\UserCoinLogModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\facade\Log;

class AddCoinByFinishTask extends Command
{
    protected function configure()
    {
        // setName 设置命令行名称
        // setDescription 设置命令行描述
        $this->setName('de_education:addCoin')
            ->setDescription('add coin');
    }

    protected function execute(Input $input, Output $output)
    {
        $startTime = time();
        while (time() - $startTime < 60) {
            $redis = Redis::factory();

            $addCoinInfo = getAddCoinList($redis);

            if ($addCoinInfo == null || empty($addCoinInfo[1])) {
                $redis->close();
                continue;
            }

            $addCoinTaskInfo = json_decode($addCoinInfo[1], true);
            if (empty($addCoinTaskInfo["uuid"]) || empty($addCoinTaskInfo["add_type"])) {
                continue;
            }

            //书币增加方式
            switch ($addCoinTaskInfo["add_type"]) {
                case UserCoinAddTypeEnum::USER_INFO:
                    $this->finishUserInfo($addCoinTaskInfo["uuid"], $redis);
                    break;
                case UserCoinAddTypeEnum::PARENT_INVITE_CODE:
                    $this->fillInInviteCode($addCoinTaskInfo["uuid"], $redis);
                    break;
                case UserCoinAddTypeEnum::BIND_WE_CHAT:
                    $this->finishBindWeChat($addCoinTaskInfo["uuid"], $redis);
                    break;
                case UserCoinAddTypeEnum::SHARE:
                    $this->finishShare($addCoinTaskInfo["uuid"], $redis);
                    break;
            }

            $redis->close();
        }
    }

    //完善用户信息
    private function finishUserInfo($userUuid, $redis)
    {
        $userCoinLogModel = new UserCoinLogModel();
        $userModel = new UserBaseModel();

        //领取过奖励不予处理
        $taskData = $userCoinLogModel->getByUserUuidAndAddType($userUuid, UserCoinAddTypeEnum::USER_INFO);
        if ($taskData) {
            return;
        }

        //用户原信息
        $user = $userModel->findByUuid($userUuid);
        if (empty($user)) {
            return;
        }
        $oldUser = $user->toArray();

        //判断用户是否完成任务
        $userService = new UserService();
        if ($userService->checkUserInfoComplete($user) == false) {
            Log::write("add coin user uuid $userUuid 完善用户资料任务未完成");
            return;
        }

        Db::startTrans();
        try {
            //增加用户书币数
            $userModel->where("uuid", $userUuid)
                ->inc("coin", Constant::TASK_COIN_NUM["user_info"])
                ->update(["update_time"=>time()]);
            $newUser = $userModel->findByUuid($userUuid)->toArray();

            //纪录书币流水
            $userCoinLogModel->recordAddLog(
                $userUuid,
                UserCoinAddTypeEnum::USER_INFO,
                Constant::TASK_COIN_NUM["user_info"],
                $oldUser["coin"],
                $newUser["coin"],
                UserCoinAddTypeEnum::USER_INFO_DSC);

            Db::commit();
            
            //缓存用户信息
            cacheUserInfoByToken($newUser, $redis);

            //纪录，发送消息
            $newsModel = new NewsModel();
            $content = "恭喜你完善所有个人信息，将获得一次 性奖励 10DE。";
            $newsModel->addNews($newUser["uuid"], $content);
            $title = "完善信息还有小奖励哦~";
            createUnicastPushTask($newUser["os"], $newUser["uuid"], $content, "", [], $redis, $title);

        } catch (\Throwable $e) {
            Db::rollback();
            Log::write("add coin error: " . $e->getMessage(), "error");
        }
    }

    //完成填写邀请码
    private function fillInInviteCode($userUuid, $redis)
    {
        $userCoinLogModel = new UserCoinLogModel();
        $userModel = new UserBaseModel();

        //领取过奖励不予处理
        $taskData = $userCoinLogModel->getByUserUuidAndAddType($userUuid, UserCoinAddTypeEnum::PARENT_INVITE_CODE);
        if ($taskData) {
            return;
        }

        //用户原信息
        $user = $userModel->findByUuid($userUuid);
        if (empty($user)) {
            return;
        }
        $oldUser = $user->toArray();

        //判断用户是否完成任务
        if (empty($user["parent_invite_code"])) {
            Log::write("add coin user uuid $userUuid 填写邀请码任务未完成");
            return;
        }

        Db::startTrans();
        try {
            //增加用户书币数
            $userModel->where("uuid", $userUuid)
                ->inc("coin", Constant::TASK_COIN_NUM["parent_invite_code"])
                ->update(["update_time"=>time()]);
            $newUser = $userModel->findByUuid($userUuid)->toArray();

            //纪录书币流水
            $userCoinLogModel->recordAddLog(
                $userUuid,
                UserCoinAddTypeEnum::PARENT_INVITE_CODE,
                Constant::TASK_COIN_NUM["parent_invite_code"],
                $oldUser["coin"],
                $newUser["coin"],
                UserCoinAddTypeEnum::PARENT_INVITE_CODE_DESC);

            Db::commit();

            //缓存用户信息
            cacheUserInfoByToken($newUser, $redis);
        } catch (\Throwable $e) {
            Db::rollback();
            Log::write("add coin error: " . $e->getMessage(), "error");
        }
    }

    //完成绑定微信
    private function finishBindWeChat($userUuid, $redis)
    {
        $userCoinLogModel = new UserCoinLogModel();
        $userModel = new UserBaseModel();

        //领取过奖励不予处理
        $taskData = $userCoinLogModel->getByUserUuidAndAddType($userUuid, UserCoinAddTypeEnum::BIND_WE_CHAT);
        if ($taskData) {
            return;
        }

        //用户原信息
        $user = $userModel->findByUuid($userUuid);
        if (empty($user)) {
            return;
        }
        $oldUser = $user->toArray();

        //判断用户是否完成任务
        if (empty($user["mobile_openid"])) {
            Log::write("add coin user uuid $userUuid 绑定微信任务未完成");
            return;
        }

        Db::startTrans();
        try {
            //增加用户书币数
            $userModel->where("uuid", $userUuid)
                ->inc("coin", Constant::TASK_COIN_NUM["bind_we_chat"])
                ->update(["update_time"=>time()]);
            $newUser = $userModel->findByUuid($userUuid)->toArray();

            //纪录书币流水
            $userCoinLogModel->recordAddLog(
                $userUuid,
                UserCoinAddTypeEnum::BIND_WE_CHAT,
                Constant::TASK_COIN_NUM["bind_we_chat"],
                $oldUser["coin"],
                $newUser["coin"],
                UserCoinAddTypeEnum::BIND_WE_CHAT_DESC);

            Db::commit();

            //缓存用户信息
            cacheUserInfoByToken($newUser, $redis);

            //纪录，发送消息
            $newsModel = new NewsModel();
            $content = "你已成功绑定微信，将获得一次性奖励 10DE。";
            $newsModel->addNews($newUser["uuid"], $content);
            $title = "绑定微信还有小惊喜哦~";
            createUnicastPushTask($newUser["os"], $newUser["uuid"], $content, "", [], $redis, $title);
        } catch (\Throwable $e) {
            Db::rollback();
            Log::write("add coin error: " . $e->getMessage(), "error");
        }
    }

    //完成微信分享
    private function finishShare($userUuid, $redis)
    {
        $userCoinLogModel = new UserCoinLogModel();
        $userModel = new UserBaseModel();

        //通过分享，每日最多领取固定次数书币
        $finishCount = $userCoinLogModel->todayCountFromShare($userUuid);
        if ($finishCount >= Constant::TASK_SHARE_DAILY_TIMES) {
            return;
        }

        //用户原信息
        $user = $userModel->findByUuid($userUuid);

        if (empty($user)) {
            return;
        }
        $oldUser = $user->toArray();

        Db::startTrans();
        try {
            //增加用户书币数
            $userModel->where("uuid", $userUuid)
                ->inc("coin", Constant::TASK_COIN_NUM["share"])
                ->update(["update_time"=>time()]);
            $newUser = $userModel->findByUuid($userUuid)->toArray();

            //纪录书币流水
            $userCoinLogModel->recordAddLog(
                $userUuid,
                UserCoinAddTypeEnum::SHARE,
                Constant::TASK_COIN_NUM["share"],
                $oldUser["coin"],
                $newUser["coin"],
                UserCoinAddTypeEnum::SHARE_DESC);

            Db::commit();
            //缓存用户信息
            cacheUserInfoByToken($newUser, $redis);
            //用户今日分享领书币次数+1
            addUserGetCoinByShareTimes($userUuid, $redis);

            //纪录，发送消息
            $newsModel = new NewsModel();
            $content = "你已成功分享，系统奖励 2DE。";
            $newsModel->addNews($newUser["uuid"], $content);
        } catch (\Throwable $e) {
            Db::rollback();
            Log::write("add coin error: " . $e->getMessage(), "error");
        }
    }
}