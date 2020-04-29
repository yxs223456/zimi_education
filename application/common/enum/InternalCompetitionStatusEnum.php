<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-26
 * Time: 22:03
 */

namespace app\common\enum;

/**
 * 内部大赛状态
 * Class InternalCompetitionStatusEnum
 * @package app\common\enum
 */
class InternalCompetitionStatusEnum
{
    use EnumTrait;

    const APPLYING = 1;
    const APPLYING_DSC = "报名中";

    const UNDERWAY = 2;
    const UNDERWAY_DESC = "进行中";

    const SUBMIT_ANSWER_FINISH = 3;
    const SUBMIT_ANSWER_FINISH_DESC = "答题结束";

    const FINISH = 4;
    const FINISH_DESC = "评分结束";

    const WAIT = 5;
    const WAIT_DESC = "未开始";
}