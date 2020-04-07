<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-04-07
 * Time: 15:34
 */

namespace app\common\enum;

/**
 * 综合测试评分是否完成
 * Class UserSynthesizeScoreIsFinishEnum
 * @package app\common\enum
 */
class UserSynthesizeScoreIsFinishEnum
{
    use EnumTrait;

    const YES = 1;
    const YES_DSC = "已完成";

    const NO = 0;
    const NO_DESC = "未完成";
}