<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-31
 * Time: 18:13
 */

namespace app\admin\service;

use app\common\model\InternalCompetitionJoinModel;

class InternalCompetitionJoinService extends Base
{

    public function __construct()
    {
        parent::__construct();
        $this->currentModel = new InternalCompetitionJoinModel();
    }

    public function getListByCondition($condition)
    {
        $list = $this->currentModel->alias("icj")
            ->leftJoin("internal_competition ic", "ic.uuid=icj.c_uuid")
            ->where($condition['whereSql'])
            ->field("icj.uuid,icj.question,icj.submit_answer_time,icj.is_comment,icj.score,icj.comment_time,ic.name")
            ->order(['icj.is_comment'=>'asc', 'icj.submit_answer_time'=>'asc'])
            ->paginate(\config("paginate.list_rows"), false,
                ["query" => $condition['pageMap']]);

        return $list;
    }
}