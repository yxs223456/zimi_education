<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-02-27
 * Time: 20:51
 */

namespace app\common\enum;

/**
 * 题目是否加入题库
 * Class QuestionIsUseEnum
 * @package app\common\enum
 */
class QuestionIsUseEnum
{
    use EnumTrait;

    const YES = 1;
    const YES_DESC = "使用中";

    const NO = 0;
    const NO_DESC = "未使用";
}