<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

require __DIR__ . "/helper/wxTemplate.php";
require __DIR__ . "/helper/redisTemplate.php";

// 应用公共文件

use taobao\AliSms;

/**
 * 判空
 * @param $obj
 * @return bool
 */
function isNullOrEmpty($obj)
{
    return (!isset($obj) || $obj == "" || $obj == null | $obj == []);
}

/**
 * 获取枚举含义
 * @param $enum
 * @param $chosenValue
 * @return mixed
 */
function getEnumString($enum, $chosenValue)
{

    $returnStr = "";

    foreach ($enum as $key => $value) {
        if ($chosenValue == $value["value"]) {
            $returnStr = $value["desc"];
            break;
        }
    }

    return $returnStr;
}

/**
 * 获取枚举的值
 * @param $enum
 * @param $chosenKey
 * @return string
 */
function getEnumValue($enum, $chosenKey)
{

    $returnValue = "";

    foreach ($enum as $key => $value) {
        if ($chosenKey == $key) {
            $returnValue = $value["value"];
            break;
        }
    }

    return $returnValue;

}

/**
 * 枚举select生成
 * @param $enum
 * @param $name
 * @param string $default
 * @param int $val
 * @param int $hasAll
 * @return string
 */
function getEnumSelectWidget($enum, $name, $default = "请选择", $val = "-999", $hasAll = 0)
{

    $all = $hasAll == 1 ? '<option value="">' . $default . '</option>' : '';

    $html = '<select class="form-control chosen-select" id=\'' . $name . '\' name=\'' . $name . '\'>' . $all;

    foreach ($enum as $key => $value) {

        $selected = '';

        if ($value["value"] == $val)
            $selected = 'selected="selected"';

        $html .= '<option value="' . $value["value"] . '" ' . $selected . '>' . $value["desc"] . '</option>';

    }

    $html .= '</select>';

    return $html;
}

/**
 * 获取当前日期
 * @return false|string
 */
function getCurrentDate()
{
    return date('Y-m-d');
}

/**
 * 获取当前日期和时间
 * @return false|string
 */
function getCurrentTime()
{
    return date('Y-m-d H:i:s');
}

/**
 * 获取当前时间戳
 * @return false|string
 */
function getCurrentStamp()
{
    return time();
}

/**
 * 获取今天起始时间
 * @return bool|string
 */
function getToDayStartTime()
{
    return date("Y-m-d H:i:s", mktime(0, 0, 0, date("m", time()), date("d", time()), date("Y", time())));
}

/**
 * 获取今天结束时间
 * @return bool|string
 */
function getToDayEndTime()
{
    return date("Y-m-d H:i:s", mktime(23, 59, 59, date("m", time()), date("d", time()), date("Y", time())));
}

/**
 * 获取终端初始码
 * @return mixed
 */
function getInitCode()
{
    return mt_rand(100000, 999999) . mt_rand(100000, 999999);
}

/**
 * 获取商品条形码
 * @return string
 */
function getBarCode()
{
    return mt_rand(100000, 999999) . mt_rand(1000000, 9999999);
}

/**
 * 计算两个时间的时差
 * @param $begin_time
 * @param $end_time
 * @return array
 */
function timeDiff($begin_time, $end_time)
{
    if ($begin_time < $end_time) {
        $starttime = $begin_time;
        $endtime = $end_time;
    } else {
        $starttime = $end_time;
        $endtime = $begin_time;
    }
    $timediff = $endtime - $starttime;
    $days = intval($timediff / 86400);
    $remain = $timediff % 86400;
    $hours = sprintf("%02d", intval($remain / 3600));
    $remain = $remain % 3600;
    $mins = sprintf("%02d", intval($remain / 60));
    $secs = sprintf("%02d", $remain % 60);
    $res = array("day" => $days, "hour" => $hours, "min" => $mins, "sec" => $secs);
    return $res;
}

/**
 * 计算两个日期格式时间的时差
 * @param $startDate string 开始时间
 * @param $endDate string 结束时间
 * @return string 时间差
 */
function diffDateTime($startDate, $endDate)
{
    //将时间转换为时间戳
    $startTime = strtotime($startDate);
    $endTime = strtotime($endDate);
    //求时间差
    $diff = $endTime - $startTime;
    //将时间差时间戳转换为天数或者其他时间单位
    return trim($diff / (24 * 60 * 60), '-');
}

/**
 * 生成n位随机数
 * @param int $length
 * @return string
 */
