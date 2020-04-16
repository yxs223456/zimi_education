<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-26
 * Time: 21:37
 */

namespace app\common\model;

use app\common\enum\InternalCompetitionIsFinishEnum;

class InternalCompetitionModel extends Base
{
    protected $table = 'internal_competition';

    public function getList($pageNum, $pageSize)
    {
        return $this
            ->whereTime("online_time", "<=", time())
            ->order("id", "desc")
            ->limit(($pageNum-1)*$pageSize, $pageSize)
            ->select();
    }

    public function competitionReportCard($pageNum, $pageSize)
    {
        return $this
            ->whereTime("is_finish", InternalCompetitionIsFinishEnum::YES)
            ->order("id", "desc")
            ->limit(($pageNum-1)*$pageSize, $pageSize)
            ->select()->toArray();
    }
}