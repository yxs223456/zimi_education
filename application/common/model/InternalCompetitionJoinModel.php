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
}