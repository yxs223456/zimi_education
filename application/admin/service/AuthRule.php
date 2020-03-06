<?php

namespace app\admin\service;

use app\admin\model\AuthRule as AuthRuleModel;
use think\Db;

class AuthRule extends Base {

     public function __construct() {
         parent::__construct();
         $this->currentModel = new AuthRuleModel();
     }

    /**
     * 获取所有菜单列表
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getAllMenu() {

        $list = $this->currentModel
            ->order("sort desc")
            ->select();

        return object2array($list);

    }
    /**
     * 获取节点数据对应的菜单
     * @param string $nodeStr
     * @return array
     */
    public function getMenu($nodeStr = '') {
        //超级管理员没有节点数组
        $where = empty($nodeStr) ? 'status = 1' : 'status = 1 and id in('.$nodeStr.')';
        $result = Db::name('auth_rule')->where($where)->order('sort')->select();
        $menu = prepareMenu($result);
        return $menu;
    }

    /**
     * 根据角色权限获取菜单列表
     * @param $rules
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getMenuByGroupRules($rules) {

        $map["status"] = config("enum.sysMenuStatus")["valid"]['value'];

        //字符串转数组
        $idArray = explode(",", $rules);

        $list = $this->currentModel
            ->whereIn("id", $idArray)
            ->where($map)
            ->order('sort desc')
            ->select();

        return object2array($list);

    }

    /**
     * 获取权限结点
     * @param $rule
     * @return string
     */
    public function getNodeInfo($rule) {

        $result = $this->currentModel
            ->field('id,title,pid')
            ->select();

        $str = "";

        if(!empty($rule)){
            $rule = explode(',', $rule);
        }

        foreach($result as $key=>$vo){
            $str .= '{ "id": "' . $vo['id'] . '", "pId":"' . $vo['pid'] . '", "name":"' . $vo['title'].'"';

            if(!empty($rule) && in_array($vo['id'], $rule)){
                $str .= ' ,"checked":1';
            }

            $str .= '},';
        }

        return "[" . substr($str, 0, -1) . "]";
    }

}