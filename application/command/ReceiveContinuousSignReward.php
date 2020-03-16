<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-15
 * Time: 11:25
 */

namespace app\command;

use app\common\Constant;
use app\common\enum\UserCoinAddTypeEnum;
use app\common\helper\Redis;
use app\common\model\UserBaseModel;
use app\common\model\UserCoinLogModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\facade\Log;

class ReceiveContinuousSignReward extends Command
{
    protected function configure()
    {
        // setName 设置命令行名称
        // setDescription 设置命令行描述
        $this->setName('de_education:receiveContinuousSignReward')
            ->setDescription('receive continuous sign reward');
    }

    protected function execute(Input $input, Output $output)
    {
        $redis = Redis::factory();

        $receiveContinuousSignRewardInfo = getReceiveContinuousSignRewardList($redis);

        if ($receiveContinuousSignRewardInfo == null || empty($receiveContinuousSignRewardInfo[1])) {
            $redis->close();
            return;
        }

        $receiveInfo = json_decode($receiveContinuousSignRewardInfo[1], true);
        if (empty($receiveInfo["user"]) || empty($receiveInfo["condition"])) {
            return;
        }

        $this->doWord($receiveInfo["user"], $receiveInfo["condition"], $redis);
        $redis->close();
    }

   protected function doWord($user, $condition, $redis)
   {
       //连续签到奖励配置
       $rewardConfigList = Constant::CONTINUOUS_SIGN_REWARD;
        Log::write("1111");
       //用户本月连续签到奖励领取情况
       $continuousSignReward = currentMonthContinuousSignReward($user["uuid"], $redis);

       foreach ($rewardConfigList as $rewardConfig) {
           //用户当前连续签到天数 >= 配置中可以领取奖励的连续签到天数
           if ($user["continuous_sign_times"] >= $condition && $rewardConfig["condition"] == $condition) {
               switch ($condition) {
                   //计算书币增加类型、奖励书币数、奖励书币纪录描述
                   case Constant::CONTINUOUS_SIGN_REWARD[0]["continue"]:
                       $addCoinType = UserCoinAddTypeEnum::CONTINUOUS_SIGN_3_DAY;
                       $coinNum = Constant::CONTINUOUS_SIGN_REWARD[0]["coin"];
                       $coinLogDetailNote = UserCoinAddTypeEnum::CONTINUOUS_SIGN_3_DAY_DESC;
                       break;
                   case Constant::CONTINUOUS_SIGN_REWARD[1]["continue"]:
                       $addCoinType = UserCoinAddTypeEnum::CONTINUOUS_SIGN_7_DAY;
                       $coinNum = Constant::CONTINUOUS_SIGN_REWARD[1]["coin"];
                       $coinLogDetailNote = UserCoinAddTypeEnum::CONTINUOUS_SIGN_7_DAY_DESC;
                       break;
                   case Constant::CONTINUOUS_SIGN_REWARD[2]["continue"]:
                       $addCoinType = UserCoinAddTypeEnum::CONTINUOUS_SIGN_15_DAY;
                       $coinNum = Constant::CONTINUOUS_SIGN_REWARD[2]["coin"];
                       $coinLogDetailNote = UserCoinAddTypeEnum::CONTINUOUS_SIGN_15_DAY_DESC;
                       break;
                   case Constant::CONTINUOUS_SIGN_REWARD[3]["continue"]:
                       $addCoinType = UserCoinAddTypeEnum::CONTINUOUS_SIGN_30_DAY;
                       $coinNum = Constant::CONTINUOUS_SIGN_REWARD[3]["coin"];
                       $coinLogDetailNote = UserCoinAddTypeEnum::CONTINUOUS_SIGN_30_DAY_DESC;
                       break;
                   default:
                       return;
               }

               Log::write($coinLogDetailNote);

               $userModel = new UserBaseModel;
               $userCoinLogModel = new UserCoinLogModel();

               //本月领取过奖励不予处理
               $receiveCoinLog = $userCoinLogModel->getLastGetCoinFromContinuousSign($user["uuid"], $addCoinType);
               if ($receiveCoinLog &&
                   substr($receiveCoinLog["create_date"], 0, 7) == date("Y-m")) {
                   return;
               }

               Log::write(22222222);

               Db::startTrans();
               try {
                   //增加用户书币数
                   $userModel->where("uuid", $user["uuid"])
                       ->inc("coin", $coinNum)
                       ->update(["update_time"=>time()]);
                   $newUser = $userModel->findByUuid($user["uuid"])->toArray();

                   //纪录书币流水
                   $userCoinLogModel->recordAddLog(
                       $user["uuid"],
                       $addCoinType,
                       $coinNum,
                       $newUser["coin"] - $coinNum,
                       $newUser["coin"],
                       $coinLogDetailNote);

                   Db::commit();

                   //缓存用户信息
                   cacheUserInfoByToken($newUser, $redis);

                   //纪录用户领取了本次奖励
                   $continuousSignReward[] = [
                       "condition" => $condition,
                       "coin" => $coinNum,
                   ];
                   cacheMonthContinuousSignReward($user["uuid"], $continuousSignReward, $redis);

               } catch (\Throwable $e) {
                   Db::rollback();
                   Log::write("receive continuous sign reward error: " . $e->getMessage(), "error");
               }

               break;
           }
       }
   }
}