<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-05-02
 * Time: 16:16
 */
namespace app\common\enum;

/**
 * 用户渠道
 * Class UserBaseChannelEnum
 * @package app\common\enum
 */
class UserBaseChannelEnum
{

    use EnumTrait;

    const WEB = "DEWeb";
    const WEB_DESC = "官网";

    const DS = "DEDS";
    const DS_DESC = "大山";
}