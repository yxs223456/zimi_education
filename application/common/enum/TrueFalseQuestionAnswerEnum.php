<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-02-25
 * Time: 20:13
 */
namespace app\common\enum;

/**
 * 判断题答案
 * Class TrueFalseQuestionAnswerEnum
 * @package app\common\enum
 */
class TrueFalseQuestionAnswerEnum
{

    use EnumTrait;

    const DESC_TRUE = 1;
    const DESC_TRUE_DESC = "描述正确";

    const DESC_FALSE = 2;
    const DESC_FALSE_DESC = "描述错误";
}