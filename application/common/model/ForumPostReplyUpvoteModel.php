<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-04-28
 * Time: 17:56
 */

namespace app\common\model;

class ForumPostReplyUpvoteModel extends Base
{
    protected $table = "forum_post_reply_upvote_log";

    public function findByRUuidAndUserUuid($rUuid, $userUuid)
    {
        return $this->where("r_uuid", $rUuid)
            ->where("user_uuid", $userUuid)
            ->find();
    }
}