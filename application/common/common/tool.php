<?php
/**
 * Created by PhpStorm.
 * User: yangxs
 * Date: 2018/9/18
 * Time: 18:13
 */

function checkIsMobile($mobile)
{
    if (empty($mobile) || !is_numeric($mobile) || strlen($mobile) != 11) {
        return false;
    } else {
        return true;
    }
}

/**
 * 获取客户端ip
 */
function getClientIp() {
    if (getenv('HTTP_CLIENT_IP')) {
        $clientIp = getenv('HTTP_CLIENT_IP');
    } else if (getenv('HTTP_X_FORWARDED_FOR')) {
        $clientIp = getenv('HTTP_X_FORWARDED_FOR');
    } else if (getenv('REMOTE_ADDR')) {
        $clientIp = getenv('REMOTE_ADDR');
    } else {
        $clientIp = '';
    }
    return $clientIp;
}

/**
 * @param $url                      string 请求地址
 * @param $method                   string 请求类型
 * @param $postData                 null|json|array 请求的post数据
 * @param $isPostDataJsonEncode     bool post数据是否需要为json格式
 * @param $cookie                   null|string cookie数据
 * @param $isResponseJson           bool 请求$url的响应是否为json
 * @param header                    null|array 请求的消息报头
 * @param $isReturnHeader           bool 是否返回响应头信息
 * @return mixed                    请求的响应数据，失败时返回false
 */
function curl($url, $method = 'get', $postData = null, $isPostDataJsonEncode = false, $isResponseJson = false, $cookie = null, $header = null, $isReturnHeader = false) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if (stripos($url, 'https') !== false) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }
    if ($isReturnHeader) {
        curl_setopt($ch, CURLOPT_HEADER, 1);
    }
    if (strtolower($method) == 'post') {
        curl_setopt($ch, CURLOPT_POST, 1);
        if ($isPostDataJsonEncode && is_array($postData)) {
            $postData = json_encode($postData, JSON_UNESCAPED_UNICODE);
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    }
    if (!empty($cookie)) {
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    }
    if (!empty($header) && is_array($header) && count($header)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    }
    $data = curl_exec($ch);
    $err = curl_error($ch);
    if ($err) {
        $log = json_encode([
            "url" => $url,
            "data" => $postData,
            "header" => $header,
            "cookie" => $cookie,
            "reason" => $err,
        ], JSON_UNESCAPED_UNICODE);
        \think\facade\Log::write("curl exec error: $log");
        return false;
    }
    if ($isResponseJson) {
        $data = json_decode($data, true);
    }
    return $data;
}

/**
 * 生成随机字符串
 */
function getRandomString($length = 32, $isNumeric = false) {
    if ($isNumeric) {
        $chars = '0123456789';
    } else {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    }
    $str = '';
    for ($i = 0; $i < $length; $i++) {
        $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
}

function downloadExcel($tableStr, $title = '数据.xls') {
    header("Content-Type:application/vnd.ms-excel");
    header("Content-Disposition:attachment;filename=" . $title);
    echo "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml'>
    <head>
        <meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
        <title>" . $title . "</title>
    </head>
    <body>" . $tableStr . "</body>
</html>";
}

/**
 * 检验参数是否在给定的几个值内
 * @param $param
 * @param array ...$ranges
 * @param bool ...$strict
 * @return bool
 */
function checkIsIn($param, $strict = true, ...$ranges)
{
    if ($strict) {
        if (in_array($param, $ranges, true)) {
            return true;
        }
    } else {
        if (in_array($param, $ranges)) {
            return true;
        }
    }
    return false;
}

/**
 * 验证是否为整数，默认验证 >=0 的整数
 * @param mixed $param 要验证的参数
 * @param bool $zero 是否准许为0
 * @param bool $positive 是否不小于0
 * @return bool
 */
function checkInt($param, $zero = true, $positive = true)
{
    if (!preg_match('#^-?\d+$#', $param)) {
        return false;
    }
    if (!$zero && $param == 0) {
        return false;
    }
    if ($positive && $param < 0) {
        return false;
    }
    return true;
}

/**
 * 验证日期格式是否正确
 * @param $datetime string 精确到秒的日期格式
 * @return bool
 */
function checkDatetime($datetime)
{
    if (!empty($datetime)) {
        $dateArr = explode(' ', $datetime);
        if (count($dateArr) != 2) {
            return false;
        }
        $limitDayArr = explode('-', $dateArr[0]);
        if (count($limitDayArr) != 3 || !checkdate($limitDayArr[1], $limitDayArr[2], $limitDayArr[0])) {
            return false;
        }
        if (!preg_match('/^([01]\d:[012345]\d:[012345]\d)$|^(2[0123]:[012345]\d:[012345]\d)$/', $dateArr[1])) {
            return false;
        }
        return true;
    }
    return false;
}

function hideUserPhone($phone)
{
    if (preg_match('#^\d{11}$#', $phone)) {
        return substr($phone, 0, 3) . '****' . substr($phone, 7);
    }
    return $phone;
}

function getUxuanAccessToken($appId) {
    $url = '47.94.80.136:8082/GetToken/getWechatToken?appid=' . $appId;
    $access_token = curl($url);
    return $access_token;
}

function dealUrlDomain($imgPath) {
    $imgPath = str_replace('http://image-xinke.oss-cn-beijing.aliyuncs.com', 'https://image-xinke.xinker.me', $imgPath);
    $imgPath = str_replace('https://image-xinke.oss-cn-beijing.aliyuncs.com', 'https://image-xinke.xinker.me', $imgPath);
    return $imgPath;
}

function checkUrl($url) {
    if (preg_match('/^http[s]?:\/\/' .
            '(([0-9]{1,3}\.){3}[0-9]{1,3}' . // IP形式的URL- 199.194.52.184
            '|' . // 允许IP和DOMAIN（域名）
            '([0-9a-z_!~*\'()-]+\.)*' . // 域名- www.
            '([0-9a-z][0-9a-z-]{0,61})?[0-9a-z]\.' . // 二级域名
            '[a-z]{2,6})' .  // first level domain- .com or .museum
            '(:[0-9]{1,4})?' .  // 端口- :80
            '((\/\?)|' .  // a slash isn't required if there is no file name
            '(\/[0-9a-zA-Z_!~\'\.;\?:@&=\+\$,%#-\/^\*\|]*)?)$/',
            $url) == 0
    ) {
        return false;
    }
    return true;
}

function createInviteCode($length = 10)
{
    $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    $str = '';
    for ($i = 0; $i < $length; $i++) {
        $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
}

function getImageUrl($imageUrl)
{
    return str_replace("static/api", config("web.self_domain") . "/static/api", $imageUrl);
}

function getHeadImageUrl($headImageUrl)
{
    if ($headImageUrl == "") {
        return \app\common\Constant::DEFAULT_HEAD_IMAGE_URL;
    } else {
        return getImageUrl($headImageUrl);
    }
}

function getNickname($nickname)
{
    if ($nickname == "") {
        return \app\common\Constant::DEFAULT_NICKNAME;
    } else {
        return $nickname;
    }
}

function gmt_iso8601($time) {
    $dtStr = date("c", $time);
    $datetime = new DateTime($dtStr);
    $expiration = $datetime->format(DateTime::ISO8601);
    $pos = strpos($expiration, '+');
    $expiration = substr($expiration, 0, $pos);
    return $expiration."Z";
}