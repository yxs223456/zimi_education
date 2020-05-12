<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-05-11
 * Time: 17:03
 */

namespace app\command;

use app\common\helper\Redis;
use app\common\helper\UmengPush;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

class PushMessageToUser extends Command
{
    protected function configure()
    {
        // setName 设置命令行名称
        // setDescription 设置命令行描述
        $this->setName('de_education:pushMessage')
            ->setDescription('push message');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            $redis = Redis::factory();
            do {
                $pushTask = getPushTask($redis);
                if ($pushTask) {
                    $pushInfo = json_decode($pushTask, true);
                    switch ($pushInfo["type"]) {
                        case "unicast":
                            $this->unicastPush($pushInfo);
                            break;
                    }
                }
            } while($pushTask);
            $redis->close();
        } catch (\Throwable $e) {
            $msg = "file:".$e->getFile()."\n" .
                "line:".$e->getLine()."\n" .
                "message".$e->getMessage();
            Log::write($msg, "error");
        }
    }

    protected function unicastPush(array $params)
    {
        $umengPush = new UmengPush();
        if (strtolower($params["os"]) == "android") {
            $umengPush->sendAndroidUnicast($params["umengDeviceToken"], $params["title"], $params["content"]);
        } elseif (strtolower($params["os"]) == "ios") {
            $umengPush->sendIOSUnicast($params["umengDeviceToken"], $params["content"]);
        }
    }
}