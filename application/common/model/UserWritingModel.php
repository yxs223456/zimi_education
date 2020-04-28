<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-17
 * Time: 14:58
 */

namespace app\common\model;

class UserWritingModel extends Base
{
    protected $table = 'user_writing';

    public function myWritingList($userUuid, $pageNum, $pageSize)
    {
        return $this->where("user_uuid", $userUuid)
            ->order("id", "desc")
            ->limit(($pageNum-1)*$pageSize, $pageSize)
            ->select()->toArray();
    }
}