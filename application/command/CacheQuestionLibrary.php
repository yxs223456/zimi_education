<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-16
 * Time: 15:40
 */

namespace app\command;

use app\common\Constant;
use app\common\enum\UserCoinAddTypeEnum;
use app\common\helper\Redis;
use app\common\model\UserBaseModel;
use app\common\model\UserCoinLogModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\facade\Log;

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

    }
}