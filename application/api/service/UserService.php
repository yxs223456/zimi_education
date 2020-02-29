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
use app\common\helper\MobTech;
use app\common\helper\Pbkdf2;
use app\common\helper\Redis;
use app\common\model\PhoneVerificationCodeModel;
use app\common\model\UserBaseModel;
use think\facade\Log;
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
        //判断手机号是否已注册
        $userModel = new UserBaseModel();
        $userByPhone = $userModel->getUserByPhone($phone);
        if ($userByPhone) {
            throw AppException::factory(AppException::USER_PHONE_EXISTS_ALREADY);
        }

        //验证密码格式是否正确
        if ($this->checkPasswordFormat($password) == false) {
            throw AppException::factory(AppException::USER_PASSWORD_FORMAT_ERROR);
        }


        //判断验证码是否正确（不验证手机号是否注册）
        if (MobTech::verify($phone, $code) == false) {
//            throw AppException::factory(AppException::USER_PHONE_VERIFY_CODE_ERROR);
        }

//        $phoneVerificationCodeModel = new PhoneVerificationCodeModel();
//        $useType = PhoneVerificationCodeTypeEnum::SIGN_UP;
//        $status = PhoneVerificationCodeStatusEnum::VALID;
//        $codeInfo = $phoneVerificationCodeModel->getLastPhoneVerificationCode($phone, $useType, $status);
//
//        if ($codeInfo == null || $codeInfo["code"] != $code) {
//            throw AppException::factory(AppException::USER_PHONE_VERIFY_CODE_ERROR);
//        }
//
//        if ($codeInfo["create_time"] + Constant::PHONE_VERIFICATION_CODE_VALID_TIME < time()) {
//            $phoneVerificationCodeModel->updateStatusToInvalid($phone, $useType);
//            throw AppException::factory(AppException::USER_PHONE_VERIFY_CODE_ERROR);
//        }
//
//        $phoneVerificationCodeModel->updateStatusToHasBeenUsed($codeInfo["id"]);


        //通过手机号创建用户
        $userInfo = $this->createUserByPhone($phone, $password);

        //把用户信息记录到redis
        $redis = Redis::factory();
        cacheUserInfoByToken($userInfo, $redis);

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

        $id = $userBaseModel->insertGetId($userInfo);

        $userInfo = $userBaseModel->where("id", $id)->find()->toArray();

        return $userInfo;
    }

    //通过手机号和验证码登录
    public function signInByCode($phone, $code)
    {
        //判断验证码是否正确
        if (MobTech::verify($phone, $code) == false) {
//            throw AppException::factory(AppException::USER_PHONE_VERIFY_CODE_ERROR);
        }
//        $phoneVerificationCodeModel = new PhoneVerificationCodeModel();
//        $useType = PhoneVerificationCodeTypeEnum::SIGN_IN;
//        $status = PhoneVerificationCodeStatusEnum::VALID;
//        $codeInfo = $phoneVerificationCodeModel->getLastPhoneVerificationCode($phone, $useType, $status);
//
//        if ($codeInfo == null || $codeInfo["code"] != $code) {
//            throw AppException::factory(AppException::USER_PHONE_VERIFY_CODE_ERROR);
//        }
//
//        if ($codeInfo["create_time"] + Constant::PHONE_VERIFICATION_CODE_VALID_TIME < time()) {
//            $phoneVerificationCodeModel->updateStatusToInvalid($phone, $useType);
//            throw AppException::factory(AppException::USER_PHONE_VERIFY_CODE_ERROR);
//        }
//
//        $phoneVerificationCodeModel->updateStatusToHasBeenUsed($codeInfo["id"]);

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
        if (MobTech::verify($phone, $code) == false) {
//            throw AppException::factory(AppException::USER_PHONE_VERIFY_CODE_ERROR);
        }
//        $phoneVerificationCodeModel = new PhoneVerificationCodeModel();
//        $useType = PhoneVerificationCodeTypeEnum::RESET_PASSWORD;
//        $status = PhoneVerificationCodeStatusEnum::VALID;
//        $codeInfo = $phoneVerificationCodeModel->getLastPhoneVerificationCode($phone, $useType, $status);
//
//        if ($codeInfo == null || $codeInfo["code"] != $code) {
//            throw AppException::factory(AppException::USER_PHONE_VERIFY_CODE_ERROR);
//        }
//
//        if ($codeInfo["create_time"] + Constant::PHONE_VERIFICATION_CODE_VALID_TIME < time()) {
//            $phoneVerificationCodeModel->updateStatusToInvalid($phone, $useType);
//            throw AppException::factory(AppException::USER_PHONE_VERIFY_CODE_ERROR);
//        }
//
//        $phoneVerificationCodeModel->updateStatusToHasBeenUsed($codeInfo["id"]);


        //修改用户密码
        $encryptPassword = Pbkdf2::create_hash($password);
        $user->password = $encryptPassword;
        $user->save();

        return [];
    }

    public function bindWeChat($code, $userInfo)
    {
        //获取access token
        $weChatConfig = config("account.we_chat.mobile");
        $accessTokenResp = $this->getAccessTokenForMobileApp($weChatConfig["app_id"], $weChatConfig["app_secret"], $code);

        //获取用户微信信息
        $userWeChatInfo = $this->getUserInfoForMobileApp($accessTokenResp["access_token"], $accessTokenResp["openid"]);

        //纪录用户微信信息
        $userModel = new UserBaseModel();
        $user = $userModel->where("uuid", $userInfo["uuid"])->find();
        if (empty($user)) {
            throw AppException::factory(AppException::USER_NOT_EXISTS);
        }
        $this->recordUserWeChatInfo($user, $userWeChatInfo);

        //把用户信息记入缓存
        $userInfo = $user->toArray();
        $redis = Redis::factory();
        cacheUserInfoByToken($userInfo, $redis);

        return [
            "head_image_url" => $userWeChatInfo["headimgurl"],
            "nickname" => $userWeChatInfo["nickname"],
            "sex" => $userWeChatInfo["sex"],
        ];
    }

    public function weChatSignIn($code)
    {
        //获取access token
        $weChatConfig = config("account.we_chat.mobile");
        $accessTokenResp = $this->getAccessTokenForMobileApp($weChatConfig["app_id"], $weChatConfig["app_secret"], $code);

        //获取用户微信信息
        $userWeChatInfo = $this->getUserInfoForMobileApp($accessTokenResp["access_token"], $accessTokenResp["openid"]);

        //获取用户
        $userModel = new UserBaseModel();
        $user = $userModel->where("unionid", $userWeChatInfo["unionid"])->find();
        if (empty($user)) {
            throw AppException::factory(AppException::WE_CHAT_NOT_BIND_USER);
        }

        //纪录用户微信信息
        $this->recordUserWeChatInfo($user, $userWeChatInfo);

        //把用户信息记入缓存
        $userInfo = $user->toArray();
        $redis = Redis::factory();
        cacheUserInfoByToken($userInfo, $redis);

        return $this->userInfoForRequire($userInfo);
    }

    public function userInfo($user)
    {
        return $this->userInfoForRequire($user);
    }

    private function recordUserWeChatInfo(Model $user, $userWeChatInfo)
    {
        $user->unionid = $userWeChatInfo["unionid"];
        $user->mobile_openid = $userWeChatInfo["openid"];
        $user->nickname = $userWeChatInfo["nickname"];
        $user->head_image_url = $userWeChatInfo["headimgurl"];
        $user->sex = $userWeChatInfo["sex"];
        $user->country = $userWeChatInfo["country"];
        $user->province = $userWeChatInfo["province"];
        $user->city = $userWeChatInfo["city"];
        $user->update_time = time();
        $user->save();

    }

    private function getAccessTokenForMobileApp($appId, $appSecret, $code)
    {
        $accessTokenResp = getAccessTokenForMobileApp($appId, $appSecret, $code);
        if (!is_array($accessTokenResp) ||
            empty($accessTokenResp["access_token"]) ||
            empty($accessTokenResp["openid"])) {
            Log::write("get access token error:" . json_encode($accessTokenResp, JSON_UNESCAPED_UNICODE));
            throw AppException::factory(AppException::WE_CHAT_GET_ACCESS_TOKEN_ERROR);
        }

        return $accessTokenResp;
    }

    private function getUserInfoForMobileApp($accessToken, $openid)
    {
        $userWeChatInfo = getUserInfoForMobileApp($accessToken, $openid);
        if (!is_array($userWeChatInfo) ||
            empty($userWeChatInfo["unionid"])) {
            Log::write("get access token error:" . json_encode($userWeChatInfo, JSON_UNESCAPED_UNICODE));
            throw AppException::factory(AppException::WE_CHAT_GET_ACCESS_TOKEN_ERROR);
        }

        return $userWeChatInfo;
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
            "token" => $userInfo["token"],
            "nickname" => $userInfo["nickname"],
            "head_image_url" => $userInfo["head_image_url"],
            "sex" => (int) $userInfo["sex"],
            "level" => (int) $userInfo["level"],
            "coin" => (int) $userInfo["coin"],
            "invite_code" => $userInfo["invite_code"],
            "bind_wechat" => empty($userInfo["unionid"]) ? 0 : 1,
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