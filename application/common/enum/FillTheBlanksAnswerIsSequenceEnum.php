<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-04-05
 * Time: 12:13
 */

namespace app\common\enum;

/**
 * 填空题多个空是否有顺序
 * Class FillTheBlanksAnswerIsSequenceEnum
 * @package app\common\enum
 */
class FillTheBlanksAnswerIsSequenceEnum
{
    use EnumTrait;

    const YES = 1;
    const YES_DSC = "有顺序";

    const NO = 0;
    const NO_DESC = "无序";
}