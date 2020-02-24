<?php
/**
 * Created by PhpStorm.
 * User: chenxj
 * Date: 2018/11/30
 * Time: 17:03
 */
//引入工具包
require_once('util.php');
//引入配置文件
$config = include('config.php');

//基本配置信息
$server = $config['server'];
$user = $config['username'];
$pass = $config['password'];
$dbname = $config['database'];
$prefix = $config['prefix'];
$errorTipsTableName = $config['errorTipsTableName'];

//排除列表
//$exceptList = array($prefix . 'action_permission', $prefix . 'actions', $prefix . 'menu_permission', $prefix . 'menus', $prefix . 'password_resets', $prefix . 'permission_role', $prefix . 'permissions', $prefix . 'role_user', $prefix . 'roles', $prefix . 'users');

//固定列表
//$staticList = array($prefix . 'menu', $prefix . 'user', $prefix . 'role', $prefix . 'action', $prefix . 'permission');

//连接数据库
$connection = mysqlConnect($server, $user, $pass, $dbname);

//删除文件夹
if (is_dir('./generated/')) {
    delDirAndFile('./generated/');
}
$result = mysqli_query($connection, "SHOW TABLES") or die('Query failed: ' . mysqli_error($connection) . "\n");

if ($argv[1] == 'all') {
    $sql = "SELECT * FROM ".$prefix.$errorTipsTableName." WHERE status=1";
}else if($argv[1] == 'single' && !empty($argv[2])){
    $sql = $sql = "SELECT * FROM ".$prefix.$errorTipsTableName." WHERE status=1 AND code='{$argv[2]}'";
}else {
    exit;
}

//$sql = "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='{$dbname}' AND TABLE_NAME='{$prefix}{$dictionaryTableName}'";
//print_r($sql);die;
$result = mysqli_query($connection,$sql);
$data=[];

while ($rs = mysqli_fetch_assoc($result)) {
    $data[$rs['id']] = $rs;
}
//释放结果
mysqli_free_result($result);
if ($argv[1] == 'all') {
    generateErrorTips($data);
}else if($argv[1] == 'single' && !empty($argv[2])){
    generateErrorTipsForSingle($data);
}else {
    exit;
}
