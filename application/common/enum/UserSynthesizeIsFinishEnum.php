<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-18
 * Time: 14:06
 */

namespace app\common\enum;

/**
 * 综合测试是否完成
 * Class UserSynthesizeIsFinishEnum
 * @package app\common\enum
 */
class UserSynthesizeIsFinishEnum
{
    use EnumTrait;

    const YES = 1;
    const YES_DSC = "已完成";

    const NO = 0;
    const NO_DESC = "未完成";
}