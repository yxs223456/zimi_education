<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-06-01
 * Time: 19:50
 */

namespace app\common\enum;

/**
 * 用户分享等级
 * Class UserShareLevelEnum
 * @package app\common\enum
 */
class UserShareLevelEnum
{
    use EnumTrait;

    const ONE = 1;
    const ONE_DSC = "分享助手";

    const TWO = 2;
    const TWO_DESC = "分享达人";

    const THREE = 3;
    const THREE_DESC = "分享大使";
}