<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-06-01
 * Time: 19:50
 */

namespace app\common\enum;

/**
 * 用户签到等级
 * Class UserSignLevelEnum
 * @package app\common\enum
 */
class UserSignLevelEnum
{
    use EnumTrait;

    const ONE = 1;
    const ONE_DSC = "签到新人";

    const TWO = 2;
    const TWO_DESC = "签到能手";

    const THREE = 3;
    const THREE_DESC = "签到达人";
}