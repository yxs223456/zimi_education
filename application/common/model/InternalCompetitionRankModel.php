<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-04-08
 * Time: 18:30
 */

namespace app\common\model;

class InternalCompetitionRankModel extends Base
{
    protected $table = 'internal_competition_rank';

    public function findByUserUuid($userUuid)
    {
        return $this->where("user_uuid", $userUuid)->find();
    }
}