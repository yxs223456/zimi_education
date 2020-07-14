<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-07-13
 * Time: 16:53
 */

namespace app\api\service\v1;

use app\api\service\Base;
use app\api\service\UserService;
use app\common\AppException;
use app\common\enum\ForumTopicIsHotEnum;
use app\common\enum\PostIsRecommendEnum;
use app\common\model\ForumPostModel;
use app\common\model\ForumPostReplyModel;
use app\common\model\ForumPostReplyUpvoteModel;
use app\common\model\ForumPostUpvoteModel;
use app\common\model\ForumTopicModel;
use app\common\model\UserBaseModel;
use think\Db;

class ForumService extends Base
{
    /**
     * 全部话题
     */
    public function topic()
    {
        $forumTopicModel = new ForumTopicModel();
        $topic = $forumTopicModel->select();

        $returnData = [
            "hot_topic" => [],
            "total_topic" => [],
        ];
        foreach ($topic as $item) {
            if ($item["is_hot"] == ForumTopicIsHotEnum::YES) {
                $returnData["hot_topic"][] = [
                    "uuid" => $item["uuid"],
                    "topic" => $item["topic"],
                    "post_num" => $item["post_num"],
                ];
            }
            $returnData["total_topic"][] = [
                "uuid" => $item["uuid"],
                "topic" => $item["topic"],
                "post_num" => $item["post_num"],
            ];
        }

        return $returnData;
    }

    /**
     * 发布帖子
     * @param $user
     * @param $topicUuid
     * @param $content
     * @return array
     * @throws \Throwable
     */
    public function publishPost($user, $topicUuid, $content)
    {
        $forumPostModel = new ForumPostModel();
        Db::startTrans();
        try {

            //添加帖子
            $uuid = "fp" . date("ymd") . getRandomString(16);
            $postInfo = [
                "uuid" => $uuid,
                "user_uuid" => $user["uuid"],
                "t_uuid" => $topicUuid,
                "content" => $content,
            ];
            $forumPostModel->save($postInfo);

            //增加话题帖子数量
            Db::name("forum_topic")
                ->where("uuid", $topicUuid)
                ->inc("post_num", 1)
                ->update([
                    "update_time" => time()
                ]);

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }

        return [
            "uuid" => $uuid,
        ];
    }

    /**
     * 评论帖子
     * @param $user
     * @param $postUuid
     * @param $content
     * @return array
     * @throws \Throwable
     */
    public function replyPost($user, $postUuid, $content)
    {
        $forumPostReplyModel = new ForumPostReplyModel();
        Db::startTrans();
        try {

            //发表评论
            $uuid = "rfp" . date("ymd") . getRandomString(16);
            $postInfo = [
                "uuid" => $uuid,
                "user_uuid" => $user["uuid"],
                "p_uuid" => $postUuid,
                "content" => $content,
            ];
            $forumPostReplyModel->save($postInfo);

            //增加评论数量
            Db::name("forum_post")
                ->where("uuid", $postUuid)
                ->inc("direct_reply_num", 1)
                ->inc("total_reply_num", 1)
                ->update([
                    "update_time" => time()
                ]);

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }


        return [
            "uuid" => $uuid,
        ];
    }

    /**
     * 点赞帖子
     * @param $user
     * @param $postUuid
     * @return \stdClass
     * @throws \Throwable
     */
    public function upvotePost($user, $postUuid)
    {
        $forumPostModel = new ForumPostModel();
        $forumPostUpvoteModel = new ForumPostUpvoteModel();

        Db::startTrans();
        try {
            $forum = $forumPostModel
                ->where("uuid", $postUuid)
                ->lock(true)
                ->find();
            if (empty($forum)) {
                throw AppException::factory(AppException::COM_PARAMS_ERR);
            }

            if ($forumPostUpvoteModel->findByPUuidAndUserUuid($postUuid, $user["uuid"])) {
                throw AppException::factory(AppException::FORUM_UPVOTE_ALREADY);
            }

            //纪录点赞纪录
            $upvoteInfo = [
                "user_uuid" => $user["uuid"],
                "p_uuid" => $postUuid,
            ];
            $forumPostUpvoteModel->save($upvoteInfo);

            //增加点赞次数
            $forum->upvote_num += 1;
            $forum->save();

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }

        return new \stdClass();
    }

    /**
     * 取消点赞帖子
     * @param $user
     * @param $postUuid
     * @return \stdClass
     * @throws \Throwable
     */
    public function cancelUpvotePost($user, $postUuid)
    {
        $forumPostModel = new ForumPostModel();
        $forumPostUpvoteModel = new ForumPostUpvoteModel();

        Db::startTrans();
        try {
            $forum = $forumPostModel
                ->where("uuid", $postUuid)
                ->lock(true)
                ->find();
            if (empty($forum)) {
                throw AppException::factory(AppException::COM_PARAMS_ERR);
            }

            $forumPostUpvote = $forumPostUpvoteModel->findByPUuidAndUserUuid($postUuid, $user["uuid"]);
            if (empty($forumPostUpvote)) {
                throw AppException::factory(AppException::COM_INVALID);
            }

            //删除点赞纪录
            $forumPostUpvote->delete();

            //减少点赞次数
            $forum->upvote_num -= 1;
            $forum->save();

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }

        return new \stdClass();
    }

