<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-04-10
 * Time: 14:51
 */

namespace app\command;

use app\api\service\UserService;
use app\common\enum\PkIsInitiatorEnum;
use app\common\enum\PkStatusEnum;
use app\common\enum\UserCoinAddTypeEnum;
use app\common\enum\UserCoinLogTypeEnum;
use app\common\enum\UserPkCoinAddTypeEnum;
use app\common\enum\UserPkCoinLogTypeEnum;
use app\common\helper\Redis;
use app\common\model\NewsModel;
use app\common\model\PkJoinModel;
use app\common\model\PkModel;
use app\common\model\UserBaseModel;
use app\common\model\UserCoinLogModel;
use app\common\model\UserPkCoinLogModel;
use app\common\model\UserPkRankModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\facade\Log;

class PkFinish extends Command
{
    protected function configure()
    {
        // setName 设置命令行名称
        // setDescription 设置命令行描述
        $this->setName('de_education:pkFinish')
            ->setDescription('pk finish');
    }

    protected function execute(Input $input, Output $output)
    {
        while (true) {
            $redis = Redis::factory();
            $finishPkInfo = popPkFinishList($redis);
            $redis->close();
            if ($finishPkInfo == null || empty($finishPkInfo[1])) {
                break;
            }

            $finishPkInfo = json_decode($finishPkInfo[1], true);
            if (empty($finishPkInfo["uuid"])) {
                break;
            }

            $pkUuid = $finishPkInfo["uuid"];
            $this->doWork($pkUuid);
        }
    }

