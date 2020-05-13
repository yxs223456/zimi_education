<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-04-10
 * Time: 14:51
 */

namespace app\command;

use app\api\service\UserService;
use app\common\enum\PkIsInitiatorEnum;
use app\common\enum\PkStatusEnum;
use app\common\enum\UserCoinAddTypeEnum;
use app\common\enum\UserCoinLogTypeEnum;
use app\common\enum\UserPkCoinAddTypeEnum;
use app\common\enum\UserPkCoinLogTypeEnum;
use app\common\helper\Redis;
use app\common\model\PkJoinModel;
use app\common\model\PkModel;
use app\common\model\UserBaseModel;
use app\common\model\UserCoinLogModel;
use app\common\model\UserPkCoinLogModel;
use app\common\model\UserPkRankModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\facade\Log;

class PkTimeoutFinish extends Command
{
    protected function configure()
    {
        // setName 设置命令行名称
        // setDescription 设置命令行描述
        $this->setName('de_education:pkTimeoutFinish')
            ->setDescription('pk timeout finish');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->doWork();

        sleep(30);
    }

    protected function doWork()
    {
        $pkModel = new PkModel();

        do {
            //已经过了截止答题时间（延迟5秒）状态为进行中的pk
            $pk = Db::name($pkModel->getTable())
                ->where("status", PkStatusEnum::UNDERWAY)
                ->where("deadline", "<", time()-5)
                ->find();

            if ($pk == null) {
                break;
            }

            $redis = Redis::factory();
            pushPkFinishList($pk["uuid"], $redis);
            $redis->close();
        } while(!!$pk);
    }
}