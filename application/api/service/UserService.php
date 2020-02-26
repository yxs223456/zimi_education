<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-02-26
 * Time: 15:09
 */
namespace app\api\service;

use app\common\AppException;
use app\common\Constant;
use app\common\enum\PhoneVerificationCodeStatusEnum;
use app\common\enum\PhoneVerificationCodeTypeEnum;
use app\common\helper\Pbkdf2;
use app\common\helper\Redis;
use app\common\model\PhoneVerificationCodeModel;
use app\common\model\UserBaseModel;
use think\Model;

class UserService extends Base
{

    public function getCodeForSignUp($phone)
    {
        //判断手机号是否已注册
        $userModel = new UserBaseModel();
        $userByPhone = $userModel->getUserByPhone($phone);
        if ($userByPhone) {
            throw AppException::factory(AppException::USER_PHONE_EXISTS_ALREADY);
        }

        //随机生成验证码并记录到数据库
//        $code = getRandomString(6, true);
        $code = "666666";
        $codeUseType = PhoneVerificationCodeTypeEnum::SIGN_UP;
        $phoneVerificationCodeModel = new PhoneVerificationCodeModel();
        $phoneVerificationCodeModel->insertPhoneVerificationCode($phone, $code, $codeUseType);

        //TODO 发送验证码

        return [];
    }

    //通过手机号注册用户
    public function singUp($phone, $code, $password)
    {

        //验证密码格式是否正确
        if ($this->checkPasswordFormat($password) == false) {
            throw AppException::factory(AppException::USER_PASSWORD_FORMAT_ERROR);
        }


        //判断验证码是否正确（不验证手机号是否注册）
        $phoneVerificationCodeModel = new PhoneVerificationCodeModel();
        $useType = PhoneVerificationCodeTypeEnum::SIGN_UP;
        $status = PhoneVerificationCodeStatusEnum::VALID;
        $codeInfo = $phoneVerificationCodeModel->getLastPhoneVerificationCode($phone, $useType, $status);

        if ($codeInfo == null || $codeInfo["code"] != $code) {
            throw AppException::factory(AppException::USER_PHONE_VERIFY_CODE_ERROR);
        }

        if ($codeInfo["create_time"] + Constant::PHONE_VERIFICATION_CODE_VALID_TIME < time()) {
            $phoneVerificationCodeModel->updateStatusToInvalid($phone, $useType);
            throw AppException::factory(AppException::USER_PHONE_VERIFY_CODE_ERROR);
        }

        $phoneVerificationCodeModel->updateStatusToHasBeenUsed($codeInfo["id"]);


        //通过手机号创建用户
        $userInfo = $this->createUserByPhone($phone, $password);

        return $this->userInfoForRequire($userInfo);
    }

    //发送登录验证码
    public function getCodeForSignIn($phone)
    {
        //判断手机号是否已注册
        $userModel = new UserBaseModel();
        $userByPhone = $userModel->getUserByPhone($phone);
        if (!$userByPhone) {
            throw AppException::factory(AppException::USER_NOT_EXISTS);
        }

        //随机生成验证码并记录到数据库
//        $code = getRandomString(6, true);
        $code = "666666";
        $codeUseType = PhoneVerificationCodeTypeEnum::SIGN_IN;
        $phoneVerificationCodeModel = new PhoneVerificationCodeModel();
        $phoneVerificationCodeModel->insertPhoneVerificationCode($phone, $code, $codeUseType);

        //TODO 发送验证码

        return [];
    }

    public function getCodeForResetPassword($phone)
    {
        //判断手机号是否已注册
        $userModel = new UserBaseModel();
        $userByPhone = $userModel->getUserByPhone($phone);
        if (!$userByPhone) {
            throw AppException::factory(AppException::USER_NOT_EXISTS);
        }

        //随机生成验证码并记录到数据库
//        $code = getRandomString(6, true);
        $code = "666666";
        $codeUseType = PhoneVerificationCodeTypeEnum::RESET_PASSWORD;
        $phoneVerificationCodeModel = new PhoneVerificationCodeModel();
        $phoneVerificationCodeModel->insertPhoneVerificationCode($phone, $code, $codeUseType);

        //TODO 发送验证码

        return [];
    }

    private function createUserByPhone($phone, $password)
    {
        $encryptPassword = Pbkdf2::create_hash($password);
        $inviteCode = createInviteCode(8);
        $token = getRandomString(32);
        $uuid = getRandomString(32);
        $time = time();

        $userInfo = [
            "uuid" => $uuid,
            "phone" => $phone,
            "password" => $encryptPassword,
            "token" => $token,
            "invite_code" => $inviteCode,
            "create_time" => $time,
            "update_time" => $time,
        ];

        $userBaseModel = new UserBaseModel();

        $userInfo["id"] = $userBaseModel->insertGetId($userInfo);

        //把用户信息记录到redis
        $redis = Redis::factory();
        cacheUserInfoByToken($userInfo, $redis);

        return $userInfo;
    }

