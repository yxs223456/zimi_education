<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-02-25
 * Time: 15:04
 */

namespace app\admin\service;

use app\admin\model\WritingLibraryModel;
use app\common\enum\DbIsDeleteEnum;
use app\common\enum\QuestionIsUseEnum;

class WritingLibraryService extends Base
{

    public function __construct()
    {
        parent::__construct();
        $this->currentModel = new WritingLibraryModel();
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
}