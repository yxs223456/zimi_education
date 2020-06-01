<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-17
 * Time: 14:58
 */

namespace app\common\model;

use app\common\enum\UserWritingSourceTypeEnum;

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

    public function studyWritingList($userUuid, $pageNum, $pageSize)
    {
        return $this->where("user_uuid", $userUuid)
            ->where("source_type", UserWritingSourceTypeEnum::STUDY)
            ->order("is_comment", "asc")
            ->order("id", "desc")
            ->limit(($pageNum-1)*$pageSize, $pageSize)
            ->select()->toArray();
    }

    public function synthesizeWritingList($userUuid, $pageNum, $pageSize)
    {
        return $this->where("user_uuid", $userUuid)
            ->where("source_type", UserWritingSourceTypeEnum::SYNTHESIZE)
            ->order("is_comment", "asc")
            ->order("id", "desc")
            ->limit(($pageNum-1)*$pageSize, $pageSize)
            ->select()->toArray();
    }
}