<?php

namespace app\admin\controller;

use app\admin\controller\Base;
use org\Verify;
//use com\Geetestlib;
use think\Controller;
use \think\facade\Session;

class Login extends Base {

    protected $uid;
    protected $username;

    public function _initialize() {
        parent::_initialize();
    }

    //登录页面
    public function index() {
        return $this->fetch('/login');
    }

    //登录操作
    public function doLogin() {

        $username = input("param.username");
        $password = input("param.password");

        if(isNullOrEmpty($username)) {
            $this->error('用户名不能为空');
        }

        $hasUser = $this->adminService->findAdminForLogin($username);

        if(isNullOrEmpty($hasUser)) {
            $this->error('用户不存在');
        }


        $hasUser = $this->adminService->findAdminForLogin($username);

        if(empty($hasUser)){
            $this->error("管理员不存在");
        }

        if(md5($password) != $hasUser['password']){
            $this->error("账号或密码错误");
        }

        if(1 != $hasUser['status']){
            $this->error("该账号被禁用");
        }

        Session::set('uid', $hasUser['id']);             //用户ID
        Session::set('username', $username);             //用户名
        Session::set('portrait', $hasUser['portrait']);  //用户头像
        Session::set('group_id', $hasUser['group_id']);           //角色ID

        $this->success("登录成功",url('index/index'));
    }

    //退出操作
    public function logout() {
        session('uid', null);
        session('username', null);
        session('rule', null);
        cache('db_config_data',null);
        $this->redirect("admin/Login/index");
    }

}