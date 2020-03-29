<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-26
 * Time: 22:01
 */

namespace app\common\enum;

/**
 * 内部大赛是否结束
 * Class InternalCompetitionIsFinishEnum
 * @package app\common\enum
 */
class InternalCompetitionIsFinishEnum
{
    use EnumTrait;

    const YES = 1;
    const YES_DSC = "已结束";

    const NO = 0;
    const NO_DESC = "没有结束";
}