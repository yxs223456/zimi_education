<?php

namespace app\admin\controller;

use think\Db;

class Config extends Common {

    /**
     * 配置列表
     * @return mixed
     */
    public function index() {

        $requestMap = $this->convertRequestToMap();

        $list = $this->configService->paginateList($requestMap);

        $this->assign('list',$list);

        return $this->fetch();

    }

    /**
     * 添加配置页面
     * @return mixed
     */
    public function add() {
        return $this->fetch();
    }

    /**
     * 添加配置操作
     * @return array|\think\response\Json
     */
    public function addPost() {

        $param = input('post.');

        $configService = model("Config","service");

        try {

            $result = $configService->saveByAllowField($param);

            if($result === false){
                $this->error($configService->getError());
            }

            cache('db_config_data',null);

            $this->success("配置添加成功",url("index"));

        } catch(\PDOException $e) {
            $this->error($e->getMessage());
        }

    }

    /**
     * 编辑配置页面
     * @return mixed
     */
    public function edit() {

        $id = input('param.id');

        $configService = model("Config","service");

        $info = $configService->findById($id);

        $this->assign('info', $info);

        return $this->fetch();

    }

    /**
     * 编辑配置操作
     * @return array|\think\response\Json
     */
    public function editPost() {

        $param = input('post.');

        $configService = model("Config","service");

        try{

            $result = $configService->updateByAllowFieldAndId($param,$param["id"]);

            if($result === false){
                $this->error($configService->getError());
            }

            cache('db_config_data',null);

            $this->success("配置编辑成功",url("index"));

        } catch(\PDOException $e) {
            $this->error($e->getMessage());
        }

    }

    /**
     * 删除操作
     * @return array|\think\response\Json
     */
    public function delete() {

        $id = input('param.id');

        $configService = model("Config","service");

        try{

            $result = $configService->deleteById($id);

            if(false === $result){
                $this->error($configService->getError());
            }

            cache('db_config_data',null);

            $this->success("配置删除成功");

        } catch(\PDOException $e) {
            $this->error($e->getMessage());
        }

    }

    /**
     * 启用配置操作
     * @return mixed
     */
    public function activate() {

        $id = input('param.id');

        $configService = model("Config","service");

        $config = $configService->findById($id);

        if($config['status'] == 1) {
            $this->error("该配置已是启用状态");
        }

        try {
            $result = $configService->updateByIdAndData($id,["status"=>1]);

            if($result === false) {
                $this->error($configService->getError());
            }

            $this->success("已开启");

        } catch (\PDOException $e) {
            $this->error($e->getMessage());
        }

    }

    /**
     * 禁用配置操作
     * @return mixed
     */
    public function deactivate() {

        $id = input('param.id');

        $configService = model("Config","service");

        $config = $configService->findById($id);

        if($config['status'] == 0) {
            $this->error("该配置已是禁用状态");
        }

        try {
            $result = $configService->updateByIdAndData($id,["status"=>0]);

            if($result === false) {
                $this->error($configService->getError());
            }

            $this->success("已禁用");

        } catch (\PDOException $e) {
            $this->error($e->getMessage());
        }

    }

    /**
     * 根据标签获取配置信息
     * @return mixed
     */
    public function group() {

        $id   = input('id',1);

        $type = config('config_group_list');

        $list = $this->configService->getConfigByGroupId($id);

        $this->assign("list",$list);
        $this->assign('id',$id);

        return $this->fetch();

    }

    /**
     * 批量保存配置
     * @param $config
     */
    public function save($config){

        if($config && is_array($config)){

            try {

                $Config = Db::name('Config');
                foreach ($config as $name => $value) {
                    $map = array('name' => $name);
                    $Config->where($map)->setField('value', $value);
                }

            } catch (\PDOException $e) {
                $this->error($e->getMessage());
            }

        }

        cache('db_config_data',null);

        $this->success('保存成功！');

    }

}