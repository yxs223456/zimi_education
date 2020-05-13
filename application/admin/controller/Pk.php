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
use app\common\model\NewsModel;
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

                case "name":
                    $whereSql .= " and name like '%$value%'";
                    break;

                case "status":
                    $whereSql .= " and status = $value";
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
                //审核通过后，报名截止时间为审核第二日 24 点。
                $pk->join_deadline = strtotime(date("Y-m-d", time() + 86400) . "23:59:59");
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

            $redis = Redis::factory();
            //缓存用户信息
            if ($status == PkStatusEnum::AUDIT_FAIL && isset($newUser)) {
                cacheUserInfoByToken($newUser, $redis);
            }

            //消息纪录和推送
            if (isset($newUser)) {
                $newsModel =  new NewsModel();
                if ($status == PkStatusEnum::AUDIT_FAIL) {
                    //纪录消息
                    $content = "很遗憾你发起的 PK 未通过审核，可以自我检讨 一下是否标题起的不符合规范呢。";
                    $newsModel->addNews($newUser["uuid"], $content);

                    //推送消息
                    $title = "你的审核结果出来啦，快来看看吧！";
                    createUnicastPushTask($newUser["os"], $newUser["uuid"], $content, "", [], $redis, $title);
                } else {
                    //纪录消息
                    $content = "你发起的 PK 已成功通过审核，可邀请学员报名参加答题。";
                    $newsModel->addNews($newUser["uuid"], $content);

                    //推送消息
                    $title = "你的审核结果出来啦，快来看看吧！";
                    createUnicastPushTask($newUser["os"], $newUser["uuid"], $content, "", [], $redis, $title);
                }
            }


            $this->success("审核成功");
        } catch (\PDOException $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }


    }
}