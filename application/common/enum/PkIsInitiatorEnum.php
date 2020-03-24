<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-24
 * Time: 14:24
 */

namespace app\common\enum;

/**
 * 用户是否是pk发起人
 * Class PkIsInitiatorEnum
 * @package app\common\enum
 */
class PkIsInitiatorEnum
{
    use EnumTrait;

    const YES = 1;
    const YES_DSC = "是";

    const NO = 0;
    const NO_DESC = "不是";
}