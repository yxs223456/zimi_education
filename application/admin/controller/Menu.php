<?php

namespace app\admin\controller;
use \org\LeftNav;
use think\Db;

class Menu extends Common {

    /**
     * 菜单列表
     * @return mixed
     */
    public function index() {

        $nav = new LeftNav();

        $menuList = $this->authRuleService->getAllMenu();

        $arr = $nav::rule($menuList);

        $this->assign('list',$arr);

        return $this->fetch();

    }

    /**
     * 添加菜单操作
     * @return mixed|\think\response\Json
     */
	public function addPost() {

        $param = input('post.');

        Db::startTrans();
        try {

            $this->authRuleService->saveByData($param);

            Db::commit();

        } catch(\PDOException $e) {

            Db::rollback();

            $this->error('服务器错误,请稍后再试');

        }

        $this->success("菜单添加成功",url("index"));

    }

    /**
     * 菜单编辑页面
     * @return mixed
     */
    public function edit() {

        $id = input('param.id');

        $menuList = $this->authRuleService->getAllMenu();

        $nav = new LeftNav();

        $arr = $nav::rule($menuList);

        $this->assign('menu',$this->authRuleService->findById($id));

        $this->assign('list',$arr);

        return $this->fetch();

    }

    /**
     * 菜单编辑操作
     * @return \think\response\Json
     */
    public function editPost() {

        $param = input('post.');

        Db::startTrans();
        try {

            $this->authRuleService->updateByIdAndData($param['id'], $param);

            Db::commit();

        } catch(\PDOException $e) {

            Db::rollback();

            $this->error('服务器错误,请稍后再试');

        }

        $this->success("菜单编辑成功",url("index"));

    }


    /**
     * 菜单删除
     * @return array|\think\response\Json
     */
    public function delete() {

        $id = input('param.id');

        $map["pid"] = $id;

        $subMenu = $this->authRuleService->findByMap($map);

        if(!isNullOrEmpty($subMenu)) {
            $this->error("请首先删除子菜单");
        }

        try {

           $result = $this->authRuleService->deleteById($id);

           if(false === $result) {
               $this->error($this->authRuleService->getError());
           }

           $this->success("菜单删除成功", url('index'));

        }catch(\PDOException $e){
            $this->error($e->getMessage());
        }

    }

    /**
     * 排序操作
     * @return \think\response\Json
     */
    public function order() {

        $param = input('post.');

        $auth_rule = Db::name('auth_rule');

        foreach ($param as $id => $sort){
            $auth_rule->where(array('id' => $id ))->setField('sort',$sort);
        }

        $this->success("排序更新成功");

    }

    /**
     * 启用菜单操作
     * @return mixed
     */
    public function activate() {

        $id = input('param.id');

        $menu = $this->authRuleService->findById($id);

        if($menu['status'] == 1) {
            $this->error("该菜单已是启用状态");
        }

        try {

            $result = $this->authRuleService->updateByIdAndData($id,["status"=>1]);

            if(false === $result) {
                $this->error($this->authRuleService->getError());
            }

            $this->success("已开启");

        } catch (\PDOException $e) {
            $this->error($e->getMessage());
        }

    }

    /**
     * 禁用菜单操作
     * @return mixed
     */
    public function deactivate() {

        $id = input('param.id');

        $menu = $this->authRuleService->findById($id);

        if($menu['status'] == 0) {
            $this->error("该菜单已是禁用状态");
        }

        try {
            $result = $this->authRuleService->updateByIdAndData($id,["status"=>0]);

            if($result === false) {
                $this->error($this->authRuleService->getError());
            }

            $this->success("已禁用");

        } catch (\PDOException $e) {
            $this->error($e->getMessage());
        }

    }

}