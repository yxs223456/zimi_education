<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-04-07
 * Time: 16:52
 */

namespace app\api\service\v1;

use app\api\service\Base;
use app\common\AppException;
use app\common\Constant;
use app\common\helper\Redis;
use app\common\model\InternalCompetitionLikeLogModel;
use app\common\model\InternalCompetitionRankModel;
use app\common\model\SynthesizeRankLikeLogModel;
use app\common\model\UserSynthesizeRankModel;
use think\Db;

class RankService extends Base
{
    public function synthesizeRank($user, $difficultyLevel)
    {
        $userSynthesizeRankModel = new UserSynthesizeRankModel();
        $rankList = $userSynthesizeRankModel->getRank($difficultyLevel);
        foreach ($rankList as $key=>$item) {
            $rankList[$key]["user_uuid"] = $item["user_uuid"];
            $rankList[$key]["nickname"] = getNickname($item["nickname"]);
            $rankList[$key]["head_image_url"] = getHeadImageUrl($item["head_image_url"]);
            $rankList[$key]["rank"] = $key+1;
        }

        $myRank = [
            "head_image_url" => getHeadImageUrl($user["head_image_url"]),
            "nickname" => getNickname($user["nickname"]),
            "rank" => $userSynthesizeRankModel->getUserSynthesizeRank($user["uuid"], $difficultyLevel),
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
        foreach ($rankList as $key=>$item) {
            $rankList[$key]["user_uuid"] = $item["user_uuid"];
            $rankList[$key]["nickname"] = getNickname($item["nickname"]);
            $rankList[$key]["head_image_url"] = getHeadImageUrl($item["head_image_url"]);
            $rankList[$key]["rank"] = $key+1;
        }

        $myRank = [
            "head_image_url" => getHeadImageUrl($user["head_image_url"]),
            "nickname" => getNickname($user["nickname"]),
            "rank" => $internalCompetitionRankModel->getSelfRank($user["uuid"]),
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
}