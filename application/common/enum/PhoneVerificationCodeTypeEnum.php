<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-02-26
 * Time: 15:55
 */

namespace app\common\enum;

/**
 * 验证码类型
 * Class PhoneVerificationCodeTypeEnum
 * @package app\common\enum
 */
class PhoneVerificationCodeTypeEnum
{

    use EnumTrait;

    const SIGN_UP = 1;
    const SIGN_UP_DESC = "注册";

    const SIGN_IN = 2;
    const SIGN_DESC = "登录";

    const RESET_PASSWORD = 3;
    const RESET_PASSWORD_DESC = "重置密码";
}