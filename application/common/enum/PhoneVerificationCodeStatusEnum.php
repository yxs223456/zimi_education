<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-02-26
 * Time: 15:58
 */

namespace app\common\enum;

class PhoneVerificationCodeStatusEnum
{
    const VALID = 1;
    const HAS_BEEN_USED = 2;
    const INVALID = 3;
}