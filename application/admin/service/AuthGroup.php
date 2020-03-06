<?php

namespace app\admin\service;

use app\admin\service\Base;
use app\admin\model\AuthGroup as AuthGroupModel;
use think\Db;

class AuthGroup extends Base {

     public function __construct() {
         parent::__construct();
         $this->currentModel = new AuthGroupModel();
     }

     public function getRole() {
        return $this->currentModel->where('id','<>',1)->select();
    }

    /**
     * 获取角色的权限节点
     * @param $id
     * @return mixed
     */
    public function getRuleById($id) {
        $res = $this->currentModel->field('rules')->where('id', $id)->find();
        return $res['rules'];
    }

    /**
     * 获取角色权限信息
     * @param $id
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function getRoleInfo($id){

        $result = Db::name('auth_group')->where('id', $id)->find();
        if(empty($result['rules'])){
            $where = '';
        }else{
            $where = 'id in('.$result['rules'].')';
        }
        $res = Db::name('auth_rule')->field('name')->where($where)->select();

        foreach($res as $key=>$vo){
            if('#' != $vo['name']){
                $result['name'][] = $vo['name'];
            }
        }

        return $result;
    }
}