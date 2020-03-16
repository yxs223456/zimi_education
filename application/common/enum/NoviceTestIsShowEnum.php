<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-16
 * Time: 11:46
 */

namespace app\common\enum;

/**
 * 用户新手测试图标是否显示
 * Class NoviceTestIsShowEnum
 * @package app\common\enum
 */
class NoviceTestIsShowEnum
{
    use EnumTrait;

    const YES = 1;
    const YES_DSC = "显示";

    const NO = 0;
    const NO_DESC = "不显示";
}