function createRandomKey($length = 32)
{
    $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
    $str = "";
    for ($i = 0; $i < $length; $i++) {
        $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
}

/**
 * 生成n位包含$string的随机数
 * @param int $length
 * @param string $str
 * @return string
 */
function createRandomStringKey($length = 32, $chars = "abcdefghijklmnopqrstuvwxyz0123456789")
{
    $str = "";
    for ($i = 0; $i < $length; $i++) {
        $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
}

/**
 * 生成邀请码
 * @return string
 */
function createGuid()
{
    $code = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $rand = $code[rand(0, 25)]
        . strtoupper(dechex(date('m')))
        . date('d') . substr(time(), -5)
        . substr(microtime(), 2, 5)
        . sprintf('%02d', rand(0, 99));
    for (
        $a = md5($rand, true),
        $s = '0123456789ABCDEFGHIJKLMNOPQRSTUV',
        $d = '',
        $f = 0;
        $f < 8;
        $g = ord($a[$f]),
        $d .= $s[($g ^ ord($a[$f + 8])) - $g & 0x1F],
        $f++
    ) ;
    return $d;
}

/**
 * excel表格导出
 * @param string $fileName 文件名称
 * @param array $headArr 表头名称
 * @param array $data 要导出的数据
 * @author static7
 */
function excelExport($fileName = '', $headArr = [], $data = [])
{
    $fileName .= "_" . date("Y_m_d", \think\Request::instance()->time()) . ".xlsx";
    $objPHPExcel = new \PHPExcel();
    $objPHPExcel->getProperties();
    $key = ord("A"); // 设置表头
    foreach ($headArr as $v) {
        $colum = chr($key);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($colum . '1', $v);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($colum . '1', $v);
        $key += 1;
    }
    $column = 2;
    $objActSheet = $objPHPExcel->getActiveSheet();
    foreach ($data as $key => $rows) { // 行写入
        $span = ord("A");
        foreach ($rows as $keyName => $value) { // 列写入
            $objActSheet->setCellValue(chr($span) . $column, $value);
            $span++;
        }
        $column++;
    }
    $fileName = iconv("utf-8", "gb2312", $fileName); // 重命名表
    $objPHPExcel->setActiveSheetIndex(0); // 设置活动单指数到第一个表,所以Excel打开这是第一个表
    header('Content-Type: application/vnd.ms-excel');
    header("Content-Disposition: attachment;filename='$fileName'");
    header('Cache-Control: max-age=0');
    $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save('php://output'); // 文件通过浏览器下载
    exit();
}

/**
 * 字符串截取，支持中文和其他编码
 */
function msubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = true)
{
    if (function_exists("mb_substr"))
        $slice = mb_substr($str, $start, $length, $charset);
    elseif (function_exists('iconv_substr')) {
        $slice = iconv_substr($str, $start, $length, $charset);
        if (false === $slice) {
            $slice = '';
        }
    } else {
        $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("", array_slice($match[0], $start, $length));
    }
    return $suffix ? $slice . '...' : $slice;
}

/**
 * 调用系统的API接口方法（静态方法）
 * api('User/getName','id=5'); 调用公共模块的User接口的getName方法
 * api('Admin/User/getName','id=5');  调用Admin模块的User接口
 * @param  string $name 格式 [模块名]/接口名/方法名
 * @param  array|string $vars 参数
 */
function api($name, $vars = array())
{
    $array = explode('/', $name);
    $method = array_pop($array);
    $classname = array_pop($array);
    $module = $array ? array_pop($array) : 'admin';
    $callback = 'app\\' . $module . '\\api\\' . $classname . '::' . $method;
    if (is_string($vars)) {
        parse_str($vars, $vars);
    }
    return call_user_func_array($callback, $vars);
}


/**
 * 读取配置
 * @return array
 */
// function load_config(){
//     $list = Db::name('config')->where('status',1)->select();
//     $config = [];
//     foreach ($list as $k => $v) {
//         $config[trim($v['name'])]=$v['value'];
//     }

//     return $config;
// }

/**
 * 获取配置的分组
 * @param string $group 配置分组
 * @return string
 */
function get_config_group($group = 0)
{
    $list = config('config_group_list');
    return $group ? $list[$group] : '';
}

/**
 * 获取配置的类型
 * @param string $type 配置类型
 * @return string
 */
function get_config_type($type = 0)
{
    $list = config('config_type_list');
    return $list[$type];
}


/**
 * 发送短信(参数：签名,模板（数组）,模板ID，手机号)
 */
function sms($signname = '', $param = [], $code = '', $phone)
{
    $alisms = new AliSms();
    $result = $alisms->sign($signname)->data($param)->code($code)->send($phone);
    return $result['info'];
}


/**
 * 循环删除目录和文件
 * @param string $dir_name
 * @return bool
 */
function delete_dir_file($dir_name)
{
    $result = false;
    if (is_dir($dir_name)) {
        if ($handle = opendir($dir_name)) {
            while (false !== ($item = readdir($handle))) {
                if ($item != '.' && $item != '..') {
                    if (is_dir($dir_name . DS . $item)) {
                        delete_dir_file($dir_name . DS . $item);
                    } else {
                        unlink($dir_name . DS . $item);
                    }
                }
            }
            closedir($handle);
            if (rmdir($dir_name)) {
                $result = true;
            }
        }
    }

    return $result;
}

/**
 * 时间格式化
 * @param $time
 * @return string
 */
function formatTime($time)
{
    $now_time = time();
    $t = $now_time - $time;
    $mon = (int)($t / (86400 * 30));
    if ($mon >= 1) {
        return '一个月前';
    }
    $day = (int)($t / 86400);
    if ($day >= 1) {
        return $day . '天前';
    }
    $h = (int)($t / 3600);
    if ($h >= 1) {
        return $h . '小时前';
    }
    $min = (int)($t / 60);
    if ($min >= 1) {
        return $min . '分钟前';
    }
    return '刚刚';
}

/**
 * 时间格式化
 * @param $time
 * @return string
 */
function pincheTime($time)
{
    $today = strtotime(date('Y-m-d')); //今天零点
    $here = (int)(($time - $today) / 86400);
    if ($here == 1) {
        return '明天';
    }
    if ($here == 2) {
        return '后天';
    }
    if ($here >= 3 && $here < 7) {
        return $here . '天后';
    }
    if ($here >= 7 && $here < 30) {
        return '一周后';
    }
    if ($here >= 30 && $here < 365) {
        return '一个月后';
    }
    if ($here >= 365) {
        $r = (int)($here / 365) . '年后';
        return $r;
    }
    return '今天';
}

/**
 * 解析请求转换为条件
 * @param $map
 * @return array
 */
function getMapFromRequest($map)
{

    $queryMap = [];

    if (!isNullOrEmpty($map)) {
        foreach ($map as $key => $value) {

            $keyArray = explode("-", $key);

            $flag = "eq";

            if (count($keyArray) > 1) {
                if (!isNullOrEmpty($keyArray[1])) {
                    $flag = $keyArray[1];
                }
            }

            $columnName = str_replace("#", ".", $keyArray[0]);

            switch ($flag) {
                case "eq":
                    array_push($queryMap, [
                        $columnName, "=", $value
                    ]);
                    break;
                case "leftLike":
                    array_push($queryMap, [
                        $columnName, "like", "%" . $value
                    ]);
                    break;
                case "rightLike":
                    array_push($queryMap, [
                        $columnName, "like", $value . "%"
                    ]);
                    break;
                case "like":
                    array_push($queryMap, [
                        $columnName, "like", "%" . $value . "%"
                    ]);
                    break;
                case "in":
                case "lt":
                case "elt":
                case "gt":
                case "egt":
                    array_push($queryMap, [
                        $columnName, $flag, $value
                    ]);
                    break;
                case "between":
                    array_push($queryMap, [
                        $columnName, $flag, explode(" , ", $value)
                    ]);
                    break;
                default:
                    break;
            }

        }
    }

    return $queryMap;

}

/**
 * post请求
 * @param $url
 * @param $post_data
 * @return mixed
 */
function curlPost($url,$post_data) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // post数据
    curl_setopt($ch, CURLOPT_POST, 1);
    // post的变量
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
    $output = curl_exec($ch);
    curl_close($ch);
    //返回获得的数据
    return $output;
}

