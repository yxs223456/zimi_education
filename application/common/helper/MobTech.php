<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-02-27
 * Time: 18:28
 */

namespace app\common\helper;

use think\facade\Log;

class MobTech
{
    public static function verify($phone, $code, $zone = "86")
    {
        $url = "https://webapi.sms.mob.com/sms/verify";

        $postParams = [
            "appkey" => config("account.mob_tech.app_key"),
            "phone" => $phone,
            "zone" => $zone,
            "code" => $code,
        ];

        $verifyResponse = curl($url, "post", http_build_query($postParams));

        Log::write("verify code response" . $verifyResponse);

        $verifyResponse = json_decode($verifyResponse, true);
        if (!is_array($verifyResponse) || !isset($verifyResponse["status"]) || $verifyResponse["status"] != 200) {
            return false;
        }

        return true;
    }
}