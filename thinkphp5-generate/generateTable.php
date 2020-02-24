<?php
/**
 * Created by PhpStorm.
 * User: chenxj
 * Date: 2018/12/4
 * Time: 13:09
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

//排除列表
$exceptList = array($prefix . 'action_permission', $prefix . 'actions', $prefix . 'menu_permission', $prefix . 'menus', $prefix . 'password_resets', $prefix . 'permission_role', $prefix . 'permissions', $prefix . 'role_user', $prefix . 'roles', $prefix . 'users');

//固定列表
$staticList = array($prefix . 'menu', $prefix . 'user', $prefix . 'role', $prefix . 'action', $prefix . 'permission');

//连接数据库
$connection = mysqlConnect($server, $user, $pass, $dbname);

//删除文件夹
if (is_dir('./generated/')) {
    delDirAndFile('./generated/');
}
//获取所有数据表
if ($argv[1] == 'all') {

    $result = mysqli_query($connection, "SHOW TABLES") or die('Query failed: ' . mysqli_error($connection) . "\n");

    while ($row = mysqli_fetch_array($result)) {
        $sql = "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='{$dbname}' AND TABLE_NAME='{$row[0]}'";
        $tableField = mysqli_query($connection,$sql);
        while ($rs = mysqli_fetch_array($tableField)) {
            $tableInfo[$row[0]][] = $rs[0];
        }

    }
    //释放结果
    mysqli_free_result($result);

} elseif ($argv[1] == 'single' && !empty($argv[2])) {
    $sql = "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='{$dbname}' AND TABLE_NAME='{$argv[2]}'";
    $tableField = mysqli_query($connection,$sql);
    while ($rs = mysqli_fetch_array($tableField)) {
        $tableInfo[$argv[2]][] = $rs[0];
    }
    //释放结果
    mysqli_free_result($result);
}else {
    exit;
}

generateTableModelFile($tableInfo, $prefix);

