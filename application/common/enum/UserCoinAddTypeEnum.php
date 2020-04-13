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

    const CUMULATIVE_SIGN = 5;
    const CUMULATIVE_SIGN_DESC = "累计签到";

    const CONTINUOUS_SIGN_3_DAY = 6;
    const CONTINUOUS_SIGN_3_DAY_DESC = "连续签到3天";

    const CONTINUOUS_SIGN_7_DAY = 7;
    const CONTINUOUS_SIGN_7_DAY_DESC = "连续签到7天";

    const CONTINUOUS_SIGN_15_DAY = 8;
    const CONTINUOUS_SIGN_15_DAY_DESC = "连续签到15天";

    const CONTINUOUS_SIGN_30_DAY = 9;
    const CONTINUOUS_SIGN_30_DAY_DESC = "连续签到30天";

    const JOIN_INTERNAL_COMPETITION = 10;
    const JOIN_INTERNAL_COMPETITION_DESC = "参与DE内部大赛";

    const INTERNAL_COMPETITION_WIN = 11;
    const INTERNAL_COMPETITION_WIN_DESC = "DE内部大赛取得名次";

    const PK_GROUP_FAIL = 12;
    const PK_GROUP_FAIL_DESC = "PK成团失败";
}