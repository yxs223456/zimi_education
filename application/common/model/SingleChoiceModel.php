<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-16
 * Time: 15:18
 */

namespace app\common\model;

use app\common\enum\DbIsDeleteEnum;
use app\common\enum\QuestionIsUseEnum;

class SingleChoiceModel extends Base
{
    protected $table = 'single_choice';

    public function getRandomSingleChoiceUuid($difficultyLevel, $count)
    {
        return $this->where("difficulty_level", $difficultyLevel)
            ->where("is_use", QuestionIsUseEnum::YES)
            ->where("is_delete", DbIsDeleteEnum::NO)
            ->limit($count)
            ->column("uuid");
    }

    public function getByUuidsAndOrderByDifficultyLevel(array $uuids)
    {
        return $this->whereIn("uuid", $uuids)
            ->order("difficulty_level", "asc")
            ->select();
    }
}