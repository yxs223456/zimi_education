<?php
/**
 * Created by PhpStorm.
 * User: houchaowei
 * Date: 2017/8/16
 * Time: 11:02
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
        //获取所有表名
        $tableName = $row[0];

        //获取所有主键
        if (!in_array($tableName, $exceptList)) {
            $primaryKeyName = getPrimaryKey($tableName, $connection);
            //完整数据表信息
            $tableList[$tableName] = array('tableName' => $tableName, 'primaryKeyName' => $primaryKeyName);
        }

    }
    //释放结果
    mysqli_free_result($result);

} elseif ($argv[1] == 'single' && !empty($argv[2])) {

    $table[$argv[2]] = array('tableName' => $argv[2], 'primaryKeyName' => 'id');
}

if ($argv[1] == 'all') {

    generateDir();
    generateBaseFile($tableList, $prefix);
    generateService($tableList, $prefix);
    generateModel($tableList, $prefix);
} elseif ($argv[1] == 'single' && !empty($argv[2])) {

    generateDir();
    generateBaseFile($table, $prefix);
    generateService($table, $prefix);
    generateModel($table, $prefix);
}
