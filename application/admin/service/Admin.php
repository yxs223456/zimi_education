<?php

namespace app\admin\service;

use app\admin\service\Base;
use app\admin\model\Admin as AdminModel;
use think\Db;

class Admin extends Base {

     public function __construct() {
         parent::__construct();
         $this->currentModel = new AdminModel();
     }

    /**
     * 多账号登录
     * @param $account
     * @return mixed
     */
    public function findAdminForLogin($account) {

        $where['username'] = $account;
        $info = $this->currentModel
            ->where($where)
            ->find();
        return $info;

    }

    /**
     * 根据username查找
     * @param $username
     * @return array|null|\PDOStatement|string|\think\Model
     */
    public function findByUsername($username) {

        $info = $this->currentModel
            ->where('username', $username)
            ->find();

        return $info;
    }

}