<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-18
 * Time: 14:01
 */

namespace app\common\model;

use app\common\enum\UserSynthesizeIsFinishEnum;
use app\common\enum\UserSynthesizeScoreIsFinishEnum;

class UserSynthesizeModel extends Base
{
    protected $table = 'user_synthesize';

    public function getLastUnFinish($userUuid, $difficultyLevel)
    {
        return $this
            ->where("user_uuid", $userUuid)
            ->where("difficulty_level", $difficultyLevel)
            ->where("is_finish", UserSynthesizeIsFinishEnum::NO)
            ->order("id", "desc")
            ->find();
    }

    public function synthesizeReportCardList($userUuid, $difficultyLevel, $pageNum, $pageSize)
    {
        return $this->where("user_uuid", $userUuid)
            ->where("difficulty_level", $difficultyLevel)
            ->where("score_is_finish", UserSynthesizeScoreIsFinishEnum::YES)
            ->order("id", "desc")
            ->limit(($pageNum-1)*$pageSize, $pageSize)
            ->select()->toArray();
    }
}