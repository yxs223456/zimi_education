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

    const WE_CHAT_GET_ACCESS_TOKEN_ERROR = [2000, "获取用户微信信息失败"];
    const WE_CHAT_NOT_BIND_USER = [2001, "当前微信号还没有绑定任何账号,请先使用手机号注册"];
    const WE_CHAT_BIND_ALREADY = [2002, "当前微信号已绑定其他手机账号"];

    public static function factory($errConst)
    {
        $e = new self($errConst[1], $errConst[0]);
        return $e;
    }
}