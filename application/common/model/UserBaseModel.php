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

    //通过uuid获取用户
    public function getUserByUuid($uuid)
    {
        return $this->where("uuid", $uuid)->find();
    }

    //通过手机号获取用户
    public function getUserByPhone($phone)
    {
        return $this->where("phone", $phone)->find();
    }

    //通过手机号获取用户
    public function getUserByInviteCode($inviteCode)
    {
        return $this->where("invite_code", $inviteCode)->find();
    }

    //通过微信unionid获取用户
    public function getUserByUnionid($unionid)
    {
        return $this->where("unionid", $unionid)->find();
    }

    //用户邀请数+1
    public function addUserInviteCountByUuid($uuid)
    {
        $this->where("uuid", $uuid)->setInc("invite_count", 1);
    }

    public function updateNoviceTestStatusAndGetUser($uuid, $isShow = 0)
    {
        //修改新手测试显示状态
        $user = $this->findByUuid($uuid);
        $user->novice_test_is_show = $isShow;
        $user->update_time = time();
        $user->save();

        return $user;
    }
}