<?php

namespace com;

use think\facade\Config;

trait BaseServiceTrait {

    /**
     * 根据主键ID查询记录
     * @param $id
     * @return mixed
     */
    public function findById($id) {
        return $this->currentModel->where([$this->currentModel->getPk() => $id])->find();
    }

    /**
     * 获取所有数据
     * @param string $sort
     * @return mixed
     */
    public function getAll($sort="desc") {

        return $this->currentModel
            ->order($this->currentModel->getPk()." $sort")
            ->select();

    }

    public function findByMap($map, $order=null) {
        return $this->currentModel->where($map)
            ->order(isNullOrEmpty($order) ? $this->currentModel->getPk()." desc" : $order)
            ->find();
    }

    public function selectByMap($map, $order=null) {
        return $this->currentModel
            ->where($map)
            ->order(isNullOrEmpty($order) ? $this->currentModel->getPk()." desc" : $order)
            ->select();
    }

    /**
     * 根据数据新增某一实体类
     * @param $data
     * @return mixed
     */
    public function saveByData($data) {
        $result = $this->currentModel->isUpdate(false)->save($data);
        if(false === $result) {
            return $result;
        } else {
            return $this->currentModel;
        }
    }

    /**
     * 根据模型允许字段新增某一实体
     * @param bool $allowField
     * @param $data
     * @return mixed
     */
    public function saveByAllowField($data,$allowField=true) {
        $result = $this->currentModel->isUpdate(false)->allowField($allowField)->save($data);
        if(false === $result) {
            return $result;
        } else {
            return $this->currentModel;
        }
    }

    /**
     * 根据模型存储或更新
     * @param $model
     * @return mixed
     */
    public function saveByModel($model) {
        $result = $model->isUpdate(false)->save();
        if(false === $result) {
            return $result;
        } else {
            return $model;
        }
    }

    /**
     * 批量新增
     * @param $list
     * @return mixed
     */
    public function saveByList($list) {
        return $this->currentModel->saveAll($list,false);
    }

    /**
     * 根据主键和数据更新某一实体
     * @param $id
     * @param $data
     * @return mixed
     */
    public function updateByIdAndData($id,$data) {
        return $this->currentModel->isUpdate(true)->save($data,[$this->currentModel->getPk() => $id]);
    }

    /**
     * 根据条件和数据更新某一实体
     * @param $map
     * @param $data
     * @return mixed
     */
    public function updateByMapAndData($map,$data) {
        return $this->currentModel->isUpdate(true)->save($data,$map);
    }

    /**
     * 根据模型允许字段和主键更新某一实体
     * @param bool $allowField
     * @param $data
     * @param $id
     * @return mixed
     */
    public function updateByAllowFieldAndId($data,$id,$allowField=true) {
        return $this->currentModel->isUpdate(true)->allowField($allowField)->save($data,[$this->currentModel->getPk() => $id]);
    }

    /**
     * 批量更新
     * @param $list
     * @return mixed
     */
    public function updateByList($list) {
        return $this->currentModel->saveAll($list,true);
    }

    /**
     * 根据主键ID删除记录
     * @param $id
     */
    public function deleteById($id) {
       return $this->currentModel->destroy($id);
    }

    /**
     * 根据条件删除记录
     * @param $map
     */
    public function deleteByMap($map) {
        return $this->currentModel->destroy($map);
    }

    /**
     * 根据条件查询数量
     * @param $map
     */
    public function countByMap($map) {
        return $this->currentModel->where($map)->count();
    }

    /**
     * 分页查询列表
     * @param $requestMap
     * @param string $field
     * @param bool $extraCondition
     * @param null $alias
     * @param null $order
     * @return mixed
     */
    public function paginateList($requestMap,$field="*",$extraCondition=false,$alias=null,$order=null) {
        if(!$extraCondition) {
            return $this->currentModel->field($field)
                ->where(getMapFromRequest($requestMap["condition"]))
                ->order(isNullOrEmpty($order)
                    ? $this->currentModel->getPk()." desc" : $order)
                ->paginate(Config::get("paginate.list_rows"),
                    false,["query"=>$requestMap["page"]]);
        } else {
            if(!isNullOrEmpty($alias)) {
                return $this->currentModel->alias($alias)->field($field)->where(getMapFromRequest($requestMap["condition"]))->order(isNullOrEmpty($order) ? $this->currentModel->getPk()." desc" : $order);
            } else {
                return $this->currentModel->field($field)->where(getMapFromRequest($requestMap["condition"]))->order(isNullOrEmpty($order) ? $this->currentModel->getPk()." desc" : $order);
            }
        }
    }

}

