<?php

/**
 * 移动应用微信登录，通过 code 获取 access_token 的接口
 *
 * @param $appId
 * @param $secret
 * @param $code
 * @return mixed
 */
function getAccessTokenForMobileApp($appId, $secret, $code) {
    $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=$appId".
        "&secret=$secret&code=$code&grant_type=authorization_code";

    $data = curl($url);
    $data = json_decode($data, true);

    return $data;
}

/**
 * 移动应用微信登录,获取用户微信信息
 *
 * @param $accessToken
 * @param $openid
 * @return mixed
 */
function getUserInfoForMobileApp($accessToken, $openid) {
    $url = "https://api.weixin.qq.com/sns/userinfo?access_token=$accessToken&openid=$openid";

    $data = curl($url);
    $data = json_decode($data, true);

    return $data;
}