<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-05-25
 * Time: 15:09
 */

namespace app\common\model;

class ActivityNewsModel extends Base
{
    protected $table = 'activity_news';

    public function getAllCount()
    {
        return $this->count();
    }

    public function getList($pageNum, $pageSize)
    {
        return $this->order("id", "desc")
            ->limit(($pageNum-1)*$pageSize, $pageSize)
            ->select()->toArray();
    }
}