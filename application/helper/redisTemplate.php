<?php

function cacheUserInfoByToken(array $userInfo, Redis $redis) {
    $key = "zimi_education:userInfoByToken:" . $userInfo["token"];
    $redis->hMSet($key, $userInfo);
    //缓存有效期72小时
    $redis->expire($key, 259200);

}

function getUserInfoByToken($token, Redis $redis) {
    if ($token == "") {
        return [];
    }
    $key = "zimi_education:userInfoByToken:$token";
    return $redis->hGetAll($key);
}