    protected function doWork($pkUuid)
    {
        $pkModel = new PkModel();
        $pkJoinModel = new PkJoinModel();
        $userModel = new UserBaseModel();
        $userPkRankModel = new UserPkRankModel();
        $userCoinLogModel = new UserCoinLogModel();
        $userPkCoinLogModel = new UserPkCoinLogModel();
        $userService = new UserService();
        $newsModel = new NewsModel();

        do {
            $redis = Redis::factory();
            $pk = Db::name($pkModel->getTable())
                ->where("uuid", $pkUuid)
                ->find();

            if ($pk == null || $pk["status"] != PkStatusEnum::UNDERWAY) {
                break;
            }

            //参与PK信息
            $joinCount = Db::name($pkJoinModel->getTable())
                ->where("pk_uuid", $pk["uuid"])->count();
            $pkJoins = Db::name($pkJoinModel->getTable())->alias("pj")
                ->leftJoin("user_base u", "u.uuid=pj.user_uuid")
                ->where("pj.pk_uuid", $pk["uuid"])
                ->where("pj.submit_answer_time" , ">", 0)
                ->field("pj.*,u.nickname,u.os,u.umeng_device_token")
                ->order(["pj.score"=>"desc", "pj.answer_time"=>"asc", "pj.id"=>"asc"])
                ->select();

            Db::startTrans();
            try {
                //奖励分配如下：
                //所有参赛人员的DE累加 80%作为参赛选手前几名奖励，10%平台回收作为PK资源消耗，10%用来奖励发起者得到第一名，如发起者没得到第一名这10%平台回收
                //3-5（包括5人）人PK 第一名60%（3PK值） 第二名40%
                //6-9人 PK 第一名 50%（5PK值） 第二名30%（3PK值） 第三名 20%
                //10人 第一名 40%（10PK值） 第二名 30%（5Pk值） 第三名 20%（3PK值） 第四名 10%
                $coinFlow = [];
                $pkCoinFlow = [];
                $winUserUuids = [];
                $totalRewardCoin = bcmul($pk["total_coin"], 0.8, 0);
                foreach ($pkJoins as $key=>$pkJoin) {
                    if ($key == 0) {
                        if ($joinCount <= 5) {
                            $pkCoin = 3;
                            $winCoin = bcmul($totalRewardCoin, 0.6, 0);
                        } else if ($joinCount <= 9) {
                            $pkCoin = 5;
                            $winCoin = bcmul($totalRewardCoin, 0.5, 0);
                        } else {
                            $pkCoin = 10;
                            $winCoin = bcmul($totalRewardCoin, 0.4, 0);
                        }

                        //团长额外奖励发放
                        if ($pkJoin["is_initiator"] == PkIsInitiatorEnum::YES) {
                            $initiatorBonusCoin = bcmul($totalRewardCoin, 0.1, 0);
                            Db::name($pkJoinModel->getTable())
                                ->where("uuid", $pkJoin["uuid"])
                                ->update(["initiator_bonus_coin"=>$initiatorBonusCoin]);
                            Db::name($userModel->getTable())->where("uuid", $pkJoin["user_uuid"])
                                ->setInc("coin",$initiatorBonusCoin);
                            $user = Db::name($userModel->getTable())->where("uuid", $pkJoin["user_uuid"])->find();
                            $coinFlow[] = [
                                "user_uuid" => $pkJoin["user_uuid"],
                                "type" => UserCoinLogTypeEnum::ADD,
                                "add_type" => UserCoinAddTypeEnum::PK_INITIATOR_WIN,
                                "add_uuid" => $pk["uuid"],
                                "num" => $initiatorBonusCoin,
                                "before_num" => $user["coin"]-$initiatorBonusCoin,
                                "after_num" => $user["coin"],
                                "detail_note" => UserCoinAddTypeEnum::PK_INITIATOR_WIN_DESC,
                                "create_date" => date("Y-m-d"),
                                "create_time" => time(),
                                "update_time" => time(),
                            ];
                            $content = "恭喜你获得 {$pk['name']} PK 赛的冠军，获 得{$winCoin}DE 奖励，{$pkCoin}PK 值奖励，系统将额外奖励{$initiatorBonusCoin}DE 给获得冠军的发起者。";
                        } else {
                            $content = "恭喜你获得 {$pk['name']} PK 赛的冠军，获得{$winCoin}DE 奖励，{$pkCoin}PK 值奖励。";
                        }

                    } else if ($key == 1) {
                        if ($joinCount <= 5) {
                            $pkCoin = 0;
                            $winCoin = bcmul($totalRewardCoin, 0.4, 0);
                        } else if ($joinCount <= 9) {
                            $pkCoin = 3;
                            $winCoin = bcmul($totalRewardCoin, 0.3, 0);
                        } else {
                            $pkCoin = 5;
                            $winCoin = bcmul($totalRewardCoin, 0.3, 0);
                        }
                        if ($joinCount <= 5) {
                            $content = "恭喜你获得 {$pk['name']} PK 赛的第二名，获得{$winCoin}DE 奖励。";
                        } else {
                            $content = "恭喜你获得 {$pk['name']} PK 赛的第二名，获得{$winCoin}DE 奖励，{$pkCoin}PK 值奖励。";
                        }

                    } else if ($key == 2) {
                        if ($joinCount <= 5) {
                            $pkCoin = 0;
                            $winCoin = 0;
                            $content = "很遗憾你未获得 {$pk['name']} PK 赛名次， 继续加油哦。";
                        } else if ($joinCount <= 9) {
                            $pkCoin = 0;
                            $winCoin = bcmul($totalRewardCoin, 0.2, 0);
                            $content = "恭喜你获得 {$pk['name']} PK 赛的第三名，获得{$winCoin}DE 奖励。";
                        } else {
                            $pkCoin = 3;
                            $winCoin = bcmul($totalRewardCoin, 0.2, 0);
                            $content = "恭喜你获得 {$pk['name']} PK 赛的第三名，获得{$winCoin}DE 奖励，{$pkCoin}PK 值奖励。";
                        }
                    } else if ($key == 3) {
                        if ($joinCount <= 5) {
                            $pkCoin = 0;
                            $winCoin = 0;
                            $content = "很遗憾你未获得 {$pk['name']} PK 赛名次， 继续加油哦。";
                        } else if ($joinCount <= 9) {
                            $pkCoin = 0;
                            $winCoin = 0;
                            $content = "很遗憾你未获得 {$pk['name']} PK 赛名次， 继续加油哦。";
                        } else {
                            $pkCoin = 0;
                            $winCoin = bcmul($totalRewardCoin, 0.1, 0);
                            $content = "恭喜你获得 {$pk['name']} PK 赛的第四名，获得{$winCoin}DE 奖励。";
                        }
                    } else {
                        $pkCoin = 0;
                        $winCoin = 0;
                        $content = "很遗憾你未获得 {$pk['name']} PK 赛名次， 继续加油哦。";
                    }

                    //纪录、发送消息
                    $newsModel->addNews($pkJoin["user_uuid"], $content);
                    createUnicastPushTask($pkJoin["os"], $pkJoin["umeng_device_token"], $content, "", [], $redis);

                    //纪录用户PK排行，纪录赢取的DE币
                    Db::name($pkJoinModel->getTable())
                        ->where("uuid", $pkJoin["uuid"])
                        ->update(["rank"=>$key+1,"win_coin"=>$winCoin,"update_time"=>time()]);

                    if ($winCoin != 0 || $pkCoin != 0) {
                        //发放DE币,发放pk值
                        Db::name($userModel->getTable())->where("uuid", $pkJoin["user_uuid"])
                            ->inc("coin",$winCoin)
                            ->inc("pk_coin", $pkCoin)->update(["update_time"=>time()]);
                        $user = Db::name($userModel->getTable())->where("uuid", $pkJoin["user_uuid"])->find();

                        //pk勋章
                        if ($pkCoin > 0) {
                            $userPkLevel = $userService->userPkLevel($user);
                            $userAllMedals = json_decode($user["medals"], true);
                            if ($userPkLevel != 0 && (!isset($userAllMedals["pk_level"]) || $userAllMedals["pk_level"] < $userPkLevel)) {
                                $userAllMedals["pk_level"] = $userPkLevel;
                                $userUpdateData = ["medals"=>json_encode($userAllMedals)];
                                $userSelfMedals = json_decode($user["self_medals"], true);
                                if (count($userSelfMedals) == 0) {
                                    $newUser["self_medals"] = json_encode(["pk_level"=>$userPkLevel]);
                                    $userUpdateData["self_medals"] = $newUser["self_medals"];
                                }
                                $userModel->where("uuid", $user["uuid"])->update($userUpdateData);
                                $newUser["medals"] = json_encode($userAllMedals);
                                switch ($userPkLevel) {
                                    case 1:
                                        $levelContent = "恭喜你 PK 达标 100 点，喜获 PK 新秀称号。";
                                        break;
                                    case 2:
                                        $levelContent = "恭喜你 PK 达标 300 点，喜获 PK 达人称号。";
                                        break;
                                    case 3:
                                        $levelContent = "恭喜你 PK 达标 800 点，喜获 PK 大师称号。";
                                        break;
                                    case 4:
                                        $levelContent = "恭喜你 PK 达标 1500 点，喜获 PK 王称号。";
                                        break;
                                    default:
                                        $levelContent = "恭喜你 PK 达标 1500 点，喜获 PK 王称号。";
                                        break;
                                }
                                //纪录、发送消息
                                $newsModel->addNews($pkJoin["user_uuid"], $levelContent);
                                createUnicastPushTask($pkJoin["os"], $pkJoin["umeng_device_token"], $levelContent, "", [], $redis);
                            }
                        }

                        //DE币流水
                        if ($winCoin > 0) {
                            $coinFlow[] = [
                                "user_uuid" => $pkJoin["user_uuid"],
                                "type" => UserCoinLogTypeEnum::ADD,
                                "add_type" => UserCoinAddTypeEnum::PK_WIN,
                                "add_uuid" => $pk["uuid"],
                                "num" => $winCoin,
                                "before_num" => $user["coin"]-$winCoin,
                                "after_num" => $user["coin"],
                                "detail_note" => UserCoinAddTypeEnum::PK_WIN_DESC,
                                "create_date" => date("Y-m-d"),
                                "create_time" => time(),
                                "update_time" => time(),
                            ];
                        }

                        if ($pkCoin > 0) {
                            //pk值流水
                            $pkCoinFlow[] = [
                                "user_uuid" => $pkJoin["user_uuid"],
                                "type" => UserPkCoinLogTypeEnum::ADD,
                                "add_type" => UserPkCoinAddTypeEnum::PK_WIN,
                                "add_uuid" => $pk["uuid"],
                                "num" => $pkCoin,
                                "before_num" => $user["pk_coin"]-$pkCoin,
                                "after_num" => $user["pk_coin"],
                                "detail_note" => UserPkCoinAddTypeEnum::PK_WIN_DESC,
                                "create_date" => date("Y-m-d"),
                                "create_time" => time(),
                                "update_time" => time(),
                            ];
                            //增加pk排行榜中的pk数值
                            $userPkRankModel->addPkCoin($pkJoin["user_uuid"], $pk["type"], $pkCoin);
                        }

                        $winUserUuids[] = $pkJoin["user_uuid"];
                    }
                }

                //批量写入流水
                if ($coinFlow) {
                    Db::name($userCoinLogModel->getTable())->insertAll($coinFlow);
                }
                if ($pkCoinFlow) {
                    Db::name($userPkCoinLogModel->getTable())->insertAll($pkCoinFlow);
                }

                //pk状态修改为已完成
                Db::name($pkModel->getTable())
                    ->where("uuid", $pk["uuid"])
                    ->update(["status"=>PkStatusEnum::FINISH,"finish_time"=>time(),"update_time"=>time()]);

                Db::commit();

                //修改获胜者用户信息
                if ($winUserUuids) {
                    $winUsers = Db::name($userModel->getTable())->whereIn("uuid", $winUserUuids)->select();
                    foreach ($winUsers as $winUser) {
                        cacheUserInfoByToken($winUser, $redis);
                    }
                }

            } catch (\Throwable $e) {
                Db::rollback();
                Log::write("pk finish error:".$e->getMessage());
            }
            $redis->close();
        } while(!!$pk);

    }
}