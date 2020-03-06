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
$sysConstantsTableName = $config['sysConstantsTableName'];

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
    $sql = "SELECT * FROM ".$prefix.$sysConstantsTableName." WHERE status=1";
}else if($argv[1] == 'single' && !empty($argv[2])){
    $sql = "SELECT * FROM ".$prefix.$sysConstantsTableName." WHERE status=1 AND constants_code='{$argv[2]}'";
} else {
    exit;
}
$result = mysqli_query($connection,$sql);
$data=[];
while ($rs = mysqli_fetch_assoc($result)) {
    if (array_key_exists($rs['constants_code'], $data)) {
        $data[$rs['constants_code']]['constants'][$rs['id']] = ['constants_name'=>$rs['constants_name'],'key'=>$rs['key'],'value'=>$rs['value'],'constants_type'=>$rs['constants_type'],'object'=>$rs['object'],'status'=>$rs['status']];
        $data[$rs['constants_code']]['constants_name'] = $rs['constants_name'];
        $data[$rs['constants_code']]['constants_type'] = $rs['constants_type'];
        $data[$rs['constants_code']]['constants_code'] = $rs['constants_code'];
    }else{
        $data[$rs['constants_code']]['constants'][$rs['id']] = ['constants_name'=>$rs['constants_name'],'key'=>$rs['key'],'value'=>$rs['value'],'constants_type'=>$rs['constants_type'],'object'=>$rs['object'],'status'=>$rs['status']];;
        $data[$rs['constants_code']]['constants_name'] = $rs['constants_name'];
        $data[$rs['constants_code']]['constants_type'] = $rs['constants_type'];
        $data[$rs['constants_code']]['constants_code'] = $rs['constants_code'];
    }
}
//释放结果
mysqli_free_result($result);
//print_r($data);die;
generateConstants($data);