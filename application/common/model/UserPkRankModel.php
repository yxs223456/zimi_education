<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-04-14
 * Time: 15:24
 */

namespace app\common\model;

use think\Db;

class UserPkRankModel extends Base
{
    protected $table = 'user_pk_rank';

    public function findByUserUuidAndType($userUuid, $type)
    {
        return $this->where("user_uuid", $userUuid)->where("type", $type)->find();
    }

    public function addPkCoin($userUuid, $type, $pkCoin)
    {
        $userPkRank = $this->findByUserUuidAndType($userUuid, $type);
        if ($userPkRank) {
            Db::name($this->table)->where("user_uuid", $userUuid)
                ->where("type", $type)
                ->inc("total_pk_coin", $pkCoin)
                ->update(["update_time"=>time()]);
        } else {
            $internalCompetitionRankData = [
                "user_uuid" => $userUuid,
                "total_pk_coin" => $pkCoin,
                "create_time" => time(),
                "update_time" => time(),
            ];
            //使用save会产生bug
            $this->insert($internalCompetitionRankData);
        }
    }
}