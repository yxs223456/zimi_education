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
        $this->doWork();

        sleep(30);
    }

    protected function doWork()
    {
        $pkModel = new PkModel();
        $pkJoinModel = new PkJoinModel();
        $userModel = new UserBaseModel();
        $userPkRankModel = new UserPkRankModel();
        $userCoinLogModel = new UserCoinLogModel();
        $userPkCoinLogModel = new UserPkCoinLogModel();
        $userService = new UserService();

        do {
            //已经过了截止答题时间（延迟5秒）状态为进行中的pk
            $pk = Db::name($pkModel->getTable())
                ->where("status", PkStatusEnum::UNDERWAY)
                ->where("deadline", "<", time()-5)
                ->find();

            if ($pk == null) {
                break;
            }

            //参与PK信息
            $joinCount = Db::name($pkJoinModel->getTable())
                ->where("pk_uuid", $pk["uuid"])->count();
            $pkJoins = Db::name($pkJoinModel->getTable())
                ->where("pk_uuid", $pk["uuid"])
                ->where("submit_answer_time" , ">", 0)
                ->order(["score"=>"desc", "answer_time"=>"asc", "id"=>"asc"])
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
                        }
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
                    } else if ($key == 2) {
                        if ($joinCount <= 5) {
                            $pkCoin = 0;
                            $winCoin = 0;
                        } else if ($joinCount <= 9) {
                            $pkCoin = 0;
                            $winCoin = bcmul($totalRewardCoin, 0.2, 0);
                        } else {
                            $pkCoin = 3;
                            $winCoin = bcmul($totalRewardCoin, 0.2, 0);
                        }
                    } else if ($key == 3) {
                        if ($joinCount <= 5) {
                            $pkCoin = 0;
                            $winCoin = 0;
                        } else if ($joinCount <= 9) {
                            $pkCoin = 0;
                            $winCoin = 0;
                        } else {
                            $pkCoin = 0;
                            $winCoin = bcmul($totalRewardCoin, 0.1, 0);
                        }
                    } else {
                        $pkCoin = 0;
                        $winCoin = 0;
                    }

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
                    $redis = Redis::factory();
                    foreach ($winUsers as $winUser) {
                        cacheUserInfoByToken($winUser, $redis);
                    }
                    $redis->close();
                }

            } catch (\Throwable $e) {
                Db::rollback();
                Log::write("pk finish error:".$e->getMessage());
            }
        } while(!!$pk);
    }
}