    /**
     * 帖子详情
     * @param $userUuid
     * @param $postUuid
     * @return array
     * @throws AppException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function postInfo($userUuid, $postUuid)
    {
        $forumPost = Db::name("forum_post")->alias("fp")
            ->leftJoin("forum_topic ft", "fp.t_uuid=ft.uuid")
            ->where("fp.uuid", $postUuid)
            ->field("fp.*,ft.topic")
            ->find();
        if (empty($forumPost)) {
            throw AppException::factory(AppException::COM_INVALID);
        }

        $userModel = new UserBaseModel();
        $user = $userModel->findByUuid($userUuid);
        $userSelfMedals = json_decode($user["self_medals"], true);
        $userCurrentMedal = (new UserService())->getUserCurrentMedal($userSelfMedals);

        $returnData = [
            "user" => [
                "nickname" => $user["nickname"],
                "head_image_url" => getImageUrl($user["head_image_url"]),
                "medal" => $userCurrentMedal["medal_url"]??"",
            ],
            "post" => [
                "uuid" => $forumPost["uuid"],
                "content" => $forumPost["content"],
                "topic" => $forumPost["topic"],
                "publish_time" => date("m-d H:i", $forumPost["create_time"]),
                "reply_num" => $forumPost["direct_reply_num"],
                "upvote_num" => $forumPost["upvote_num"],
            ],
        ];

        return $returnData;
    }

    /**
     * 帖子评论列表
     * @param $userUuid
     * @param $postUuid
     * @param $pageNum
     * @param $pageSize
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function postReplyList($userUuid, $postUuid, $pageNum, $pageSize)
    {
        $replyList = Db::name("forum_post_reply")
            ->where("p_uuid", $postUuid)
            ->limit(($pageNum-1)*$pageSize, $pageSize)
            ->select();
        if (empty($replyList)) {
            return [];
        }

        //当前用户点赞情况
        $replyUuids = array_column($replyList, "uuid");
        $replyUpvote = Db::name("forum_post_reply_upvote_log")
            ->whereIn("r_uuid", $replyUuids)
            ->where("user_uuid", $userUuid)
            ->select();
        $replyUpvoteSign = array_column($replyUpvote, "r_uuid");

        //发表评论的用户信息
        $userUuids = array_column($replyList, "user_uuid");
        $users = Db::name("user_base")->whereIn("uuid", $userUuids)->select();
        $userInfo = [];
        foreach ($users as $user) {
            $userSelfMedals = json_decode($user["self_medals"], true);
            $userCurrentMedal = (new UserService())->getUserCurrentMedal($userSelfMedals);
            $userInfo[$user["uuid"]] = [
                "nickname" => $user["nickname"],
                "head_image_url" => getImageUrl($user["head_image_url"]),
                "medal" => $userCurrentMedal["medal_url"]??"",
            ];
        }


        $returnData = [];
        foreach ($replyList as $item) {
            $returnData[] = [
                "user" => $userInfo[$item["user_uuid"]]??[
                        "nickname" => "",
                        "head_image_url" => "",
                        "medal" => "",
                    ],
                "reply" => [
                    "uuid" => $item["uuid"],
                    "content" => $item["content"],
                    "reply_time" => date("m-d H:i", $item["create_time"]),
                    "upvote_num" => $item["upvote_num"],
                    "is_upvote" => (int) in_array($item["uuid"], $replyUpvoteSign),
                ],
            ];
        }

        return $returnData;
    }

    /**
     * 点赞评论
     * @param $user
     * @param $replyUuid
     * @return \stdClass
     * @throws \Throwable
     */
    public function upvoteReply($user, $replyUuid)
    {
        $forumPostReplyModel = new ForumPostReplyModel();
        $forumPostReplyUpvoteModel = new ForumPostReplyUpvoteModel();

        Db::startTrans();
        try {
            $reply = $forumPostReplyModel
                ->where("uuid", $replyUuid)
                ->lock(true)
                ->find();
            if (empty($reply)) {
                throw AppException::factory(AppException::COM_PARAMS_ERR);
            }

            if ($forumPostReplyUpvoteModel->findByRUuidAndUserUuid($replyUuid, $user["uuid"])) {
                throw AppException::factory(AppException::FORUM_UPVOTE_ALREADY);
            }

            //纪录点赞纪录
            $upvoteInfo = [
                "p_uuid" => $reply["p_uuid"],
                "user_uuid" => $user["uuid"],
                "r_uuid" => $replyUuid,
            ];
            $forumPostReplyUpvoteModel->save($upvoteInfo);

            //增加点赞次数
            $reply->upvote_num += 1;
            $reply->save();

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }

        return new \stdClass();
    }

