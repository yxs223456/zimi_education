<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-05-09
 * Time: 10:44
 */

namespace app\common\enum;

/**
 * 用户注销状态
 * Class UserCancelStatusEnum
 * @package app\common\enum
 */
class UserCancelStatusEnum
{

    use EnumTrait;

    const NONE = 0;
    const WEB_DESC = "未申请";

    const CHECKING = 1;
    const DS_DESC = "审核中";

    const CANCEL = 2;
    const CANCEL_DESC = "已注销";
}