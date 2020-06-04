<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-05-25
 * Time: 15:09
 */

namespace app\common\model;

class SystemNewsModel extends Base
{
    protected $table = 'system_news';

    public function getAllCount()
    {
        return $this->where("push_time", "<=", time())->count();
    }

    public function getAll()
    {
        return $this->where("push_time", "<=", time())
            ->order("id", "desc")
            ->select()->toArray();
    }
}