<?php
/**
 * Created by PhpStorm.
 * User: yangxs
 * Date: 2018/9/18
 * Time: 17:44
 */
namespace app\common;

class AppException extends \Exception
{
    const COM_PARAMS_ERR = [1, "请求参数错误"];
    const COM_FILE_ERR = [2, "上传文件不存在或超过服务器限制"];
    const COM_DATE_ERR = [3, "日期格式错误"];
    const COM_MOBILE_ERR = [4, "手机号格式错误"];
    const COM_ADDRESS_ERR = [5, "地址信息不全"];
    const COM_INVALID = [6, "非法请求"];
    const COM_APP_NOT_ONLINE = [7, "产品未上架"];

    const USER_NOT_LOGIN = [1000, "您还未登录"];
    const USER_TOKEN_ERR = [1001, "登录信息已过期,请重新登录"];
    const USER_PASSWORD_FORMAT_ERROR = [1002, "密码格式错误"];
    const USER_INVITE_CODE_NOT_EXISTS = [1003, "邀请码不存在"];
    const USER_PHONE_EXISTS_ALREADY = [1004, "手机号已被注册"];
    const USER_PHONE_VERIFY_CODE_ERROR = [1005, "验证码错误"];
    const USER_NOT_EXISTS = [1006, "用户不存在"];
    const USER_PASSWORD_ERROR = [1007, "密码错误"];
    const USER_CREATE_ERROR = [1008, "创建用户失败"];
    const USER_PARENT_NOT_ALLOW_MODIFY = [1009, "邀请人不允许修改"];
    const USER_MODIFY_ERROR = [1010, "信息修改失败"];
    const USER_INFO_NOT_MODIFY = [1011, "没有修改任何个人信息"];
    const USER_SIGN_ALREADY = [1012, "今日已签到"];
    const USER_NOVICE_TEST_ALREADY = [1013, "你已经做过新手测试啦"];
    const USER_COIN_NOT_ENOUGH = [1014, "书币数量不足"];
    const USER_BIND_PHONE_KEY_TIMEOUT = [1015, "微信授权信息过期，请重新登录绑定手机号"];
    const USER_BIND_WE_CHAT_ALREADY = [1016, "该手机号已绑定其他微信账号"];

    const WE_CHAT_GET_ACCESS_TOKEN_ERROR = [2000, "获取用户微信信息失败"];
    const WE_CHAT_NOT_BIND_USER = [2001, "当前微信号还没有绑定任何账号,请先使用手机号注册"];
    const WE_CHAT_BIND_ALREADY = [2002, "当前微信号已绑定其他手机账号"];

    const QUESTION_WRITING_NOT_EXISTS = [3000, "作文题目不存在"];

    const PK_STATUS_NOT_WAIT_JOIN = [4000, "无法加入当前pk"];
    const PK_NOT_EXISTS = [4001, "pk不存在"];
    const PK_JOIN_ALREADY = [4002, "你已经加入了当前PK请不要重复申请"];
    const PK_PEOPLE_ENOUGH = [4003, "PK人数已满请申请其他PK吧"];
    const PK_STATUS_NOT_UNDERWAY = [4004, "PK状态不为进行中无法提交答案"];
    const PK_NOT_JOIN = [4005, "你没有参与当前PK"];
    const PK_SUBMIT_ANSWERS_ALREADY = [4006, "你已经提交过答案无法重复提交"];

    const INTERNAL_COMPETITION_NOT_EXISTS = [5000, "内部大赛不存在"];
    const INTERNAL_COMPETITION_JOIN_ALREADY = [5001, "你已经参与该项大赛"];
    const INTERNAL_COMPETITION_STATUS_NOT_APPLYING = [5002, "大赛已过报名时间，下届记得准时呦~"];
    const INTERNAL_COMPETITION_USER_LEVEL_LOW = [5003, "你的等级不满足条件，快去答题升级吧"];
    const INTERNAL_COMPETITION_NOT_JOIN = [5004, "你没有参与该项大赛"];
    const INTERNAL_COMPETITION_SUBMIT_ANSWER_ALREADY = [5005, "你已经提交了答案，不能再修改"];
    const INTERNAL_COMPETITION_SUBMIT_ANSWER_TIMEOUT = [5006, "本次大赛已过提交作品截止时间"];
    const INTERNAL_COMPETITION_ANSWER_EMPTY = [5007, "作品不能为空"];

    const SYNTHESIZE_NOT_EXISTS = [6000, "综合测试不存在"];
    const SYNTHESIZE_SUBMIT_ANSWER_ALREADY = [6001, "综合测试答案已提交"];

    public static function factory($errConst)
    {
        $e = new self($errConst[1], $errConst[0]);
        return $e;
    }
}