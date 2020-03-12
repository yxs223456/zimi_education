<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-12
 * Time: 11:49
 */

namespace app\common\model;

class UserCoinLogModel extends Base
{
    protected $table = 'user_coin_log';

    public function getByUserUuidAndAddType($userUuid, $addType)
    {
        return $this->where("user_uuid", $userUuid)
            ->where("add_type", $addType)
            ->find();
    }

}