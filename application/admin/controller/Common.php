<?php

namespace app\admin\controller;

use app\admin\controller\Base;
use think\Controller;
use think\Request;

class Common extends Base {

    protected $beforeActionList = [
        'filterAdmin',
    ];

    protected function filterAdmin() {

        if(!session('uid')||!session('username')){
            $this->redirect("admin/Login/index");
        }

        $auth = new \com\Auth();
        $module     = strtolower(request()->module());
        $controller = strtolower(request()->controller());
        $action     = strtolower(request()->action());
        $url        = $module."/".$controller."/".$action;

        //获取该管理员的角色信息
        $authRuleService = model("AuthRule","service");
        $authGroup = model("AuthGroup", "service");

        $group = $authGroup->findById(session('group_id'));

        if($group["is_root"] == 1) { //是超级管理员

            $rules = '';

            $ruleList = $authRuleService->getAllMenu();
        } else { //不是超级管理员

            $rules = $group['rules'];

            $ruleList = $authRuleService->getMenuByGroupRules($rules);
        }

        //跳过检测以及主页权限
        if(session('uid')!=1){
            if(!in_array($url, ['admin/index/index','admin/index/indexpage'])){
                if (!in_array($url, mergeRulesToArray($ruleList))) {
                    $this->error('抱歉，您没有操作权限', 'admin/index/indexPage');
                }
            }
        }

        $this->assign([
            'currentUser' => session('username'),
            'portrait' => session('portrait'),
            'roleName' => $group['title'],
            'menu' => $authRuleService->getMenu($rules)
        ]);

        //读取配置文件
        $config = cache('db_config_data');

        if(!$config){
            $config = api('Config/lists');
            cache('db_config_data',$config);
        }
        config($config);

        //单独设置后台分页数量（二维配置无法和一维配置同时应用）
        config("paginate.list_rows",intval($config["paginate.list_rows"]));

    }

    /**
     * 请求转换为查询条件
     * @return array
     */
    protected function convertRequestToMap() {

        $map = [];
        $conditionMap = [];
        $pageMap = [];

        foreach(input("param.") as $key => $value) {
            if(!in_array($key,["page","export"])) {
                if(!isNullOrEmpty($value)) {
                    $conditionMap[$key] = $value;
                    $arr1 = explode('-',$key);
                    $arr2 = explode('#', $arr1[0]);
                    $trueKey = count($arr2) > 1 ? $arr2[1] : $arr2[0];
                    $pageMap[urlencode($key)] = $value;
                    $this->assign($trueKey, $value);
                }
            }
        }

        $map["condition"] = $conditionMap;
        $map["page"] = $pageMap;

        $this->assign("pageNum", input("?param.page") ? input("param.page") : 1);

        return $map;

    }

}