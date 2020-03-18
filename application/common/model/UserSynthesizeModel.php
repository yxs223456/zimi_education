<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-18
 * Time: 14:01
 */

namespace app\common\model;

use app\common\enum\UserSynthesizeIsFinishEnum;

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
}