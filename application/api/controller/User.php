<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-02-26
 * Time: 14:49
 */

namespace app\api\controller;

use app\api\service\UserService;
use app\common\AppException;

class User extends Base
{
    protected $beforeActionList = [
        'checkAuth' => [
            'except' => 'getCodeForSignUp,signUp,getCodeForSignIn,signInByCode,signInByPassword,
            getCodeForResetPassword,resetPassword',
        ],
    ];

    //发送注册验证码
    public function getCodeForSignUp()
    {
        $phone = input("phone");
        if (!checkIsMobile($phone)) {
            throw AppException::factory(AppException::COM_MOBILE_ERR);
        }

        $userService = new UserService();
        $returnData = $userService->getCodeForSignUp($phone);

        return $this->jsonResponse($returnData);
    }

    //手机号注册
    public function signUp()
    {
        $phone = input("phone");
        $code = input("code", null);
        $password = input("password", null);

        if (!checkIsMobile($phone) || $code === null || $password === null) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $userService = new UserService();
        $returnData = $userService->singUp($phone, $code, $password);

        return $this->jsonResponse($returnData);
    }

    //发送注册验证码
    public function getCodeForSignIn()
    {
        $phone = input("phone");
        if (!checkIsMobile($phone)) {
            throw AppException::factory(AppException::COM_MOBILE_ERR);
        }

        $userService = new UserService();
        $returnData = $userService->getCodeForSignIn($phone);

        return $this->jsonResponse($returnData);
    }

    //手机验证码登录
    public function signInByCode()
    {
        $phone = input("phone");
        $code = input("code");

        if (!checkIsMobile($phone) || $code === null) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $userService = new UserService();
        $returnData = $userService->signInByCode($phone, $code);

        return $this->jsonResponse($returnData);
    }

    //手机密码登录
    public function signInByPassword()
    {
        $phone = input("phone");
        $password = input("password");

        if (!checkIsMobile($phone) || $password === null) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $userService = new UserService();
        $returnData = $userService->signInByPassword($phone, $password);

        return $this->jsonResponse($returnData);
    }

    //发送重置密码验证码
    public function getCodeForResetPassword()
    {
        $phone = input("phone");
        if (!checkIsMobile($phone)) {
            throw AppException::factory(AppException::COM_MOBILE_ERR);
        }

        $userService = new UserService();
        $returnData = $userService->getCodeForResetPassword($phone);

        return $this->jsonResponse($returnData);
    }

    //重置密码
    public function resetPassword()
    {
        $phone = input("phone");
        $code = input("code", null);
        $password = input("password", null);

        if (!checkIsMobile($phone) || $code === null || $password === null) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $userService = new UserService();
        $returnData = $userService->resetPassword($phone, $code, $password);

        return $this->jsonResponse($returnData);
    }
}