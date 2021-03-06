<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-12
 * Time: 11:49
 */

namespace app\common\model;

use app\common\enum\UserTalentCoinLogTypeEnum;

class UserTalentCoinLogModel extends Base
{
    protected $table = 'user_talent_coin_log';

    public function getByUserUuidAndAddType($userUuid, $addType)
    {
        return $this->where("user_uuid", $userUuid)
            ->where("add_type", $addType)
            ->find();
    }

    //纪录才情值增加流水，外层需开启数据库事务
    public function recordAddLog($userUuid, $addType, $num, $beforeNum, $afterNum, $detailNode, $addUuid = "")
    {
        $logData = [
            "user_uuid" => $userUuid,
            "type" => UserTalentCoinLogTypeEnum::ADD,
            "add_type" => $addType,
            "add_uuid" => $addUuid,
            "num" => $num,
            "before_num" => $beforeNum,
            "after_num" => $afterNum,
            "detail_note" => $detailNode,
            "create_date" => date("Y-m-d"),
        ];
        $this->save($logData);
    }

    //纪录才情值消耗流水，外层需开启数据库事务
    public function recordReduceLog($userUuid, $reduceType, $num, $beforeNum, $afterNum, $detailNode, $reduceUuid = "")
    {
        $logData = [
            "user_uuid" => $userUuid,
            "type" => UserTalentCoinLogTypeEnum::REDUCE,
            "reduce_type" => $reduceType,
            "reduce_uuid" => $reduceUuid,
            "num" => $num,
            "before_num" => $beforeNum,
            "after_num" => $afterNum,
            "detail_note" => $detailNode,
            "create_date" => date("Y-m-d"),
        ];
        $this->save($logData);
    }
}