    public function cancelUpvoteReply($user, $replyUuid)
    {
        $forumPostReplyModel = new ForumPostReplyModel();
        $forumPostReplyUpvoteModel = new ForumPostReplyUpvoteModel();

        Db::startTrans();
        try {
            $reply = $forumPostReplyModel
                ->where("uuid", $replyUuid)
                ->lock(true)
                ->find();
            if (empty($reply)) {
                throw AppException::factory(AppException::COM_PARAMS_ERR);
            }

            $forumPostReplyUpvote = $forumPostReplyUpvoteModel->findByRUuidAndUserUuid($replyUuid, $user["uuid"]);
            if (empty($forumPostReplyUpvote)) {
                throw AppException::factory(AppException::COM_INVALID);
            }

            //删除点赞纪录
            $forumPostReplyUpvote->delete();

            //增加点赞次数
            $reply->upvote_num -= 1;
            $reply->save();

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }

        return new \stdClass();
    }

    /**
     * 某话题下的帖子列表
     * @param $topicUuid
     * @param $pageNum
     * @param $pageSize
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function postListOnTopic($topicUuid, $pageNum, $pageSize)
    {
        $postList = Db::name("forum_post")->alias("fp")
            ->leftJoin("user_base u", "u.uuid=fp.user_uuid")
            ->where("fp.t_uuid", $topicUuid)
            ->field("fp.*,u.nickname,u.head_image_url,u.self_medals")
            ->order("fp.id desc")
            ->limit(($pageNum-1)*$pageSize, $pageSize)
            ->select();

        $userService = new UserService();
        $returnData = [];
        foreach ($postList as $item) {
            $userSelfMedals = json_decode($item["self_medals"], true);
            $userCurrentMedal = $userService->getUserCurrentMedal($userSelfMedals);
            $returnData[] = [
                "user" => [
                    "nickname" => $item["nickname"],
                    "head_image_url" => getImageUrl($item["head_image_url"]),
                    "medal" => $userCurrentMedal["medal_url"]??"",
                ],
                "post" => [
                    "uuid" => $item["uuid"],
                    "content" => $item["content"],
                    "publish_time" => date("m-d H:i", $item["create_time"]),
                    "reply_num" => $item["direct_reply_num"],
                    "upvote_num" => $item["upvote_num"],
                ],
            ];
        }

        return $returnData;
    }

    /**
     * 推荐帖子列表
     * @param $pageNum
     * @param $pageSize
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function recommendPostList($pageNum, $pageSize)
    {
        $postList = Db::name("forum_post")->alias("fp")
            ->leftJoin("user_base u", "u.uuid=fp.user_uuid")
            ->where("fp.is_recommend", PostIsRecommendEnum::YES)
            ->field("fp.*,u.nickname,u.head_image_url,u.self_medals")
            ->order("fp.id desc")
            ->limit(($pageNum-1)*$pageSize, $pageSize)
            ->select();

        $userService = new UserService();
        $returnData = [];
        foreach ($postList as $item) {
            $userSelfMedals = json_decode($item["self_medals"], true);
            $userCurrentMedal = $userService->getUserCurrentMedal($userSelfMedals);
            $returnData[] = [
                "user" => [
                    "nickname" => $item["nickname"],
                    "head_image_url" => getImageUrl($item["head_image_url"]),
                    "medal" => $userCurrentMedal["medal_url"]??"",
                ],
                "post" => [
                    "uuid" => $item["uuid"],
                    "content" => $item["content"],
                    "publish_time" => date("m-d H:i", $item["create_time"]),
                    "reply_num" => $item["direct_reply_num"],
                    "upvote_num" => $item["upvote_num"],
                ],
            ];
        }

        return $returnData;
    }

    /**
     * 帖子列表
     * @param $pageNum
     * @param $pageSize
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function postList($pageNum, $pageSize)
    {
        $postList = Db::name("forum_post")->alias("fp")
            ->leftJoin("user_base u", "u.uuid=fp.user_uuid")
            ->field("fp.*,u.nickname,u.head_image_url,u.self_medals")
            ->order("fp.id desc")
            ->limit(($pageNum-1)*$pageSize, $pageSize)
            ->select();

        $userService = new UserService();
        $returnData = [];
        foreach ($postList as $item) {
            $userSelfMedals = json_decode($item["self_medals"], true);
            $userCurrentMedal = $userService->getUserCurrentMedal($userSelfMedals);
            $returnData[] = [
                "user" => [
                    "nickname" => $item["nickname"],
                    "head_image_url" => getImageUrl($item["head_image_url"]),
                    "medal" => $userCurrentMedal["medal_url"]??"",
                ],
                "post" => [
                    "uuid" => $item["uuid"],
                    "content" => $item["content"],
                    "publish_time" => date("m-d H:i", $item["create_time"]),
                    "reply_num" => $item["direct_reply_num"],
                    "upvote_num" => $item["upvote_num"],
                ],
            ];
        }

        return $returnData;
    }
}