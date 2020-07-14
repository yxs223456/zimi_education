<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-04-28
 * Time: 17:56
 */

namespace app\common\model;

class ForumPostUpvoteModel extends Base
{
    protected $table = "forum_post_upvote_log";

    public function findByPUuidAndUserUuid($pUuid, $userUuid)
    {
        return $this->where("p_uuid", $pUuid)
            ->where("user_uuid", $userUuid)
            ->find();
    }
}