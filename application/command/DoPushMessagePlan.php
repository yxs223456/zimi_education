<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-06-03
 * Time: 16:02
 */

namespace app\command;

use app\common\enum\ActivityNewsTargetPageTypeEnum;
use app\common\enum\NewsIsPushAlreadyEnum;
use app\common\enum\NewsIsPushEnum;
use app\common\helper\Redis;
use app\common\model\SystemNewsModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class DoPushMessagePlan extends Command
{
    protected function configure()
    {
        // setName 设置命令行名称
        // setDescription 设置命令行描述
        $this->setName('de_education:doPushMessagePlan')
            ->setDescription('do something');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->doWork();

        sleep(10);
    }

    protected function doWork()
    {
        $systemNewsModel = new SystemNewsModel();
        do {
            $waitPushNews = $systemNewsModel
                ->where("is_push", NewsIsPushEnum::YES)
                ->where("is_push_already", NewsIsPushAlreadyEnum::NO)
                ->where("push_time", "<=", time())
                ->find();

            if ($waitPushNews) {
                $redis = Redis::factory();
                createBroadcastPushTask($waitPushNews["content"],
                    $waitPushNews["push_time"],
                    ActivityNewsTargetPageTypeEnum::NONE,
                    [],
                    $redis,
                    "system_message");
                $redis->close();
                $waitPushNews->save(["is_push_already"=>NewsIsPushAlreadyEnum::YES]);
            }
        } while($waitPushNews);
    }
}