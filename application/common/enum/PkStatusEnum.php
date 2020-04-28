<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-24
 * Time: 11:30
 */


namespace app\common\enum;

/**
 * pk状态
 * Class PkStatusEnum
 * @package app\common\enum
 */
class PkStatusEnum
{
    use EnumTrait;

    const AUDITING = 1;
    const AUDITING_DSC = "待审核";

    const AUDIT_TIMEOUT = 2;
    const AUDIT_TIMEOUT_DESC = "审核超时";

    const WAIT_JOIN = 3;
    const WAIT_JOIN_DESC = "待加入";

    const WAIT_JOIN_TIMEOUT = 4;
    const WAIT_JOIN_TIMEOUT_DESC = "流局";

    const UNDERWAY = 5;
    const UNDERWAY_DESC = "进行中";

    const FINISH = 6;
    const FINISH_DESC = "结束";

    const AUDIT_FAIL = 7;
    const AUDIT_FAIL_DESC = "审核不通过";
}