<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-02-26
 * Time: 16:15
 */

namespace app\common\model;

use app\common\enum\PhoneVerificationCodeStatusEnum;

class PhoneVerificationCodeModel extends Base
{
    protected $table = "phone_verification_code";

    //纪录手机验证码
    public function insertPhoneVerificationCode($phone, $code, $useType)
    {
        $time = time();
        $data = [
            "phone" => $phone,
            "code" => $code,
            "use_type" => $useType,
            "status" => PhoneVerificationCodeStatusEnum::VALID,
            "create_time" => $time,
            "update_time" => $time,
        ];
        $this->insert($data);
    }

    //获取最后一条验证码
    public function getLastPhoneVerificationCode($phone, $useType, $status)
    {
        $data = $this->where("phone", $phone)
            ->where("use_type", $useType)
            ->where("status", $status)
            ->order("id", "desc")
            ->find();

        return $data;
    }

    //修改验证码状态为无效
    public function updateStatusToInvalid($phone, $useType)
    {
        $updateData = [
            "status" => PhoneVerificationCodeStatusEnum::INVALID,
            "update_time" => time(),
        ];
        $this->where("phone", $phone)
            ->where("use_type", $useType)
            ->where("status", PhoneVerificationCodeStatusEnum::VALID)
            ->update($updateData);
    }

    //修改验证码状态为已使用
    public function updateStatusToHasBeenUsed($id)
    {
        $updateData = [
            "status" => PhoneVerificationCodeStatusEnum::HAS_BEEN_USED,
            "update_time" => time(),
        ];
        $this->where("id", $id)
            ->update($updateData);
    }

}