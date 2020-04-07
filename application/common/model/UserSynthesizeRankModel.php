<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-04-07
 * Time: 16:24
 */

namespace app\common\model;

class UserSynthesizeRankModel extends Base
{
    protected $table = 'user_synthesize_rank';

    public function getRank($difficultyLevel)
    {
        return $this->alias("usr")
            ->leftJoin("user_base u", "u.uuid=usr.user_uuid")
            ->where("usr.difficulty_level", $difficultyLevel)
            ->field("u.nickname,u.head_image_url,usr.like_count")
            ->order(["usr.total_score"=>"desc","usr.like_count"=>"desc","usr.update_time"=>"asc"])
            ->limit(0, 100)
            ->select()->toArray();
    }

    public function getUserSynthesizeRank($userUuid, $difficultyLevel)
    {
        $userSynthesizeRank = $this->where("user_uuid", $userUuid)
            ->where("difficulty_level", $difficultyLevel)
            ->find();

        if ($userSynthesizeRank == null) {
            return 0;
        } else {
            $count = $this->where("difficulty_level", $difficultyLevel)
                ->where("total_score", ">", $userSynthesizeRank["total_score"])
                ->where("like_count", ">", $userSynthesizeRank["like_count"])
                ->where("update_time", "<", $userSynthesizeRank["update_time"])
                ->count();
            return $count+1;
        }
    }
}