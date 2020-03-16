<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-16
 * Time: 15:40
 */

namespace app\command;

use app\common\enum\QuestionTypeEnum;
use app\common\helper\Redis;
use app\common\model\FillTheBlanksModel;
use app\common\model\SingleChoiceModel;
use app\common\model\TrueFalseQuestionModel;
use app\common\model\WritingModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class CacheQuestionLibrary extends Command
{
    protected function configure()
    {
        // setName 设置命令行名称
        // setDescription 设置命令行描述
        $this->setName('de_education:cacheQuestionLibrary')
            ->setDescription('cache question library');
    }

    protected function execute(Input $input, Output $output)
    {
        $redis = Redis::factory();

        $cacheQuestionLibraryInfo = getCacheQuestionLibraryList($redis);

        if ($cacheQuestionLibraryInfo == null || empty($cacheQuestionLibraryInfo[1])) {
            $redis->close();
            return;
        }

        $cacheInfo = json_decode($cacheQuestionLibraryInfo[1], true);
        if (empty($cacheInfo["question_type"]) || empty($cacheInfo["difficulty_level"])) {
            return;
        }

        $this->doWork($cacheInfo["question_type"], $cacheInfo["difficulty_level"], $redis);
        $redis->close();
    }

    protected function doWork($questionType, $difficultyLevel, $redis)
    {
        switch ($questionType) {
            case QuestionTypeEnum::FILL_THE_BLANKS:
                $this->cacheFillTheBlanksLibrary($difficultyLevel, $redis);
                break;
            case QuestionTypeEnum::SINGLE_CHOICE:
                $this->cacheSingleChoiceLibrary($difficultyLevel, $redis);
                break;
            case QuestionTypeEnum::TRUE_FALSE_QUESTION:
                $this->cacheTrueFalseQuestionLibrary($difficultyLevel, $redis);
                break;
            case QuestionTypeEnum::WRITING:
                $this->cacheWritingLibrary($difficultyLevel, $redis);
                break;
        }
    }

    protected function cacheFillTheBlanksLibrary($difficultyLevel, $redis)
    {
        $model = new FillTheBlanksModel();
        $allUuids = $model->getAllUuid($difficultyLevel);
        addAllFillTheBlanks($allUuids, $difficultyLevel, $redis);
    }

    protected function cacheSingleChoiceLibrary($difficultyLevel, $redis)
    {
        $model = new SingleChoiceModel();
        $allUuids = $model->getAllUuid($difficultyLevel);
        addAllSingleChoice($allUuids, $difficultyLevel, $redis);
    }

    protected function cacheTrueFalseQuestionLibrary($difficultyLevel, $redis)
    {
        $model = new TrueFalseQuestionModel();
        $allUuids = $model->getAllUuid($difficultyLevel);
        addAllTrueFalseQuestion($allUuids, $difficultyLevel, $redis);
    }

    protected function cacheWritingLibrary($difficultyLevel, $redis)
    {
        $model = new WritingModel();
        $allUuids = $model->getAllUuid($difficultyLevel);
        addAllWriting($allUuids, $difficultyLevel, $redis);
    }
}