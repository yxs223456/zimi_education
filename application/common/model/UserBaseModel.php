<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-02-26
 * Time: 15:22
 */

namespace app\common\model;

class UserBaseModel extends Base
{

    protected $table = 'user_base';

    //通过手机号获取用户
    public function getUserByPhone($phone)
    {
        if ($phone == "") {
            return [];
        }

        return $this->where("phone", $phone)->find();
    }

    //通过手机号获取用户
    public function getUserByInviteCode($inviteCode)
    {
        if ($inviteCode == "") {
            return [];
        }

        return $this->where("invite_code", $inviteCode)->find();
    }

    //用户邀请数+1
    public function addUserInviteCountByUuid($uuid)
    {
        if ($uuid == "") {
            return;
        }

        $this->where("uuid", $uuid)->setInc("invite_count", 1);
    }
}