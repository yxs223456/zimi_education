<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-16
 * Time: 16:48
 */

namespace app\common\model;

use app\common\enum\DbIsDeleteEnum;
use app\common\enum\QuestionIsUseEnum;

class WritingModel extends Base
{
    protected $table = 'writing';

    public function getAllUuid($difficultyLevel)
    {
        return $this->where("difficulty_level", $difficultyLevel)
            ->where("is_use", QuestionIsUseEnum::YES)
            ->where("is_delete", DbIsDeleteEnum::NO)
            ->column("uuid");
    }

    public function getRandomUuid($difficultyLevel)
    {
        return $this->where("difficulty_level", $difficultyLevel)
            ->where("is_use", QuestionIsUseEnum::YES)
            ->where("is_delete", DbIsDeleteEnum::NO)
            ->field("uuid")
            ->find();
    }

    public function getByUuid($uuid)
    {
        return $this->where("uuid", $uuid)
            ->find();
    }
}