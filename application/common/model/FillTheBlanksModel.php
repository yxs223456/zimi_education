<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-16
 * Time: 16:46
 */

namespace app\common\model;

use app\common\enum\DbIsDeleteEnum;
use app\common\enum\QuestionIsUseEnum;

class FillTheBlanksModel extends Base
{
    protected $table = 'fill_the_blanks';

    public function getRandomUuid($difficultyLevel, $count)
    {
        return $this->where("difficulty_level", $difficultyLevel)
            ->where("is_use", QuestionIsUseEnum::YES)
            ->where("is_delete", DbIsDeleteEnum::NO)
            ->limit($count)
            ->column("uuid");
    }

    public function getAllUuid($difficultyLevel)
    {
        return $this->where("difficulty_level", $difficultyLevel)
            ->where("is_use", QuestionIsUseEnum::YES)
            ->where("is_delete", DbIsDeleteEnum::NO)
            ->column("uuid");
    }

    public function getByUuids(array $uuids)
    {
        return $this->whereIn("uuid", $uuids)
            ->select();
    }

    public function getByUuidsAndOrderByDifficultyLevel(array $uuids)
    {
        return $this->whereIn("uuid", $uuids)
            ->order("difficulty_level", "asc")
            ->select();
    }
}