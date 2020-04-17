<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-26
 * Time: 21:45
 */

namespace app\common\model;

class InternalCompetitionJoinModel extends Base
{
    protected $table = 'internal_competition_join';

    public function findByUserAndCompetition($userUuid, $competitionUuid)
    {
        return $this->where("c_uuid", $competitionUuid)
            ->where("user_uuid", $userUuid)
            ->find();
    }

    public function getByUserAndCompetitions($userUuid, array $competitionUuids)
    {
        return $this->where("user_uuid", $userUuid)
            ->whereIn("c_uuid", $competitionUuids)
            ->select();
    }

    public function getJoinUserInfoList($competitionUuid, $pageNum, $pageSize)
    {
        return $this->alias('icj')
            ->leftJoin("user_base u", "u.uuid=icj.user_uuid")
            ->where("icj.c_uuid", $competitionUuid)
            ->field("u.nickname,u.head_image_url,icj.rank,icj.score")
            ->order(["icj.rank"=>"desc","icj.id"=>"asc"])
            ->limit(($pageNum-1)*$pageSize, $pageSize)
            ->select()->toArray();
    }

    public function getWinUserInfoList($competitionUuid)
    {
        return $this->alias('icj')
            ->leftJoin("user_base u", "u.uuid=icj.user_uuid")
            ->where("icj.c_uuid", $competitionUuid)
            ->where("icj.rank", ">", 0)
            ->order("rank", "desc")
            ->field("u.nickname,u.head_image_url,u.uuid,icj.rank")
            ->select()->toArray();
    }
}