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
    const PK_GROUP_FAIL_DESC = "PK成团失败退还";

    const PK_WIN = 13;
    const PK_WIN_DESC = "PK获得名次";

    const PK_INITIATOR_WIN = 14;
    const PK_INITIATOR_WIN_DESC = "PK团长获得pk冠军奖励";

    const PK_AUDIT_FAIL = 15;
    const PK_AUDIT_FAIL_DESC = "PK标题审核不通过退还";

    const NOVICE_LEVEL_UP = 16;
    const NOVICE_LEVEL_UP_DESC = "摸底测试获得等级";

    const LEVEL_UP = 17;
    const LEVEL_UP_DESC = "获得星级称号";

    const INTERNAL_USER = 18;
    const INTERNAL_USER_DESC = "内测阶段赠送";

    const READ_NOVICE_GUIDE = 19;
    const READ_NOVICE_GUIDE_DESC = "阅读新手手册";
}