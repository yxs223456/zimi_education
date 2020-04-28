<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-26
 * Time: 21:37
 */

namespace app\common\model;

class InternalCompetitionModel extends Base
{
    protected $table = 'internal_competition';

    public function getList($sponsorId, $pageNum, $pageSize)
    {
        return $this
            ->where("sponsor_id", $sponsorId)
            ->whereTime("online_time", "<=", time())
            ->order("id", "desc")
            ->limit(($pageNum-1)*$pageSize, $pageSize)
            ->select();
    }
}