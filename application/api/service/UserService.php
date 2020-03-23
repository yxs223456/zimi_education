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
use app\common\enum\UserCoinAddTypeEnum;
use app\common\enum\UserIsBindWeChatEnum;
use app\common\helper\MobTech;
use app\common\helper\Pbkdf2;
use app\common\helper\Redis;
use app\common\model\PhoneVerificationCodeModel;
use app\common\model\UserBaseModel;
use app\common\model\UserCoinLogModel;
use app\common\model\UserSignLogModel;
use think\Db;
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
    public function singUp($phone, $code, $password, $inviteCode)
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
            throw AppException::factory(AppException::USER_PHONE_VERIFY_CODE_ERROR);
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

        //判断邀请码是否存在
        if ($inviteCode) {
            $inviteUser = $userModel->getUserByInviteCode($inviteCode);
            if (!$inviteUser) {
                throw AppException::factory(AppException::USER_INVITE_CODE_NOT_EXISTS);
            }
            $parentUuid = $inviteUser["uuid"];
        } else {
            $parentUuid = "";
            $inviteCode = "";
        }

        Db::startTrans();
        try {

            //通过手机号创建用户
            $userInfo = $this->createUserByPhone($phone, $password, $parentUuid, $inviteCode);

            $userModel->addUserInviteCountByUuid($parentUuid);

            Db::commit();

        } catch (\Throwable $e) {
            Db::rollback();
            Log::write("create user by phone error:" . $e->getMessage(), "ERROR");
            throw AppException::factory(AppException::USER_CREATE_ERROR);
        }


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

    private function createUserByPhone($phone, $password, $parentUuid, $parentInviteCode)
    {
        $encryptPassword = Pbkdf2::create_hash($password);
        $inviteCode = createInviteCode(6);
        $token = getRandomString(32);
        $uuid = getRandomString(32);
        $time = time();

        $userInfo = [
            "uuid" => $uuid,
            "phone" => $phone,
            "password" => $encryptPassword,
            "token" => $token,
            "invite_code" => $inviteCode,
            "parent_uuid" => $parentUuid,
            "parent_invite_code" => $parentInviteCode,
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
            throw AppException::factory(AppException::USER_PHONE_VERIFY_CODE_ERROR);
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
            throw AppException::factory(AppException::USER_PHONE_VERIFY_CODE_ERROR);
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
        $user->update_time = time();
        $user->save();

        $userInfo = $user->toArray();
        $redis = Redis::factory();
        cacheUserInfoByToken($userInfo, $redis);

        return new \stdClass();
    }

    public function bindWeChat($code, $userInfo)
    {
        //获取access token
        $weChatConfig = config("account.we_chat.mobile");
        $accessTokenResp = $this->getAccessTokenForMobileApp($weChatConfig["app_id"], $weChatConfig["app_secret"], $code);

        //获取用户微信信息
        $userWeChatInfo = $this->getUserInfoForMobileApp($accessTokenResp["access_token"], $accessTokenResp["openid"]);

        //判断微信是否绑定了其他账号
        $userModel = new UserBaseModel();
        $userByWeChat = $userModel->getUserByUnionid($userWeChatInfo["unionid"]);
        if ($userByWeChat) {
            throw AppException::factory(AppException::WE_CHAT_BIND_ALREADY);
        }

        //纪录用户微信信息
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
            "province" => $userWeChatInfo["province"],
            "city" => $userWeChatInfo["city"],
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

    public function modifyUserInfo($userInfo, $modifyUserInfo)
    {
        //获取用户
        $userModel = new UserBaseModel();
        $user = $userModel->getUserByUuid($userInfo["uuid"]);
        if (!$user) {
            throw AppException::factory(AppException::USER_NOT_EXISTS);
        }

        //1.邀请用户不可改变 2.只要没有邀请人即可添加任意用户为邀请人
        if (isset($modifyUserInfo["parent_invite_code"])) {
            if ($userInfo["parent_invite_code"]) {
                throw AppException::factory(AppException::USER_PARENT_NOT_ALLOW_MODIFY);
            }
            if (empty($modifyUserInfo["parent_invite_code"])) {
                throw AppException::factory(AppException::COM_PARAMS_ERR);
            }

            $inviteUser = $userModel->getUserByInviteCode($modifyUserInfo["parent_invite_code"]);
            if (!$inviteUser) {
                throw AppException::factory(AppException::USER_INVITE_CODE_NOT_EXISTS);
            }
        }

        Db::startTrans();
        try {
            //修改用户信息
            if (!empty($modifyUserInfo["head_image_url"])) $user->head_image_url = $modifyUserInfo["head_image_url"];
            if (!empty($modifyUserInfo["nickname"])) $user->nickname = $modifyUserInfo["nickname"];
            if (!empty($modifyUserInfo["sign"])) $user->sign = $modifyUserInfo["sign"];
            if (!empty($modifyUserInfo["province"])) $user->province = $modifyUserInfo["province"];
            if (!empty($modifyUserInfo["city"])) $user->city = $modifyUserInfo["city"];
            if (isset($inviteUser)) {
                $user->parent_invite_code = $modifyUserInfo["parent_invite_code"];
                $user->parent_uuid = $inviteUser["uuid"];
            }
            $user->update_time = time();
            $user->save();

            //添加邀请人后修改邀请人信息
            if (isset($inviteUser)) {
                $userModel->addUserInviteCountByUuid($inviteUser["uuid"]);
            }

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            Log::write("user info modify error:" . $e->getMessage(), "ERROR");
            throw AppException::factory(AppException::USER_MODIFY_ERROR);
        }

        //缓存用户信息
        $userInfo = $user->toArray();
        $redis = Redis::factory();
        cacheUserInfoByToken($userInfo, $redis);

        return $this->userInfoForRequire($userInfo);
    }

    //判断用户信息是否完整
    public function checkUserInfoComplete($user)
    {
        if (empty($user["head_image_url"]) || empty($user["nickname"]) ||
            empty($user["province"]) || empty($user["city"]) || empty($user["sign"]) ) {
            return false;
        }

        return true;
    }

    //签到首页信息
    public function signInfo($user)
    {
        //初始化返回数据
        $returnData = [
            "is_sign" => 0,
            "continuous_sign_times" => 0,
            "cumulative_sign_times" => 0,
            "reward_list" => Constant::CONTINUOUS_SIGN_REWARD,
        ];

        $now = time();
        $today = date("Y-m-d", $now);
        $yesterday = date("Y-m-d", $now-86400);
        $month = date("Y-m", $now);

        //计算今日是否签到
        $returnData["is_sign"] = (int) ($user["last_sign_date"] == $today);

        //计算连续签到次数
        $returnData["continuous_sign_times"] =
            (int)(($user["last_sign_date"] == $today || $user["last_sign_date"] == $yesterday)?$user["continuous_sign_times"]:0);

        //计算累计签到次数
        $returnData["cumulative_sign_times"] =
            (int)(substr($user["last_sign_date"], 0, 7) == $month?$user["cumulative_sign_times"]:0);

        //当月连续签到奖励领取情况
        $redis = Redis::factory();
        $continuousSignReward = currentMonthContinuousSignReward($user["uuid"], $redis);
        $continuousSignReward = array_column($continuousSignReward, "coin", "condition");
        foreach ($returnData["reward_list"] as $key => $rewardList) {
            if (isset($continuousSignReward[$rewardList["condition"]])) {
                $returnData["reward_list"][$key] = [
                    "condition" => $rewardList["condition"],
                    "coin" => $continuousSignReward[$rewardList["condition"]],
                    "is_receive" => 1,
                ];
            } else {
                $returnData["reward_list"][$key]["is_receive"] = 0;
            }
        }

        return $returnData;
    }

    public function sign($user)
    {
        $now = time();
        $today = date("Y-m-d", $now);
        $yesterday = date("Y-m-d", $now - 86400);
        $currentMonth = date("Y-m", $now);
        $userModel = new UserBaseModel();

        //使用数据库事务，控制并发请求，保证用户一日只签到一次
        Db::startTrans();
        $userUuid = $user["uuid"];
        $userSql = "select * from user_base where uuid = '$userUuid' for update";
        $userQuery = Db::query($userSql);
        if (!isset($userQuery[0])) {
            throw AppException::factory(AppException::USER_NOT_EXISTS);
        }
        $userInfo = $userQuery[0];

        //今日已签到
        if ($userInfo["last_sign_date"] == $today) {
            Db::rollback();
            throw AppException::factory(AppException::USER_SIGN_ALREADY);
        }

        try {
            //添加签到纪录
            $signLogData = [
                "uuid" => getRandomString(),
                "user_uuid" => $userUuid,
                "create_month" => $currentMonth,
                "create_date" => $today,
                "create_time" => $now,
                "update_time" => $now,
            ];
            (new UserSignLogModel())->insert($signLogData);

            //修改用户签到统计
            $lastSignMonth = substr($userInfo["last_sign_date"], 0, 7);
            $userExec = $userModel->where("uuid", $userUuid);
            if ($lastSignMonth == $currentMonth) {
                if ($userInfo["last_sign_date"] == $yesterday) {
                    //最后一次签到是本月，且最后一次签到是昨日
                    $userExec->inc("continuous_sign_times", 1)
                        ->inc("cumulative_sign_times", 1)
                        ->update(["last_sign_date"=>$today,"update_time"=>$now]);
                } else {
                    //最后一次签到是本月，但最后一次签到不是昨日
                    $userExec->inc("cumulative_sign_times", 1)
                        ->update(["last_sign_date"=>$today,"continuous_sign_times"=>1,"update_time"=>$now]);
                }
            } else {
                //最后一次签到不是本月
                $userExec->update(["last_sign_date"=>$today,"continuous_sign_times"=>1,
                    "cumulative_sign_times"=>1,"update_time"=>$now]);
            }

            //用户累计签到达标，且未发放累计签到奖励，发放累计签到奖励
            $userNew = $userModel->findByUuid($userUuid);
            $userInfo = $userNew->toArray();
            if ($userNew["cumulative_sign_times"] == Constant::CUMULATIVE_SIGN_DAYS) {
                $userCoinLogModel = new UserCoinLogModel();
                $addCoinLog = $userCoinLogModel->checkReceiveCumulativeSignReward($userUuid);
                if ($addCoinLog == false) {
                    //增加用户书币数
                    $userModel->where("uuid", $userUuid)
                        ->inc("coin", Constant::CUMULATIVE_SIGN_COIN)
                        ->update(["update_time"=>$now]);

                    //纪录书币流水
                    $userCoinLogModel->recordAddLog(
                        $userUuid,
                        UserCoinAddTypeEnum::CUMULATIVE_SIGN,
                        Constant::CUMULATIVE_SIGN_COIN,
                        $userNew["coin"],
                        $userNew["coin"] + Constant::CUMULATIVE_SIGN_COIN,
                        UserCoinAddTypeEnum::CUMULATIVE_SIGN_DESC);

                    $userInfo["coin"] += Constant::CUMULATIVE_SIGN_COIN;
                }
            }

            Db::commit();

            //缓存用户信息
            $redis = Redis::factory();
            cacheUserInfoByToken($userInfo, $redis);

            $returnData = [
                "continuous_sign_times" => $userInfo["continuous_sign_times"],
                "cumulative_sign_times" => $userInfo["cumulative_sign_times"],
            ];
            return $returnData;

        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    public function receiveContinuousSignReward($user, $condition)
    {
        //使用队列处理连续签到
        $redis = Redis::factory();
        pushReceiveContinuousSignRewardList($user, $condition,  $redis);

        return new \stdClass();
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
            "head_image_url" => $this->getHeadImageUrl($userInfo["head_image_url"]),
            "sex" => (int) $userInfo["sex"],
            "level" => (int) $userInfo["level"],
            "coin" => (int) $userInfo["coin"],
            "invite_code" => $userInfo["invite_code"],
            "parent_invite_code" => $userInfo["parent_invite_code"],
            "sign" => $userInfo["sign"],
            "phone" => hidePhone($userInfo["phone"]),
            "province" => $userInfo["province"],
            "city" => $userInfo["city"],
            "bind_wechat" => empty($userInfo["mobile_openid"]) ? UserIsBindWeChatEnum::NO : UserIsBindWeChatEnum::YES,
            "novice_test_is_show" => $userInfo["novice_test_is_show"],
            "novice_level" => $userInfo["novice_level"],
        ];
    }

    public function getHeadImageUrl($headImageUrl)
    {
        return str_replace("static/api", config("web.self_domain") . "/static/api", $headImageUrl);
    }

    //验证密码是否符合要求。密码长度8~32位，需包含数字、字母、符号至少2种或以上元素
    private function checkPasswordFormat($password)
    {
        return true;
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