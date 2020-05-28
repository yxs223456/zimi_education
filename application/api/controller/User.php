<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-02-26
 * Time: 14:49
 */

namespace app\api\controller;

use app\api\service\UserService;
use app\api\service\v1\AthleticsService;
use app\api\service\v1\UserWritingService;
use app\common\AppException;

class User extends Base
{
    protected $beforeActionList = [
        'checkAuth' => [
            'except' => 'test,getCodeForSignUp,signUp,getCodeForSignIn,signInByCode,signInByPassword,
            getCodeForResetPassword,resetPassword,weChatSignIn,bindPhone',
        ],
    ];

    //发送注册验证码
//    public function getCodeForSignUp()
//    {
//        $phone = input("phone");
//        if (!checkIsMobile($phone)) {
//            throw AppException::factory(AppException::COM_MOBILE_ERR);
//        }
//
//        $userService = new UserService();
//        $returnData = $userService->getCodeForSignUp($phone);
//
//        return $this->jsonResponse($returnData);
//    }

    //手机号注册
    public function signUp()
    {
        $header = $this->request->header();

        $phone = input("phone");
        $code = input("code", null);
        $password = input("password", null);
        $inviteCode = input("invite_code");

        if (!checkIsMobile($phone) || $code === null || $password === null) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $userService = new UserService();
        $returnData = $userService->signUp($phone, $code, $password, $inviteCode, $header);

        return $this->jsonResponse($returnData);
    }

    //发送注册验证码
//    public function getCodeForSignIn()
//    {
//        $phone = input("phone");
//        if (!checkIsMobile($phone)) {
//            throw AppException::factory(AppException::COM_MOBILE_ERR);
//        }
//
//        $userService = new UserService();
//        $returnData = $userService->getCodeForSignIn($phone);
//
//        return $this->jsonResponse($returnData);
//    }

    //手机验证码登录
    public function signInByCode()
    {
        $phone = input("phone");
        $code = input("code");
        $header = $this->request->header();

        if (!checkIsMobile($phone) || $code === null) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $userService = new UserService();
        $returnData = $userService->signInByCode($phone, $code, $header);

        return $this->jsonResponse($returnData);
    }

    //手机密码登录
    public function signInByPassword()
    {
        $phone = input("phone");
        $password = input("password");
        $header = $this->request->header();

        if (!checkIsMobile($phone) || $password === null) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $userService = new UserService();
        $returnData = $userService->signInByPassword($phone, $password, $header);

        return $this->jsonResponse($returnData);
    }

    //发送重置密码验证码
//    public function getCodeForResetPassword()
//    {
//        $phone = input("phone");
//        if (!checkIsMobile($phone)) {
//            throw AppException::factory(AppException::COM_MOBILE_ERR);
//        }
//
//        $userService = new UserService();
//        $returnData = $userService->getCodeForResetPassword($phone);
//
//        return $this->jsonResponse($returnData);
//    }

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