/**
 * 大整数加法计算
 * @param $numOne
 * @param $numTwo
 * @return string
 */
function plus($numOne, $numTwo)
{

    $numOne = $numOne . "";
    $numTwo = $numTwo . "";

    $m = strlen($numOne);
    $n = strlen($numTwo);
    $num = $m > $n ? $m : $n;
    $result = '';
    $flag = 0;
    while ($num--) {
        $t1 = 0;
        $t2 = 0;
        if ($m > 0) {
            $t1 = $numOne[--$m];
        }
        if ($n > 0) {
            $t2 = $numTwo[--$n];
        }
        $t = $t1 + $t2 + $flag;
        $flag = $t / 10;
        $result = ($t % 10) . $result;
    }

    return $result;

}

//框架中的obj转数组
function object2array(&$object)
{
    $object = json_decode(json_encode($object), true);
    return $object;
}

/**
 * 合并权限到数组
 * @param $rules
 * @return array
 */
function mergeRulesToArray($rules)
{

    $array = array();

    foreach ($rules as $rule) {
        if ($rule["name"] != "#") {
            array_push($array, strtolower($rule["name"]));
        }
    }

    return $array;

}

/**
 * 后台管理员生成密码规则
 * @param $authKey
 * @param $password
 * @return string
 */
function generatePassword($authKey, $password)
{
    return md5(md5($password) . $authKey);
}

