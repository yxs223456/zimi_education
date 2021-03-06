<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-04-07
 * Time: 16:52
 */

namespace app\api\service\v1;

use app\api\service\Base;
use app\api\service\UserService;
use app\common\AppException;
use app\common\Constant;
use app\common\helper\Redis;
use app\common\model\InternalCompetitionLikeLogModel;
use app\common\model\InternalCompetitionRankModel;
use app\common\model\PkRankLikeLogModel;
use app\common\model\SynthesizeRankLikeLogModel;
use app\common\model\UserBaseModel;
use app\common\model\UserPkRankModel;
use app\common\model\UserSynthesizeRankModel;
use think\Db;

class RankService extends Base
{
    public function synthesizeRank($user, $difficultyLevel)
    {
        $userSynthesizeRankModel = new UserSynthesizeRankModel();
        $rankList = $userSynthesizeRankModel->getRank($difficultyLevel);
        $userService = new UserService();
        $redis = Redis::factory();
        $todayLikeInfo = getSynthesizeRankLikeTodayInfo($user["uuid"], $difficultyLevel, $redis);
        $todayLikeInfo = array_column($todayLikeInfo, "user_uuid");
        $myRankInfo = [];
        foreach ($rankList as $key=>$item) {
            if ($item["user_uuid"] == $user["uuid"]) {
                $myRankInfo = [
                    "rank" => $key+1,
                    "like_count" => $item["like_count"],
                ];
            }
            $rankList[$key]["nickname"] = getNickname($item["nickname"]);
            $rankList[$key]["head_image_url"] = getHeadImageUrl($item["head_image_url"]);
            $rankList[$key]["rank"] = $key+1;
            $rankList[$key]["self_medals"] = $userService->userSelfMedals(json_decode($item["self_medals"], true));
            $rankList[$key]["is_like"] = (int) in_array($item["user_uuid"], $todayLikeInfo);
        }

        if (!$myRankInfo) {
            $myRankInfo = $userSynthesizeRankModel->getUserSynthesizeRank($user["uuid"], $difficultyLevel);
        }
        $myRank = [
            "head_image_url" => getHeadImageUrl($user["head_image_url"]),
            "nickname" => getNickname($user["nickname"]),
            "rank" => $myRankInfo["rank"],
            "like_count" => $myRankInfo["like_count"],
            "self_medals" => $userService->userSelfMedals(json_decode($user["self_medals"], true)),
        ];

        $redis = Redis::factory();
        $carousel = getSynthesizeUpdateList($difficultyLevel, $redis);

        return [
            "rank_list" => $rankList,
            "my_rank" => $myRank,
            "carousel" => $carousel,
        ];
    }

    public function synthesizeLike($user, $likeUserUuid, $difficultyLevel)
    {
        if ($user["uuid"] == $likeUserUuid) {
            throw AppException::factory(AppException::RANK_LIKE_SELF);
        }

        //每人每天可以助力3次（不能同时助力一人）
        $redis = Redis::factory();
        $todaySynthesizeLikeInfo = getSynthesizeRankLikeTodayInfo($user["uuid"], $difficultyLevel, $redis);
        if (count($todaySynthesizeLikeInfo) >= Constant::RANK_LIKE_TIMES) {
            throw AppException::factory(AppException::RANK_DAILY_LIKE_THREE_TIMES);
        }
        foreach ($todaySynthesizeLikeInfo as $item) {
            if ($item["user_uuid"] == $likeUserUuid) {
                throw AppException::factory(AppException::RANK_DAILY_LIKE_SOMEONE_ONE_TIMES);
            }
        }

        $synthesizeRankLikeLogModel = new SynthesizeRankLikeLogModel();
        $userSynthesizeRankModel = new UserSynthesizeRankModel();
        Db::startTrans();
        try {
            //助力纪录
            $likeInfo = [
                "user_uuid" => $user["uuid"],
                "like_user_uuid" => $likeUserUuid,
                "difficulty_level" => $difficultyLevel,
                "create_date" => date("Y-m-d"),
            ];
            $synthesizeRankLikeLogModel->save($likeInfo);

            //增加被助力次数
            $userSynthesizeRankModel->where("user_uuid", $likeUserUuid)
                ->where("difficulty_level", $difficultyLevel)
                ->setInc("like_count", 1);
            $this->addLikeTimes($user["uuid"]);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }

        //纪录助力
        $todaySynthesizeLikeInfo[] = [
            "user_uuid" => $likeUserUuid
        ];
        cacheSynthesizeRankLikeTodayInfo($user["uuid"], $difficultyLevel, $todaySynthesizeLikeInfo, $redis);

        return new \stdClass();
    }

