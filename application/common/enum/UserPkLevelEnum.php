<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-04-20
 * Time: 18:47
 */

namespace app\common\enum;

/**
 * 用户PK等级
 * Class UserPkLevelEnum
 * @package app\common\enum
 */
class UserPkLevelEnum
{
    use EnumTrait;

    const ONE = 1;
    const ONE_DSC = "PK新秀";

    const TWO = 2;
    const TWO_DESC = "PK达人";

    const THREE = 3;
    const THREE_DESC = "PK大师";

    const FOUR = 4;
    const FOUR_DESC = "PK王";
}