    //移动客户端绑定微信
    public function bindWeChat()
    {
        $code = input("code");
        if (empty($code)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $userInfo = $this->query["user"];

        $userService = new UserService();
        $returnData = $userService->bindWeChat($code, $userInfo);

        return $this->jsonResponse($returnData);
    }

    //移动客户端微信登录
    public function weChatSignIn()
    {
        $code = input("code");
        $header = $this->request->header();
        if (empty($code)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $userService = new UserService();
        $returnData = $userService->weChatSignIn($code, $header);

        return $this->jsonResponse($returnData);
    }

    //微信绑定手机号
    public function bindPhone()
    {
        $header = $this->request->header();

        $key = input("key");
        $phone = input("phone");
        $code = input("code", null);
        $password = input("password", null);
        $inviteCode = input("invite_code");

        if ($key === null || !checkIsMobile($phone) || $code === null || $password === null) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $userService = new UserService();
        $returnData = $userService->bindPhone($key, $phone, $code, $password, $inviteCode, $header);

        return $this->jsonResponse($returnData);
    }

    //用户详情
    public function userInfo()
    {
        $header = $this->request->header();
        $userInfo = $this->query["user"];

        $userService = new UserService();
        $returnData = $userService->userInfo($userInfo, $header);

        return $this->jsonResponse($returnData);
    }

    //修改用户信息
    public function modifyUserInfo()
    {
        $params = file_get_contents("php://input");
        $params = json_decode($params, true);
        if (!is_array($params) || count($params) == 0) {
            throw AppException::factory(AppException::USER_INFO_NOT_MODIFY);
        }

        $userInfo = $this->query["user"];

        $userService = new UserService();
        $returnData = $userService->modifyUserInfo($userInfo, $params);

        return $this->jsonResponse($returnData);
    }

    //用户签到信息
    public function signInfo()
    {
        $user = $this->query["user"];

        $userService = new UserService();
        $returnData = $userService->signInfo($user);

        return $this->jsonResponse($returnData);
    }

    //用户签到
    public function sign()
    {
        $user = $this->query["user"];

        $userService = new UserService();
        $returnData = $userService->sign($user);

        return $this->jsonResponse($returnData);
    }

    //领取连续签到奖励
    public function receiveContinuousSignReward()
    {
        $condition = input("condition");
        $user = $this->query["user"];

        $userService = new UserService();
        $returnData = $userService->receiveContinuousSignReward($user, $condition);

        return $this->jsonResponse($returnData);
    }

    //我的作文本
    public function writingList()
    {
        $pageNum = input("page_num");
        $pageSize = input("page_size");
        if (!checkInt($pageNum, false) || !checkInt($pageSize, false)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $service = new UserWritingService();
        $returnData = $service->myWritingList($user, $pageNum, $pageSize);

        return $this->jsonResponse($returnData);
    }

    //DE币流水
    public function coinFlowList()
    {
        $pageNum = input("page_num");
        $pageSize = input("page_size");
        if (!checkInt($pageNum, false) || !checkInt($pageSize, false)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $service = new UserService();
        $returnData = $service->coinFlowList($user, $pageNum, $pageSize);

        return $this->jsonResponse($returnData);
    }

    //勋章墙
    public function medals()
    {
        $user = $this->query["user"];
        $service = new UserService();
        $returnData = $service->medals($user);
        return $this->jsonResponse($returnData);
    }

    public function updateSelfMedal()
    {
        $medalIds = input("medal_id");
        if (!is_array($medalIds) || count($medalIds) == 0) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $service = new UserService();
        $returnData = $service->updateSelfMedal($user, $medalIds);
        return $this->jsonResponse($returnData);
    }

    public function cancelAccount()
    {
        $reason = input("reason");
        if (empty($reason)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $service = new UserService();
        $returnData = $service->cancelAccount($user, $reason);
        return $this->jsonResponse($returnData);
    }

    public function unreadNewsCount()
    {
        $user = $this->query["user"];
        $service = new UserService();
        $returnData = $service->unreadNewsCount($user);
        return $this->jsonResponse($returnData);
    }

    public function allUnreadNews()
    {
        $user = $this->query["user"];
        $service = new UserService();
        $returnData = $service->allUnreadNews($user);
        return $this->jsonResponse($returnData);
    }

    /**
     * 系统消息，活动消息各自未读条数
     * @return \think\response\Json
     */
    public function unreadNewsCount2()
    {
        $user = $this->query["user"];
        $service = new UserService();
        $returnData = $service->unreadNewsCount2($user);
        return $this->jsonResponse($returnData);
    }

    public function activityNewsList()
    {
        $pageNum = input("page_num");
        $pageSize = input("page_size");
        if (!checkInt($pageNum, false) || !checkInt($pageSize, false)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $service = new UserService();
        $returnData = $service->activityNewsList($user, $pageNum, $pageSize);
        return $this->jsonResponse($returnData);
    }

    public function getNoviceGuideReward()
    {
        $user = $this->query["user"];
        $service = new UserService();
        $returnData = $service->getNoviceGuideReward($user);
        return $this->jsonResponse($returnData);
    }
}