    public function competitionRank($user)
    {
        $internalCompetitionRankModel = new InternalCompetitionRankModel();
        $rankList = $internalCompetitionRankModel->getRank();
        $userService = new UserService();
        $redis = Redis::factory();
        $todayLikeInfo = getCompetitionRankLikeTodayInfo($user["uuid"], $redis);
        $todayLikeInfo = array_column($todayLikeInfo, "user_uuid");
        $myRankInfo = [];
        foreach ($rankList as $key=>$item) {
            if ($item["user_uuid"] == $user["uuid"]) {
                $myRankInfo = [
                    "rank" => $key+1,
                    "like_count" => $item["like_count"],
                    "total_talent_coin" => $item["total_talent_coin"],
                ];
            }
            $rankList[$key]["nickname"] = getNickname($item["nickname"]);
            $rankList[$key]["head_image_url"] = getHeadImageUrl($item["head_image_url"]);
            $rankList[$key]["rank"] = $key+1;
            $rankList[$key]["self_medals"] = $userService->userSelfMedals(json_decode($item["self_medals"], true));
            $rankList[$key]["is_like"] = (int) in_array($item["user_uuid"], $todayLikeInfo);;
        }

        if (empty($myRankInfo)) {
            $myRankInfo = $internalCompetitionRankModel->getSelfRank($user["uuid"]);
        }
        $myRank = [
            "head_image_url" => getHeadImageUrl($user["head_image_url"]),
            "nickname" => getNickname($user["nickname"]),
            "rank" => $myRankInfo["rank"],
            "total_talent_coin" => $myRankInfo["total_talent_coin"],
            "like_count" => $myRankInfo["like_count"],
            "self_medals" => $userService->userSelfMedals(json_decode($user["self_medals"], true)),
        ];

        return [
            "rank_list" => $rankList,
            "my_rank" => $myRank,
        ];
    }

    public function competitionLike($user, $likeUserUuid)
    {
        if ($user["uuid"] == $likeUserUuid) {
            throw AppException::factory(AppException::RANK_LIKE_SELF);
        }

        //每人每天可以助力3次（不能同时助力一人）
        $redis = Redis::factory();
        $todayLikeInfo = getCompetitionRankLikeTodayInfo($user["uuid"], $redis);
        if (count($todayLikeInfo) >= Constant::RANK_LIKE_TIMES) {
            throw AppException::factory(AppException::RANK_DAILY_LIKE_THREE_TIMES);
        }
        foreach ($todayLikeInfo as $item) {
            if ($item["user_uuid"] == $likeUserUuid) {
                throw AppException::factory(AppException::RANK_DAILY_LIKE_SOMEONE_ONE_TIMES);
            }
        }

        $internalCompetitionRankLikeLogModel = new InternalCompetitionLikeLogModel();
        $internalCompetitionRankModel = new InternalCompetitionRankModel();
        Db::startTrans();
        try {
            //助力纪录
            $likeInfo = [
                "user_uuid" => $user["uuid"],
                "like_user_uuid" => $likeUserUuid,
                "create_date" => date("Y-m-d"),
            ];
            $internalCompetitionRankLikeLogModel->save($likeInfo);

            //增加被助力次数
            $internalCompetitionRankModel->where("user_uuid", $likeUserUuid)
                ->setInc("like_count", 1);

            $this->addLikeTimes($user["uuid"]);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }

        //纪录助力
        $todayLikeInfo[] = [
            "user_uuid" => $likeUserUuid
        ];
        cacheCompetitionRankLikeTodayInfo($user["uuid"], $todayLikeInfo, $redis);

        return new \stdClass();
    }

