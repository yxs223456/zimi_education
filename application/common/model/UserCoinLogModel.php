<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-12
 * Time: 11:49
 */

namespace app\common\model;

use app\common\enum\UserCoinAddTypeEnum;
use app\common\enum\UserCoinLogTypeEnum;

class UserCoinLogModel extends Base
{
    protected $table = 'user_coin_log';

    public function getList($userUuid, $pageNum, $pageSize)
    {
        return $this->where("user_uuid", $userUuid)
            ->order("id", "desc")
            ->limit(($pageNum-1)*$pageSize, $pageSize)
            ->select()->toArray();
    }

    public function getByUserUuidAndAddType($userUuid, $addType)
    {
        return $this->where("user_uuid", $userUuid)
            ->where("add_type", $addType)
            ->find();
    }

    public function getByUserUuidAndAddTypes($userUuid, array $addTypes)
    {
        return $this->where("user_uuid", $userUuid)
            ->whereIn("add_type", $addTypes)
            ->select();
    }

    //今日通过分享获取书币次数
    public function todayCountFromShare($userUuid)
    {
        return $this->where("user_uuid", $userUuid)
            ->where("add_type", UserCoinAddTypeEnum::SHARE)
            ->where("create_date", date("Y-m-d"))
            ->count();
    }

    //纪录书币增加流水，外层需开启数据库事务
    public function recordAddLog($userUuid, $addType, $num, $beforeNum, $afterNum, $detailNode, $addUuid = "")
    {
        $now = time();

        $logData = [
            "user_uuid" => $userUuid,
            "type" => UserCoinLogTypeEnum::ADD,
            "add_type" => $addType,
            "add_uuid" => $addUuid,
            "num" => $num,
            "before_num" => $beforeNum,
            "after_num" => $afterNum,
            "detail_note" => $detailNode,
            "create_date" => date("Y-m-d", $now),
            "create_time" => $now,
            "update_time" => $now,
        ];
        $this->insert($logData);
    }

    //纪录书币消耗流水，外层需开启数据库事务
    public function recordReduceLog($userUuid, $reduceType, $num, $beforeNum, $afterNum, $detailNode, $reduceUuid = "")
    {
        $now = time();

        $logData = [
            "user_uuid" => $userUuid,
            "type" => UserCoinLogTypeEnum::REDUCE,
            "reduce_type" => $reduceType,
            "reduce_uuid" => $reduceUuid,
            "num" => $num,
            "before_num" => $beforeNum,
            "after_num" => $afterNum,
            "detail_note" => $detailNode,
            "create_date" => date("Y-m-d", $now),
            "create_time" => $now,
            "update_time" => $now,
        ];
        $this->insert($logData);
    }

    //用户当月是否领取累计签到奖励
    public function checkReceiveCumulativeSignReward($userUuid)
    {
        $data = $this->where("user_uuid", $userUuid)
            ->where("add_type", UserCoinAddTypeEnum::CUMULATIVE_SIGN)
            ->order("id desc")
            ->find();

        if (!$data) {
            return false;
        }
        if (substr($data["create_date"], 0, 7) == date("Y-m")) {
            return true;
        }
        return false;
    }

    //最后一次通过连续签到或得奖励的信息
    public function getLastGetCoinFromContinuousSign($userUuid, $addType)
    {
        return $this->where("user_uuid", $userUuid)
            ->where("add_type", $addType)
            ->order("id desc")
            ->find();
    }
}