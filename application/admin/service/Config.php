<?php

namespace app\common\service;

use app\admin\service\Base;
use app\admin\model\Config as ConfigModel;

class Config extends Base {

     public function __construct() {
         parent::__construct();
         $this->currentModel = new ConfigModel();
     }

    /**
     * 根据标签获取配置列表
     * @param $groupId
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getConfigByGroupId($groupId) {

        $map["status"] = 1;
        $map["group"] = $groupId;

        $list = $this->currentModel->field('id,name,title,extra,value,remark,type')
            ->where($map)
            ->order('sort ASC')
            ->select();

        return $list;
    }

}