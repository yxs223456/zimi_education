<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-05-25
 * Time: 14:47
 */

namespace app\common\enum;

/**
 * 消息类型
 * Class NewsTypeEnum
 * @package app\common\enum
 */
class NewsTypeEnum
{
    use EnumTrait;

    const SYSTEM = 1;
    const SYSTEM_DSC = "系统消息";

    const ACTIVITY = 2;
    const ACTIVITY_DESC = "活动消息";
}