<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-02-26
 * Time: 17:21
 */

namespace app\common;

class Constant
{
    //手机验证码有效期30分钟
    const PHONE_VERIFICATION_CODE_VALID_TIME = 1800;

    //任务中心，任务完成奖励书币数量
    const TASK_COIN_NUM = [
        "user_info" => 10,
        "parent_invite_code" => 10,
        "bind_we_chat" => 10,
        "share" => 2,
    ];

    //分享有礼每天允许完成次数
    const TASK_SHARE_DAILY_TIMES = 5;

    //app信息
    const PACKAGE_INFO = [
        "ios" => [

        ],
        "android"
    ];
}