    public function pkRank($user, $type)
    {
        $userPkRankModel = new UserPkRankModel();
        $rankList = $userPkRankModel->getRank($type);
        $userService = new UserService();
        $redis = Redis::factory();
        $todayLikeInfo = getPkRankLikeTodayInfo($user["uuid"], $type, $redis);
        $todayLikeInfo = array_column($todayLikeInfo, "user_uuid");
        $myRankInfo = [];
        foreach ($rankList as $key=>$item) {
            if ($item["user_uuid"] == $user["uuid"]) {
                $myRankInfo = [
                    "rank" => $key+1,
                    "like_count" => $item["like_count"],
                    "total_pk_coin" => $item["total_pk_coin"],
                ];
            }
            $rankList[$key]["nickname"] = getNickname($item["nickname"]);
            $rankList[$key]["head_image_url"] = getHeadImageUrl($item["head_image_url"]);
            $rankList[$key]["rank"] = $key+1;
            $rankList[$key]["self_medals"] = $userService->userSelfMedals(json_decode($item["self_medals"], true));
            $rankList[$key]["is_like"] = (int) in_array($item["user_uuid"], $todayLikeInfo);
        }

        if (empty($myRankInfo)) {
            $myRankInfo = $userPkRankModel->getUserPkRank($user["uuid"], $type);
        }
        $myRank = [
            "head_image_url" => getHeadImageUrl($user["head_image_url"]),
            "nickname" => getNickname($user["nickname"]),
            "rank" => $myRankInfo["rank"],
            "total_pk_coin" => $myRankInfo["total_pk_coin"],
            "like_count" => $myRankInfo["like_count"],
            "self_medals" => $userService->userSelfMedals(json_decode($user["self_medals"], true)),
        ];

        return [
            "rank_list" => $rankList,
            "my_rank" => $myRank,
        ];
    }

    public function pkLike($user, $likeUserUuid, $type)
    {
        if ($user["uuid"] == $likeUserUuid) {
            throw AppException::factory(AppException::RANK_LIKE_SELF);
        }

        //每人每天可以助力3次（不能同时助力一人）
        $redis = Redis::factory();
        $todayPkLikeInfo = getPkRankLikeTodayInfo($user["uuid"], $type, $redis);
        if (count($todayPkLikeInfo) >= Constant::RANK_LIKE_TIMES) {
            throw AppException::factory(AppException::RANK_DAILY_LIKE_THREE_TIMES);
        }
        foreach ($todayPkLikeInfo as $item) {
            if ($item["user_uuid"] == $likeUserUuid) {
                throw AppException::factory(AppException::RANK_DAILY_LIKE_SOMEONE_ONE_TIMES);
            }
        }

        $pkRankLikeLogModel = new PkRankLikeLogModel();
        $userPkRankModel = new UserPkRankModel();
        Db::startTrans();
        try {
            //助力纪录
            $likeInfo = [
                "user_uuid" => $user["uuid"],
                "like_user_uuid" => $likeUserUuid,
                "type" => $type,
                "create_date" => date("Y-m-d"),
            ];
            $pkRankLikeLogModel->save($likeInfo);

            //增加被助力次数
            $userPkRankModel->where("user_uuid", $likeUserUuid)
                ->where("type", $type)
                ->setInc("like_count", 1);

            $this->addLikeTimes($user["uuid"]);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }

        //纪录助力
        $todayPkLikeInfo[] = [
            "user_uuid" => $likeUserUuid
        ];
        cachePkRankLikeTodayInfo($user["uuid"], $type, $todayPkLikeInfo, $redis);

        return new \stdClass();
    }

    private function addLikeTimes($userUuid)
    {
        $userService = new UserService();

        $user = Db::name("user_base")->where("uuid", $userUuid)->find();
        $likeTimes = $user["like_times"] + 1;
        $userNewLikeLevel = $userService->userLikeLevel($likeTimes);
        $userUpdateData = ["like_times"=>$likeTimes];
        if ($userNewLikeLevel > $user["like_level"]) {
            $userUpdateData["like_level"] = $userNewLikeLevel;
            //用户勋章以及当前勋章计算
            $userMedals = json_decode($user["medals"], true);
            $selfMedals = json_decode($user["self_medals"], true);
            $userMedals["like_level"] = $userNewLikeLevel;
            $userUpdateData["medals"] = json_encode($userMedals);
            if (empty($selfMedals)) {
                $selfMedals["like_level"] = $userNewLikeLevel;
                $userUpdateData["self_medals"] = json_encode($selfMedals);
            }
        }
        Db::name("user_base")->where("uuid", $userUuid)->update($userUpdateData);
    }

}