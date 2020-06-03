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
                    switch ($pushTask["type"]) {
                        case "unicast":
                            $this->unicastPush($pushTask);
                            break;
                        case "broadcast":
                            $this->broadcastPush($pushTask);
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

    protected function broadcastPush(array $params)
    {
        $umengPush = new UmengPush();

        $result = $umengPush->sendAndroidBroadcast($params["title"], $params["content"], $params["targetPageType"], $params["pageConfig"], $params["messageType"]);
        Log::write("umeng android broadcast push result:" . $result);
        $result = $umengPush->sendIOSBroadcast($params["content"], $params["targetPageType"], $params["pageConfig"], $params["messageType"]);
        Log::write("umeng ios broadcast  push result:" . $result);
    }

    protected function unicastPush(array $params)
    {
        $umengPush = new UmengPush();

        if (strtolower($params["os"]) == "android") {
            $result = $umengPush->sendAndroidUnicast($params["userUuid"], $params["title"], $params["content"]);
            Log::write("umeng android unicast push result:" . $result);
        } elseif (strtolower($params["os"]) == "ios") {
            $result = $umengPush->sendIOSUnicast($params["userUuid"], $params["content"]);
            Log::write("umeng ios unicast push result:" . $result);
        }
    }
}