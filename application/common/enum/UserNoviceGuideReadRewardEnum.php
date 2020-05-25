<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-05-25
 * Time: 16:16
 */

namespace app\common\enum;

/**
 * 用户是否领取阅读新手引导奖励
 * Class UserNoviceGuideReadRewardEnum
 * @package app\common\enum
 */
class UserNoviceGuideReadRewardEnum
{
    use EnumTrait;

    const YES = 1;
    const YES_DSC = "已领取";

    const NO = 0;
    const NO_DESC = "未领取";
}