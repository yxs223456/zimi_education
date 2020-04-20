<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-04-10
 * Time: 10:07
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

class PkJoinTimeout extends Command
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

        do {
            //在规定时间内参与人数不足的pk，考虑5秒延时
            $pk = Db::name($pkModel->getTable())
                ->where("status", PkStatusEnum::WAIT_JOIN)
                ->where("join_deadline", "<", time()-5)
                ->where("need_num", ">", 0)
                ->find();

            if ($pk) {
                //报名人数未满3人流局结算。若报名人数满3人，报名学员进行pk
                if ($pk["current_num"] >= 3) {
                    $this->beginPk($pk);
                } else {
                    $this->joinTimeout($pk);
                }
            }

        } while(!!$pk);
    }

    private function joinTimeout($pk)
    {
        $pkModel = new PkModel();
        $pkJoinModel = new PkJoinModel();
        $userModel = new UserBaseModel();
        $userCoinLogModel = new UserCoinLogModel();

        $joins = Db::name($pkJoinModel->getTable())->where("pk_uuid", $pk["uuid"])->select();
        Db::startTrans();
        try {
            //退还DE币
            //增加用户DE币
            foreach ($joins as $join) {
                Db::name($userModel->getTable())
                    ->where("uuid", $join["user_uuid"])
                    ->inc("coin", $join["coin"])
                    ->update(["update_time"=>time()]);
            }
            //纪录DE币流水
            $userUuidArr = array_column($joins, "user_uuid");
            $newUsers = Db::name($userModel->getTable())
                ->whereIn("uuid", $userUuidArr)
                ->column("*", "uuid");
            $coinFlow = [];
            foreach ($joins as $join) {
                $coinFlow[] = [
                    "user_uuid" => $join["user_uuid"],
                    "type" => UserCoinLogTypeEnum::ADD,
                    "add_type" => UserCoinAddTypeEnum::PK_GROUP_FAIL,
                    "add_uuid" => $pk["uuid"],
                    "num" => $join["coin"],
                    "before_num" => $newUsers[$join["user_uuid"]]["coin"] - $join["coin"],
                    "after_num" => $newUsers[$join["user_uuid"]]["coin"],
                    "detail_note" => UserCoinAddTypeEnum::PK_GROUP_FAIL_DESC,
                    "create_date" => date("Y-m-d"),
                    "create_time" => time(),
                    "update_time" => time(),
                ];
            }
            if ($coinFlow) {
                Db::name($userCoinLogModel->getTable())->insertAll($coinFlow);
            }

            //修改pk状态为参与超时
            Db::name($pkModel->getTable())
                ->where("uuid", $pk["uuid"])
                ->update([
                    "status" => PkStatusEnum::WAIT_JOIN_TIMEOUT,
                    "update_time" => time()
                ]);

            Db::commit();

            //缓存用户
            $redis = Redis::factory();
            foreach ($newUsers as $newUser) {
                cacheUserInfoByToken($newUser, $redis);
            }
            $redis->close();

        } catch (\Throwable $e) {
            Db::rollback();
            Log::write("pk join timeout error:" . $e->getMessage(), "ERROR");
            throw $e;
        }
    }

    private function beginPk($pk)
    {
        $pkModel = new PkModel();

        //修改pk状态为参与超时
        Db::name($pkModel->getTable())
            ->where("uuid", $pk["uuid"])
            ->update([
                "status" => PkStatusEnum::UNDERWAY,
                "begin_time" => time(),
                "deadline" => strtotime(date("Y-m-d",time()+($pk["duration_hour"] * 3600))." 23:59:59"),
                "update_time" => time(),
            ]);
    }
}