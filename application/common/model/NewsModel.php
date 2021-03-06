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

    public function getReadSystemCount($userUuid)
    {
        return $this
            ->where("user_uuid", $userUuid)
            ->where("type", NewsTypeEnum::SYSTEM_ALL)
            ->count();
    }

    public function allUnreadNewsByUser($userUuid)
    {
        return $this
            ->where("user_uuid", $userUuid)
            ->where("type", NewsTypeEnum::SYSTEM)
            ->where("is_read", NewsIsReadEnum::NO)
            ->order("id", "desc")
            ->select()->toArray();
    }

    public function allSystemNewsByUser($userUuid)
    {
        return $this
            ->where("user_uuid", $userUuid)
            ->where("type", NewsTypeEnum::SYSTEM_ALL)
            ->order("id", "desc")
            ->select()->toArray();
    }

    public function addNews($userUuid, $content, $targetPage = "", array $pageParams = [], $targetPageType = 0)
    {
        $data = [
            "uuid" => getRandomString(),
            "user_uuid" => $userUuid,
            "content" => $content,
            "target_page" => $targetPage,
            "target_page_type" => $targetPageType,
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