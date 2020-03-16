<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-16
 * Time: 15:46
 */

namespace app\common\enum;

/**
 * 问题类型
 * Class QuestionTypeEnum
 * @package app\common\enum
 */
class QuestionTypeEnum
{
    use EnumTrait;

    const FILL_THE_BLANKS = 1;
    const FILL_THE_BLANKS_DSC = "填空题";

    const SINGLE_CHOICE = 2;
    const SINGLE_CHOICE_DESC = "单选题";

    const TRUE_FALSE_QUESTION = 3;
    const TRUE_FALSE_QUESTION_DESC = "判断题";

    const WRITING = 4;
    const WRITING_DESC = "作文题";
}