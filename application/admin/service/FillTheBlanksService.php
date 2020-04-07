<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-02-23
 * Time: 17:30
 */

namespace app\admin\service;

use app\admin\model\FillTheBlanksLibraryModel;
use app\common\enum\DbIsDeleteEnum;
use app\common\enum\QuestionIsUseEnum;

class FillTheBlanksService extends Base
{

    public function __construct()
    {
        parent::__construct();
        $this->currentModel = new FillTheBlanksLibraryModel();
    }

    public function allDifficultyLevelCount()
    {
        return $this->currentModel
            ->where("is_delete", DbIsDeleteEnum::NO)
            ->where("is_use", QuestionIsUseEnum::YES)
            ->group("difficulty_level")
            ->field("count(1) total, difficulty_level")
            ->select();
    }

    public function getByUuids(array $uuids)
    {
        return $this->currentModel
            ->whereIn("uuid", $uuids)
            ->select()
            ->toArray();
    }
}