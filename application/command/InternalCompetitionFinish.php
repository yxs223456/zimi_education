<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-04-08
 * Time: 15:11
 */

namespace app\command;

use app\common\enum\CompetitionAnswerIsSubmitEnum;
use app\common\enum\InternalCompetitionIsFinishEnum;
use app\common\enum\InternalCompetitionJoinIsCommentEnum;
use app\common\enum\UserPkCoinAddTypeEnum;
use app\common\enum\UserPkCoinLogTypeEnum;
use app\common\enum\UserTalentCoinAddTypeEnum;
use app\common\enum\UserTalentCoinLogTypeEnum;
use app\common\helper\Redis;
use app\common\model\InternalCompetitionJoinModel;
use app\common\model\InternalCompetitionModel;
use app\common\model\InternalCompetitionRankModel;
use app\common\model\UserBaseModel;
use app\common\model\UserPkCoinLogModel;
use app\common\model\UserTalentCoinLogModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\facade\Log;

class InternalCompetitionFinish extends Command
{
    protected function configure()
    {
        // setName 设置命令行名称
        // setDescription 设置命令行描述
        $this->setName('de_education:internalCompetitionFinish')
            ->setDescription('internal competition finish');
    }

    protected function execute(Input $input, Output $output)
    {
        $internalCompetitionModel = new InternalCompetitionModel();
        $waitFinishInternalCompetition = $internalCompetitionModel
            ->where("submit_answer_deadline", "<", time())
            ->where("is_finish", InternalCompetitionIsFinishEnum::NO)
            ->find();

        if ($waitFinishInternalCompetition) {
            $internalCompetitionJoinModel = new InternalCompetitionJoinModel();
            //全部评分完成后，开始结算
            $notCommentCount = $internalCompetitionJoinModel
                ->where("c_uuid", $waitFinishInternalCompetition["uuid"])
                ->where("is_submit_answer", CompetitionAnswerIsSubmitEnum::YES)
                ->where("is_comment", InternalCompetitionJoinIsCommentEnum::NO)
                ->count();

            if ($notCommentCount == 0) {
                //获得比赛前三名的同学可再得 2、3、5 才情值，在才情 榜上公示，各获得额外 20、30、50PK 值奖励。
                $winners = $internalCompetitionJoinModel
                    ->where("c_uuid", $waitFinishInternalCompetition["uuid"])
                    ->where("is_submit_answer", CompetitionAnswerIsSubmitEnum::YES)
                    ->order(["score"=>"desc", "submit_answer_second"=>"asc", "id"=>"asc"])
                    ->limit(0, 3)
                    ->select()->toArray();

                $internalCompetitionRankModel = new InternalCompetitionRankModel();
                $pkCoinLogModel = new UserPkCoinLogModel();
                $talentCoinLogModel = new UserTalentCoinLogModel();
                $userModel = new UserBaseModel();
                $userUuids = array_column($winners, "user_uuid");
                $users = $userModel->whereIn("uuid", $userUuids)->column("pk_coin,talent_coin", "uuid");

                Db::startTrans();
                try {
                    $pkFlowData = [];
                    $talentFlowData = [];
                    foreach ($winners as $key=>$winner) {
                        $rank = $key+1;
                        if ($rank == 1) {
                            $talentCoin = 5;
                            $pkCoin = 50;
                        } elseif ($rank == 2) {
                            $talentCoin = 3;
                            $pkCoin = 30;
                        } else {
                            $talentCoin = 2;
                            $pkCoin = 20;
                        }
                        //纪录用户排名
                        Db::name($internalCompetitionJoinModel->getTable())
                            ->where("uuid", $winner["uuid"])
                            ->update(
                                ["rank"=>$rank,"update_time"=>time()]
                            );

                        //增加用户pk值和才情值
                        Db::name($userModel->getTable())
                            ->where("uuid", $winner["user_uuid"])
                            ->inc("pk_coin", $pkCoin)
                            ->inc("talent_coin", $talentCoin)
                            ->update(["update_time"=>time()]);

                        //纪录pk流水
                        $pkFlowData[] = [
                            "user_uuid" => $winner["user_uuid"],
                            "type" => UserPkCoinLogTypeEnum::ADD,
                            "add_type" => UserPkCoinAddTypeEnum::INTERNAL_COMPETITION_WIN,
                            "add_uuid" => $waitFinishInternalCompetition["uuid"],
                            "num" => $pkCoin,
                            "before_num" => $users[$winner["user_uuid"]]["pk_coin"],
                            "after_num" => $users[$winner["user_uuid"]]["pk_coin"] + $pkCoin,
                            "detail_note" => UserPkCoinAddTypeEnum::INTERNAL_COMPETITION_WIN_DSC,
                            "create_date" => date("Y-m-d"),
                            "create_time" => time(),
                            "update_time" => time(),
                        ];

                        //纪录才情流水
                        $talentFlowData[] = [
                            "user_uuid" => $winner["user_uuid"],
                            "type" => UserTalentCoinLogTypeEnum::ADD,
                            "add_type" => UserTalentCoinAddTypeEnum::INTERNAL_COMPETITION_WIN,
                            "add_uuid" => $waitFinishInternalCompetition["uuid"],
                            "num" => $talentCoin,
                            "before_num" => $users[$winner["user_uuid"]]["talent_coin"],
                            "after_num" => $users[$winner["user_uuid"]]["talent_coin"] + $talentCoin,
                            "detail_note" => UserPkCoinAddTypeEnum::INTERNAL_COMPETITION_WIN_DSC,
                            "create_date" => date("Y-m-d"),
                            "create_time" => time(),
                            "update_time" => time(),
                        ];

                        //修改才情排行榜
                       $internalCompetitionRankModel->addTalentCoin($winner["user_uuid"],$talentCoin);
                    }
                    if ($pkFlowData) {
                        $pkCoinLogModel->insertAll($pkFlowData);
                    }
                    if ($talentFlowData) {
                        $talentCoinLogModel->insertAll($talentFlowData);
                    }

                    //大赛状态改为完成
                    $waitFinishInternalCompetition->is_finish = InternalCompetitionIsFinishEnum::YES;
                    $waitFinishInternalCompetition->save();

                    Db::commit();

                    //缓存获胜者信息
                    $newUsers = $userModel->whereIn("uuid", $userUuids)->select()->toArray();
                    $redis = Redis::factory();
                    foreach ($newUsers as $newUser) {
                        cacheUserInfoByToken($newUser, $redis);
                    }

                } catch (\Throwable $e) {
                    Db::rollback();
                    Log::write("internal competition finish error:". $e->getMessage(), "ERROR");
                }
            }
        }

        sleep(300);
    }

}