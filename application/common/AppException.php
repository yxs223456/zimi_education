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
    const COM_PARAMS_ERR = [2, '请求参数错误'];
    const COM_FILE_ERR = [3, '上传文件不存在或超过服务器限制'];
    const COM_DATE_ERR = [4, '日期格式错误'];
    const COM_MOBILE_ERR = [5, '手机号合格错误'];
    const COM_ADDRESS_ERR = [6, '地址信息不全'];

    const USER_NOT_LOGIN = [1000, '您还未登录'];
    const USER_TOKEN_ERR = [1000, '登录信息已过期,请重新登录'];
    const USER_NOT_EXISTS = [1002, '用户不存在'];
    const USER_INVITE_CODE_NOT_EXISTS = [1003, '邀请码不存在'];

    public static function factory($errConst)
    {
        $e = new self($errConst[1], $errConst[0]);
        return $e;
    }
}