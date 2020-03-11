<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-11
 * Time: 14:47
 */

namespace app\common\enum;

/**
 * 题目难度
 * Class QuestionDifficultyLevelEnum
 * @package app\common\enum
 */
class QuestionDifficultyLevelEnum
{
    use EnumTrait;

    const ONE = 1;
    const ONE_DSC = "1星";

    const TWO = 2;
    const TWO_DESC = "2星";

    const THREE = 3;
    const THREE_DESC = "3星";

    const FOUR = 4;
    const FOUR_DESC = "4星";

    const FIVE = 5;
    const FIVE_DESC = "5星";

    const SIX = 6;
    const SIX_DESC = "6星";
}