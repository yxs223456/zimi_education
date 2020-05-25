<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-05-25
 * Time: 16:15
 */

namespace app\common\enum;

/**
 * 用户是否阅读过新手引导
 * Class UserNoviceGuideIsReadEnum
 * @package app\common\enum
 */
class UserNoviceGuideIsReadEnum
{
    use EnumTrait;

    const YES = 1;
    const YES_DSC = "已阅读";

    const NO = 0;
    const NO_DESC = "未阅读";
}