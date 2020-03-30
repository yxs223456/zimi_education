<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-30
 * Time: 10:55
 */

namespace app\common\enum;

/**
 * 用户是否提交DE内部大赛作品
 * Class CompetitionAnswerIsSubmitEnum
 * @package app\common\enum
 */
class CompetitionAnswerIsSubmitEnum
{
    use EnumTrait;

    const YES = 1;
    const YES_DSC = "已提交";

    const NO = 0;
    const NO_DESC = "未提交";
}