/**
 * 生成token
 * @param string $prefix
 * @return string
 */
function createToken($prefix = "")
{    //可以指定前缀
    $str = md5(uniqid(mt_rand(), true));
    $uuid = substr($str, 0, 8) . '-';
    $uuid .= substr($str, 8, 4) . '-';
    $uuid .= substr($str, 12, 4) . '-';
    $uuid .= substr($str, 16, 4) . '-';
    $uuid .= substr($str, 20, 12);
    return $prefix . $uuid;
}

/**
 * 生成有序 uuid
 * @return string
 */
function createUuid(){

    $hexChar = ['0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F'];

    date_default_timezone_set('PRC');
    $mTimestamp = sprintf("%.3f", microtime(true));

    $mTimestampStr = str_replace(".", "", $mTimestamp);

    $key = "A" . strtoupper(base_convert($mTimestampStr, 10, 16));

    for($i=0; $i<20; $i++) {
        $key .= $hexChar[rand(0,15)];
    }

    return strtolower($key);
}

/**
 * 验证值是否在枚举中
 * @param $value
 * @param $enum
 * @return bool
 */
function checkEnumValue($value, $enum)
{
    $flag = false;
    foreach ($enum as $key => $val) {
        if ($value == $val['value']) {
            $flag = true;
            break;
        }
    }
    return $flag;

}

/**
 * 生成任务编号
 * @return string
 */
function generateMissionCode() {
    return time().mt_rand(10000,99999);
}

/**
 * 检验数据的真实性，并且获取解密后的明文.
 * @param $sessionKey
 * @param $appid
 * @param $encryptedData string 加密的用户数据
 * @param $iv string 与用户数据一同返回的初始向量
 * @param $data string 解密后的原文
 * @return bool
 */
function decryptData($sessionKey, $appid, $encryptedData, $iv, &$data)
{

    if (strlen($sessionKey) != 24) {
        return false;
    }
    $aesKey = base64_decode($sessionKey);


    if (strlen($iv) != 24) {
        return false;
    }
    $aesIV = base64_decode($iv);

    $aesCipher = base64_decode($encryptedData);

    $result = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);

    $dataObj = json_decode($result);
    if ($dataObj == NULL) {
        return false;
    }
    if ($dataObj->watermark->appid != $appid) {
        return false;
    }

    $data = object2array($dataObj);
    return true;

}

/**
 * 指定位置插入字符串
 * @param $str
 * @param $i
 * @param $substr
 * @return string 处理后的字符串
 */
function insertToStr($str, $i, $substr){
    //指定插入位置前的字符串
    $startstr="";
    for($j=0; $j<$i; $j++){
        $startstr .= $str[$j];
    }

    //指定插入位置后的字符串
    $laststr="";
    for ($j=$i; $j<strlen($str); $j++){
        $laststr .= $str[$j];
    }

    //将插入位置前，要插入的，插入位置后三个字符串拼接起来
    $str = $startstr . $substr . $laststr;

    //返回结果
    return $str;
}

/**
 * 检查一个值是否存在多维数组中
 * @param $value
 * @param $array
 * @return bool
 */
function deep_in_array($value, $array) {
    foreach($array as $item) {
        if(!is_array($item)) {
            if ($item == $value) {
                return true;
            } else {
                continue;
            }
        }

        if(in_array($value, $item)) {
            return true;
        } else if(deep_in_array($value, $item)) {
            return true;
        }
    }
    return false;
}

/**
 * 校验是否是json格式
 * @param $jsonStr
 * @return bool
 */
function checkJson($jsonStr)
{
    if (!is_string($jsonStr)) {
        return false;
    }
    $arr = json_decode($jsonStr, true);
    return is_array($arr);
}

/**
 * 超出字符串长度用coverStr代替
 * @param $str
 * @param int $length
 * @param string $coverStr
 * @return string
 */
function strExceedingLength($str, $length=3, $coverStr='...') {
    if (mb_strlen($str, 'utf-8')>$length) $str=mb_substr($str,0,$length) . $coverStr;
    return $str;
}

//将XML转为array
function xmlToArray($xml)
{
    //禁止引用外部xml实体
    libxml_disable_entity_loader(true);
    $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    return $values;
}

//隐藏手机号
function hidePhone($phone)
{
    if (strlen($phone) != 11) {
        return $phone;
    }
    $phone[3] = "*";
    $phone[4] = "*";
    $phone[5] = "*";
    $phone[6] = "*";
    return $phone;
}