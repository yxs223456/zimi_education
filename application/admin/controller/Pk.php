<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-04-16
 * Time: 10:02
 */

namespace app\admin\controller;

use app\common\enum\PkStatusEnum;
use app\common\enum\PkTypeEnum;
use app\common\enum\UserCoinAddTypeEnum;
use app\common\enum\UserCoinLogTypeEnum;
use app\common\helper\Redis;
use think\Db;
use think\exception\PDOException;

class Pk extends Base
{
    public function convertRequestToWhereSql()
    {

        $whereSql = " 1=1 ";
        $pageMap = [];

        $params = input("param.");

        foreach ($params as $key => $value) {

            if ($value == "-999"
                || isNullOrEmpty($value))
                continue;

            switch ($key) {

                case "is_comment":
                    $whereSql .= " and is_comment = $value";
                    break;

            }

            $pageMap[$key] = $value;
            $this->assign($key, $value);

        }
        $data["whereSql"] = $whereSql;
        $data["pageMap"] = $pageMap;

        return $data;

    }

    public function pkList()
    {
        $condition = $this->convertRequestToWhereSql();
        $list = $this->pkService->getListByCondition($condition);
        foreach ($list as $item) {
            $item["typeDesc"] = PkTypeEnum::getEnumDescByValue($item["type"]);
            $item["statusDesc"] = PkStatusEnum::getEnumDescByValue($item["status"]);
        }
        $this->assign('list', $list);

        $pkStatus = PkStatusEnum::getAllList();
        $this->assign("pkStatus", $pkStatus);

        return $this->fetch("pkList");
    }

    public function check()
    {
        $uuid = input('param.uuid');
        $info = $this->pkService->findByMap(["uuid"=>$uuid]);
        $this->assign("info", $info);

        return $this->fetch();
    }

    public function doCheck()
    {
        $uuid = input("uuid");
        $status = input("status");
        $auditFailReason = input("audit_fail_reason");

        if (empty($uuid)) {
            $this->error('参数错误');
        }
        if (!in_array($status,[PkStatusEnum::WAIT_JOIN, PkStatusEnum::AUDIT_FAIL])) {
            $this->error('参数错误');
        }
        if ($status == PkStatusEnum::AUDIT_FAIL && $auditFailReason == "") {
            $this->error('请输入审核不通过原因');
        }
        Db::startTrans();
        try {
            $pk = $this->pkService->findByMap(["uuid"=>$uuid]);
            if ($pk == null) {
                $this->error('数据不存在');
            }

            $pk->status = $status;
            $pk->audit_time = time();
            if ($status == PkStatusEnum::WAIT_JOIN) {
                $pk->begin_time = time();
                $pk->deadline = time() + (3600 * $pk["duration_hour"]);
            } else if ($status == PkStatusEnum::AUDIT_FAIL) {
                $pk->audit_fail_reason = $auditFailReason;
            }

            $pk->save();

            //审核不通过流程
            if ($status == PkStatusEnum::AUDIT_FAIL) {
                //退还用户DE币
                Db::name("user_base")->where("uuid", $pk["initiator_uuid"])
                    ->setInc("coin", $pk["total_coin"]);
                $newUser = $this->userBaseService->findByMap(["uuid"=>$pk["initiator_uuid"]])->toArray();

                //纪录DE币流水
                $coinFlow = [
                    "user_uuid" => $pk["initiator_uuid"],
                    "type" => UserCoinLogTypeEnum::ADD,
                    "add_type" => UserCoinAddTypeEnum::PK_AUDIT_FAIL,
                    "add_uuid" => $uuid,
                    "num" => $pk["total_coin"],
                    "before_num" => $newUser["coin"] - $pk["total_coin"],
                    "after_num" => $newUser["coin"],
                    "detail_note" => UserCoinAddTypeEnum::PK_AUDIT_FAIL_DESC,
                    "create_date" => date("Y-m-d"),
                    "create_time" => time(),
                    "update_time" => time(),
                ];
                Db::name("user_coin_log")->insert($coinFlow);
            }

            Db::commit();

            //缓存用户信息
            if ($status == PkStatusEnum::AUDIT_FAIL && isset($newUser)) {
                $redis = Redis::factory();
                cacheUserInfoByToken($newUser, $redis);
            }
            $this->success("审核成功");
        } catch (\PDOException $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }


    }
}