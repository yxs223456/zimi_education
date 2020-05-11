<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-05-11
 * Time: 10:31
 */
namespace app\common\model;

use app\common\enum\NewsIsReadEnum;

class NewsModel extends Base
{
    protected $table = 'news';

    public function getUnreadCountByUser($userUuid)
    {
        return $this
            ->where("user_uuid", $userUuid)
            ->where("is_read", NewsIsReadEnum::NO)
            ->count();
    }

    public function allUnreadNewsByUser($userUuid)
    {
        return $this
            ->where("user_uuid", $userUuid)
            ->where("is_read", NewsIsReadEnum::NO)
            ->field("uuid,content,create_time")
            ->order("id", "desc")
            ->select()->toArray();
    }
}