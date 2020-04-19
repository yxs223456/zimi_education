<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-04-14
 * Time: 15:24
 */

namespace app\common\model;

use think\Db;

class UserPkRankModel extends Base
{
    protected $table = 'user_pk_rank';

    public function findByUserUuidAndType($userUuid, $type)
    {
        return $this->where("user_uuid", $userUuid)->where("type", $type)->find();
    }

    public function addPkCoin($userUuid, $type, $pkCoin)
    {
        $userPkRank = $this->findByUserUuidAndType($userUuid, $type);
        if ($userPkRank) {
            Db::name($this->table)->where("user_uuid", $userUuid)
                ->where("type", $type)
                ->inc("total_pk_coin", $pkCoin)
                ->update(["update_time"=>time()]);
        } else {
            $internalCompetitionRankData = [
                "user_uuid" => $userUuid,
                "type" => $type,
                "total_pk_coin" => $pkCoin,
                "create_time" => time(),
                "update_time" => time(),
            ];
            //使用save会产生bug
            $this->insert($internalCompetitionRankData);
        }
    }

    public function getRank($type)
    {
        return $this->alias("upr")
            ->leftJoin("user_base u", "u.uuid=upr.user_uuid")
            ->where("upr.type", $type)
            ->field("upr.user_uuid,u.nickname,u.head_image_url,upr.total_pk_coin,upr.like_count")
            ->order(["upr.total_pk_coin"=>"desc","upr.like_count"=>"desc","upr.update_time"=>"asc"])
            ->limit(0, 10)
            ->select()->toArray();
    }

    public function getUserPkRank($userUuid, $type)
    {
        $userPkRank = $this->where("user_uuid", $userUuid)
            ->where("type", $type)
            ->find();

        if ($userPkRank == null) {
            return 0;
        } else {
            $count1 = $this->where("type", $type)
                ->where("total_pk_coin", ">", $userPkRank["total_pk_coin"])->count();
            $count2 = $this->where("type", $type)
                ->where("total_pk_coin", $userPkRank["total_pk_coin"])
                ->where("like_count", ">", $userPkRank["like_count"])->count();
            $count3 = $this->where("type", $type)
                ->where("total_pk_coin", $userPkRank["total_pk_coin"])
                ->where("like_count", $userPkRank["like_count"])
                ->where("update_time", "<", $userPkRank["update_time"])
                ->count();
            return $count1+$count2+$count3+1;
        }
    }
}