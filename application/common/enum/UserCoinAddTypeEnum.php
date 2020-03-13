<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-12
 * Time: 13:43
 */

namespace app\common\enum;

/**
 * 用户书币来源
 * Class UserCoinAddTypeEnum
 * @package app\common\enum
 */
class UserCoinAddTypeEnum
{
    use EnumTrait;

    const USER_INFO = 1;
    const USER_INFO_DSC = "完善个人资料";

    const PARENT_INVITE_CODE = 2;
    const PARENT_INVITE_CODE_DESC = "填写学员邀请码";

    const BIND_WE_CHAT = 3;
    const BIND_WE_CHAT_DESC = "绑定微信";

    const SHARE = 4;
    const SHARE_DESC = "分享有礼";

}