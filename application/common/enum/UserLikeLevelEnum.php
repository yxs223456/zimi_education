<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-06-01
 * Time: 19:50
 */

namespace app\common\enum;

/**
 * 用户点赞等级
 * Class UserLikeLevelEnum
 * @package app\common\enum
 */
class UserLikeLevelEnum
{
    use EnumTrait;

    const ONE = 1;
    const ONE_DSC = "铁手指";

    const TWO = 2;
    const TWO_DESC = "铜手指";

    const THREE = 3;
    const THREE_DESC = "银手指";

    const FOUR = 4;
    const FOUR_DESC = "金手指";
}