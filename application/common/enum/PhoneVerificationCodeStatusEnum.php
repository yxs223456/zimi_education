<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-02-26
 * Time: 15:58
 */

namespace app\common\enum;

/**
 * 手机验证码状态
 * Class PhoneVerificationCodeStatusEnum
 * @package app\common\enum
 */
class PhoneVerificationCodeStatusEnum
{
    use EnumTrait;

    const VALID = 1;
    const VALID_DESC = "有效";

    const HAS_BEEN_USED = 2;
    const HAS_BEEN_USED_DESC = "已使用";

    const INVALID = 3;
    const INVALID_DESC = "无效";
}