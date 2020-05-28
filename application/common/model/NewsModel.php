<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-05-11
 * Time: 10:31
 */
namespace app\common\model;

use app\common\enum\NewsIsReadEnum;
use app\common\enum\NewsTypeEnum;

class NewsModel extends Base
{
    protected $table = 'news';

    public function getUnreadCountByUser($userUuid)
    {
        return $this
            ->where("user_uuid", $userUuid)
            ->where("type", NewsTypeEnum::SYSTEM)
            ->where("is_read", NewsIsReadEnum::NO)
            ->count();
    }

    public function allUnreadNewsByUser($userUuid)
    {
        return $this
            ->where("user_uuid", $userUuid)
            ->where("type", NewsTypeEnum::SYSTEM)
            ->where("is_read", NewsIsReadEnum::NO)
            ->field("uuid,content,create_time")
            ->order("id", "desc")
            ->select()->toArray();
    }

    public function addNews($userUuid, $content, $targetPage = "", array $pageParams = [])
    {
        $data = [
            "uuid" => getRandomString(),
            "user_uuid" => $userUuid,
            "content" => $content,
            "target_page" => $targetPage,
            "page_params" => json_encode($pageParams, JSON_UNESCAPED_UNICODE),
            "is_read" => NewsIsReadEnum::NO,
            "create_time" => time(),
            "update_time" => time(),
        ];
        $this->insert($data);
    }

    public function unreadNewsCountInfo($userUuid)
    {
        $sql = "select type,is_read from news where user_uuid='$userUuid' and ((type = 1 and is_read = 0) or (type = 2))";
        return $this->query($sql);
    }

    public function getNewsByUserUuidAndActivityUuids($userUuid, array $activityUuids)
    {
        return $this->where("user_uuid", $userUuid)
            ->whereIn("activity_uuid", $activityUuids)
            ->select()
            ->toArray();
    }
}