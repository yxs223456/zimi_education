<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-04-08
 * Time: 18:30
 */

namespace app\common\model;

use think\Db;

class InternalCompetitionRankModel extends Base
{
    protected $table = 'internal_competition_rank';

    public function findByUserUuid($userUuid)
    {
        return $this->where("user_uuid", $userUuid)->find();
    }

    public function addTalentCoin($userUuid, $talentCoin)
    {
        $internalCompetitionRank = $this->findByUserUuid($userUuid);
        if ($internalCompetitionRank) {
            Db::name($this->table)->where("user_uuid", $userUuid)
                ->inc("total_talent_coin", $talentCoin)
                ->update(["update_time"=>time()]);
        } else {
            $internalCompetitionRankData = [
                "user_uuid" => $userUuid,
                "total_talent_coin" => $talentCoin,
                "create_time" => time(),
                "update_time" => time(),
            ];
            //使用save会产生bug
            $this->insert($internalCompetitionRankData);
        }
    }

    public function getRank()
    {
        return $this->alias("icr")
            ->leftJoin("user_base u", "u.uuid=icr.user_uuid")
            ->field("icr.user_uuid,u.nickname,u.head_image_url,icr.like_count")
            ->order(["icr.total_talent_coin"=>"desc","icr.like_count"=>"desc","icr.update_time"=>"asc"])
            ->limit(0, 10)
            ->select()->toArray();
    }

    public function getSelfRank($userUuid)
    {
        $userRank = $this->where("user_uuid", $userUuid)->find();

        if ($userRank == null) {
            return 0;
        } else {
            $count1 = $this->where("total_talent_coin", ">", $userRank["total_talent_coin"])->count();
            $count2 = $this->where("total_talent_coin", $userRank["total_talent_coin"])
                ->where("like_count", ">", $userRank["like_count"])->count();
            $count3 = $this->where("total_talent_coin", $userRank["total_talent_coin"])
                ->where("like_count", $userRank["like_count"])
                ->where("update_time", "<", $userRank["update_time"])
                ->count();
            return $count1+$count2+$count3+1;
        }
    }
}