    //通过手机号和验证码登录
    public function signInByCode($phone, $code)
    {
        //判断验证码是否正确
        $phoneVerificationCodeModel = new PhoneVerificationCodeModel();
        $useType = PhoneVerificationCodeTypeEnum::SIGN_IN;
        $status = PhoneVerificationCodeStatusEnum::VALID;
        $codeInfo = $phoneVerificationCodeModel->getLastPhoneVerificationCode($phone, $useType, $status);

        if ($codeInfo == null || $codeInfo["code"] != $code) {
            throw AppException::factory(AppException::USER_PHONE_VERIFY_CODE_ERROR);
        }

        if ($codeInfo["create_time"] + Constant::PHONE_VERIFICATION_CODE_VALID_TIME < time()) {
            $phoneVerificationCodeModel->updateStatusToInvalid($phone, $useType);
            throw AppException::factory(AppException::USER_PHONE_VERIFY_CODE_ERROR);
        }

        $phoneVerificationCodeModel->updateStatusToHasBeenUsed($codeInfo["id"]);

        //通过手机号获取用户
        $userBaseModel = new UserBaseModel();
        $user = $userBaseModel->getUserByPhone($phone);
        if (!$user) {
            throw AppException::factory(AppException::USER_NOT_EXISTS);
        }

        //更新用户token
        $userInfo = $this->updateUserToken($user);

        return $this->userInfoForRequire($userInfo);
    }

    //通过手机号和验证码登录
    public function signInByPassword($phone, $password)
    {

        //通过手机号获取用户
        $userBaseModel = new UserBaseModel();
        $user = $userBaseModel->getUserByPhone($phone);
        if (!$user) {
            throw AppException::factory(AppException::USER_NOT_EXISTS);
        }

        //判断密码是否正确
        if (Pbkdf2::validate_password($password, $user->password) == false) {
            throw AppException::factory(AppException::USER_PASSWORD_ERROR);
        }

        //更新用户token
        $userInfo = $this->updateUserToken($user);

        return $this->userInfoForRequire($userInfo);
    }

    //重置密码
    public function resetPassword($phone, $code, $password)
    {

        //验证密码格式是否正确
        if ($this->checkPasswordFormat($password) == false) {
            throw AppException::factory(AppException::USER_PASSWORD_FORMAT_ERROR);
        }

        //通过手机号获取用户
        $userBaseModel = new UserBaseModel();
        $user = $userBaseModel->getUserByPhone($phone);
        if ($user == null) {
            throw AppException::factory(AppException::USER_NOT_EXISTS);
        }

        //判断验证码是否正确
        $phoneVerificationCodeModel = new PhoneVerificationCodeModel();
        $useType = PhoneVerificationCodeTypeEnum::RESET_PASSWORD;
        $status = PhoneVerificationCodeStatusEnum::VALID;
        $codeInfo = $phoneVerificationCodeModel->getLastPhoneVerificationCode($phone, $useType, $status);

        if ($codeInfo == null || $codeInfo["code"] != $code) {
            throw AppException::factory(AppException::USER_PHONE_VERIFY_CODE_ERROR);
        }

        if ($codeInfo["create_time"] + Constant::PHONE_VERIFICATION_CODE_VALID_TIME < time()) {
            $phoneVerificationCodeModel->updateStatusToInvalid($phone, $useType);
            throw AppException::factory(AppException::USER_PHONE_VERIFY_CODE_ERROR);
        }

        $phoneVerificationCodeModel->updateStatusToHasBeenUsed($codeInfo["id"]);


        //修改用户密码
        $encryptPassword = Pbkdf2::create_hash($password);
        $user->password = $encryptPassword;
        $user->save();

        return [];
    }

    private function updateUserToken(Model $user, \Redis $redis = null)
    {
        //更新用户token
        $user->token = getRandomString(32);
        $user->update_time = time();
        $user->save();

        //更新用户缓存
        if ($redis == null) {
            $redis = Redis::factory();
        }
        $userInfo = $user->toArray();
        cacheUserInfoByToken($userInfo, $redis);

        return $userInfo;
    }

    private function userInfoForRequire(array $userInfo)
    {
        return [
            "token" => $userInfo["token"]
        ];
    }



    //验证密码是否符合要求。密码长度8~32位，需包含数字、字母、符号至少2种或以上元素
    private function checkPasswordFormat($password)
    {
        $length = strlen($password);
        if ($length < 8 || $length > 32) {
            return false;
        }

        if (preg_match("/ /", $password)) {
            return false;
        }

        $hasDigit = preg_match("/[0-9]/", $password);
        $hasLetter = preg_match("/[a-zA-Z]/", $password);
        $hasSymbol = preg_match("/[^a-zA-Z0-9]/", $password);

        if ($hasSymbol + $hasLetter + $hasDigit < 2) {
            return false;
        }

        return true;
    }
}