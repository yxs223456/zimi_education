<?php

namespace app\admin\controller;

use think\Db;

class Role extends Common {

    /**
     * 角色列表
     * @return mixed
     */
    public function index() {

        $requestMap = $this->convertRequestToMap();

        $list = $this->authGroupService->paginateList($requestMap);

        foreach ($list as $item) {
            $item["create_time"] = date("Y-m-d H:i:s", $item["create_time"]);
            $item["update_time"] = date("Y-m-d H:i:s", $item["update_time"]);
        }

        $this->assign('list',$list);

        return $this->fetch();
    }

    /**
     * 添加角色页面
     * @return mixed
     */
    public function add() {
        return $this->fetch();
    }

    /**
     * 添加角色操作
     * @return \think\response\Json
     */
    public function addPost() {

        $param = input('post.');
        $param["create_time"] = $param["update_time"] = time();
        //数据校验
        $validate = validate("authGroupValidate");
        if(false === $validate->scene($this->request->action(true))
                ->check($this->request->param())) {
            $this->error($validate->getError());
        }

        Db::startTrans();
        try {

            $this->authGroupService->saveByData($param);

            Db::commit();

        } catch(\PDOException $e) {

            Db::rollback();

            $this->error('服务器错误,请稍后再试');

        }

        $this->success("角色添加成功",url("index"));

    }

    /**
     * 编辑角色页面
     * @return mixed
     */
    public function edit() {

        $id = input('param.id');

        $this->assign("role",$this->authGroupService->findById($id));

        return $this->fetch();

    }

    /**
     * 编辑角色操作
     * @return \think\response\Json
     */
    public function editPost() {

        $param = input('post.');

        //数据校验
        $validate = validate("authGroupValidate");
        if(false === $validate->scene($this->request->action(true))
                ->check($this->request->param())) {
            $this->error($validate->getError());
        }

        Db::startTrans();
        try {

            $this->authGroupService->updateByIdAndData($param['id'], $param);

            Db::commit();

        } catch(\PDOException $e) {

            Db::rollback();

            $this->error('服务器错误,请稍后再试');

        }

        $this->success("角色编辑成功",url("index"));

    }

    /**
     * 删除角色操作
     * @return \think\response\Json
     */
    public function delete() {

        $id = input('param.id');

        Db::startTrans();
        try {

            $this->authGroupService->deleteById($id);

            Db::commit();

        } catch(\PDOException $e) {

            Db::rollback();

            $this->error('服务器错误,请稍后再试');

        }

        $this->success("角色删除成功");

    }

    /**
     * 启用角色操作
     * @return mixed
     */
    public function activate() {

        $id = input('param.id');

        $authGroupService = model("AuthGroup","service");

        $authGroup = $authGroupService->findById($id);

        if($authGroup['status'] == 1) {
            $this->error("该角色已是启用状态");
        }

        try {

            $result = $authGroupService->updateByIdAndData($id,["status"=>1]);

            if(false === $result) {
                $this->error($authGroupService->getError());
            }

            $this->success("已开启");

        } catch (\PDOException $e) {
            $this->error($e->getMessage());
        }

    }

    /**
     * 禁用管理员操作
     * @return mixed
     */
    public function deactivate() {

        $id = input('param.id');

        $authGroupService = model("AuthGroup","service");

        $authGroup = $authGroupService->findById($id);

        if($authGroup['status'] == 0) {
            $this->error("该角色已是禁用状态");
        }

        try {

            $result = $authGroupService->updateByIdAndData($id,["status"=>0]);

            if(false === $result) {
                $this->error($authGroupService->getError());
            }

            $this->success("已禁用");

        } catch (\PDOException $e) {
            $this->error($e->getMessage());
        }

    }

    /**
     * 分配权限
     * @return \think\response\Json
     */
    public function giveAccess() {

        $param = input('param.');

        $currentGroup = $this->authGroupService->findById($param["id"]);

        //获取现在的权限
        if('get' == $param['type']){

            $nodeStr = $this->authRuleService
                ->getNodeInfo($currentGroup['rules']);

            $this->result($nodeStr,1,"success");

        }

        //分配新权限
        if('give' == $param['type']){

            $authGroupService = model("AuthGroup","service");

            $data["id"] = $param["id"];
            $data["rules"] = $param["rule"];

            try {

                $result = $this->authGroupService->updateByIdAndData($param["id"],$data);

                if(false === $result) {
                    $this->result("",-1,$authGroupService->getError());
                }

                $this->result("",1,"分配权限成功");

            } catch (\PDOException $e) {
                $this->result("",-2,$e->getMessage());
            }

        }

    }

}