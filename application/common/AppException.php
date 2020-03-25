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

    const WE_CHAT_GET_ACCESS_TOKEN_ERROR = [2000, "获取用户微信信息失败"];
    const WE_CHAT_NOT_BIND_USER = [2001, "当前微信号还没有绑定任何账号,请先使用手机号注册"];
    const WE_CHAT_BIND_ALREADY = [2002, "当前微信号已绑定其他手机账号"];

    const QUESTION_WRITING_NOT_EXISTS = [3000, "作文题目不存在"];

    const PK_STATUS_NOT_WAIT_JOIN = [4000, "无法加入当前pk"];
    const PK_NOT_EXISTS = [4001, "pk不存在"];
    const PK_JOIN_ALREADY = [4002, "你已经加入了当前PK请不要重复申请"];
    const PK_PEOPLE_ENOUGH = [4003, "PK人数已满请申请其他PK吧"];

    public static function factory($errConst)
    {
        $e = new self($errConst[1], $errConst[0]);
        return $e;
    }
}