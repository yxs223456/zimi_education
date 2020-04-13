<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-04-10
 * Time: 14:51
 */

namespace app\command;

use app\common\Constant;
use app\common\enum\PkStatusEnum;
use app\common\enum\UserCoinAddTypeEnum;
use app\common\enum\UserCoinLogTypeEnum;
use app\common\helper\Redis;
use app\common\model\PkJoinModel;
use app\common\model\PkModel;
use app\common\model\UserBaseModel;
use app\common\model\UserCoinLogModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\facade\Log;

class PkFinish extends Command
{
    protected function configure()
    {
        // setName 设置命令行名称
        // setDescription 设置命令行描述
        $this->setName('de_education:pkJoinTimeout')
            ->setDescription('pk join timeout');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->doWork();

        sleep(30);
    }

    protected function doWork()
    {
        $pkModel = new PkModel();
        $pkJoinModel = new PkJoinModel();
        $userModel = new UserBaseModel();

        do {
            $pk =

        } while();
    }
}