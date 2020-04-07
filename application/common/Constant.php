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
    //默认昵称
    const DEFAULT_HEAD_IMAGE_URL = "";

    //默认头像
    const DEFAULT_NICKNAME = "";

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

    //app版本信息
    const PACKAGE_INFO = [
        "ios" => [
            "current_version" => "v1.0.0",
            "history_version" => [

                [
                    "version" => "v1.0.0",
                    "forced" => false
                ],
            ],
        ],
        "android" => [
            "current_version" => "v1.0.0",
            "history_version" => [
                [
                    "version" => "v1.0.0",
                    "forced" => false
                ],
            ]
        ],
    ];

    //连续签到奖励
    const CONTINUOUS_SIGN_REWARD = [
        [
            "condition" => 3,
            "coin" => 5,
        ],
        [
            "condition" => 7,
            "coin" => 10,
        ],
        [
            "condition" => 15,
            "coin" => 20,
        ],
        [
            "condition" => 30,
            "coin" => 100,
        ],
    ];

    //每月可以获取累计签到奖励的天数
    const CUMULATIVE_SIGN_DAYS = 20;

    //累计签到达标书币奖励数量
    const CUMULATIVE_SIGN_COIN = 40;

    //自主学习模块填空题数目
    const STUDY_FILL_THE_BLANKS_COUNT = 30;

    //自主学习模块单选题数目
    const STUDY_SINGLE_CHOICE_COUNT = 30;

    //自主学习模块判断题数目
    const STUDY_TRUE_FALSE_QUESTION_COUNT = 30;

    //综合测试模块填空题数目
    const SYNTHESIZE_FILL_THE_BLANKS_COUNT = 15;

    //综合测试模块单选题数目
    const SYNTHESIZE_SINGLE_CHOICE_COUNT = 15;

    //综合测试模块判断题数目
    const SYNTHESIZE_TRUE_FALSE_QUESTION_COUNT = 5;

    //pk合法参加人数
    const PK_VALID_PEOPLE_NUM_MIN = 3;
    const PK_VALID_PEOPLE_NUM_MAX = 10;

    //pk有效截止时间
    const PK_VALID_DURATION_HOURS_MIN = 24;
    const PK_VALID_DURATION_HOURS_MAX = 72;

    //发起不同类型挑战消耗书币数
    const PK_NOVICE_INIT_COIN = 20;
    const PK_SIMPLE_INIT_COIN = 40;
    const PK_DIFFICULTY_INIT_COIN = 60;
    const PK_GOD_INIT_COIN = 100;

    //参与不同类型挑战消耗书笔数
    const PK_NOVICE_JOIN_COIN = 10;
    const PK_SIMPLE_JOIN_COIN = 20;
    const PK_DIFFICULTY_JOIN_COIN = 30;
    const PK_GOD_JOIN_COIN = 50;

    //pk赛题目数量
    const PK_QUESTION_COUNT = 30;

    //pk审核等待最长时间72小时，审核超时自动流局
    const PK_AUDIT_WAIT_TIME = 72 * 3600;

    //pk等待加入最长时间72小时， 超过72小时人数不足自动流局
    const PK_WAIT_JOIN_TIME = 72 * 3600;

    //参与DE大赛奖励
    const JOIN_INTERNAL_COMPETITION_REWARD = [
        "coin" => 10,
        "pk_coin" => 10,
        "talent_coin" => 1,
    ];
}