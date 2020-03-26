<?php
/**
 * Created by PhpStorm.
 * User: yangxs
 * Date: 2018/9/18
 * Time: 16:56
 */

namespace app\common\model;

use think\Model;

class Base extends Model
{
    protected $autoWriteTimestamp = true;

    public function findById($id)
    {
        return $this->where(static::getPk(), $id)->find();
    }

    public function findByUuid($uuid)
    {
        return $this->where("uuid", $uuid)->find();
    }

    public function saveByData($data)
    {
        $returnData = $data;
        $returnData[static::getPk()] = $this->insertGetId($data);
        return $returnData;
    }

    public function updateByIdAndData($id,$data)
    {
        return $this->isUpdate(true)->save($data, [static::getPk() => $id]);
    }

    public function findByUserIdAndSource($userId, $source)
    {
        return $this->where('userId', $userId)->where('source', $source)->find();
    }
}