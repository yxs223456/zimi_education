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
use app\common\enum\DbIsDeleteEnum;
use app\common\enum\ForumTopicIsHotEnum;
use app\common\enum\NewsTargetPageTypeEnum;
use app\common\enum\NewsTypeEnum;
use app\common\enum\PostIsRecommendEnum;
use app\common\helper\Redis;
use app\common\model\ForumPostModel;
use app\common\model\ForumPostReplyModel;
use app\common\model\ForumPostReplyUpvoteModel;
use app\common\model\ForumPostUpvoteModel;
use app\common\model\ForumTopicModel;
use app\common\model\NewsModel;
use think\Db;

class ForumService extends Base
{
    /**
     * 全部话题
     */
    public function topic()
    {
        $forumTopicModel = new ForumTopicModel();
        $topic = $forumTopicModel
            ->where("is_delete", DbIsDeleteEnum::NO)
            ->select();

        $returnData = [
            "hot_topic" => [],
            "total_topic" => [],
        ];
        foreach ($topic as $item) {
            if ($item["is_hot"] == ForumTopicIsHotEnum::YES) {
                $returnData["hot_topic"][] = [
                    "uuid" => $item["uuid"],
                    "topic" => $item["topic"],
                    "image_url" => getImageUrl($item["image_url"]),
                    "post_num" => $item["post_num"],
                ];
            } else {
                $returnData["total_topic"][] = [
                    "uuid" => $item["uuid"],
                    "topic" => $item["topic"],
                    "image_url" => getImageUrl($item["image_url"]),
                    "post_num" => $item["post_num"],
                ];
            }
        }

        return $returnData;
    }

    /**
     * 发布帖子
     * @param $user
     * @param $topicUuid
     * @param $content
     * @param $photos
     * @return array
     * @throws \Throwable
     */
    public function publishPost($user, $topicUuid, $content, array $photos)
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
                "photos" => json_encode($photos),
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

        //纪录、发送消息
        $post = (new ForumPostModel())->findByUuid($postUuid);
        $newsModel = new NewsModel();
        $newsContent = getNickname($user["nickname"]) . "评论了你发布的吐槽";
        $targetPage = json_encode([
            "android" => "com.zimi.study.module.complain_detail.ComplainDetailActivity",
            "ios" => "Roast",
        ]);
        $pageParams = [
            "android" => [
                "complain_id" => $postUuid,
            ],
            "ios" => [
                "uuid" => $postUuid,
            ],
        ];
        $newsModel->addNews($post["user_uuid"], $newsContent, $targetPage, $pageParams, NewsTargetPageTypeEnum::APP);
        $postUser = Db::name("user_base")->where("uuid", $post["user_uuid"])->find();
        $title = "有小伙伴评论你发布的吐槽了";
        createUnicastPushTask(
            $postUser["os"],
            $postUser["uuid"],
            $newsContent,
            json_decode($targetPage, true),
            $pageParams,
            Redis::factory(),
            $title,
            NewsTargetPageTypeEnum::APP
        );

        //用户勋章
        $userSelfMedals = json_decode($user["self_medals"], true);
        $userCurrentMedal = (new UserService())->getUserCurrentMedal($userSelfMedals);
        return [
            "user" => [
                "nickname" => getNickname($user["nickname"]),
                "head_image_url" => getHeadImageUrl($user["head_image_url"]),
                "medal" => $userCurrentMedal["medal_url"]??"",
            ],
            "reply" => [
                "uuid" => $uuid,
                "content" => $content,
                "reply_time" => date("m-d H:i", strtotime($forumPostReplyModel->create_time)),
                "upvote_num" => 0,
                "is_upvote" => 0,
                "is_my_reply" => 1,
            ],
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
     * @param $user
     * @param $postUuid
     * @return array
     * @throws AppException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function postInfo($user, $postUuid)
    {
        //帖子信息
        $forumPost = Db::name("forum_post")->alias("fp")
            ->leftJoin("forum_topic ft", "fp.t_uuid=ft.uuid")
            ->leftJoin("user_base u", "u.uuid=fp.user_uuid")
            ->where("fp.uuid", $postUuid)
            ->field("fp.*,ft.topic,u.nickname,u.head_image_url,u.self_medals")
            ->find();
        if (empty($forumPost)) {
            throw AppException::factory(AppException::COM_INVALID);
        }

        if ($forumPost["is_delete"] == DbIsDeleteEnum::YES) {
            throw AppException::factory(AppException::FORUM_POST_DELETE);
        }

        //帖子图片
        $photos = json_decode($forumPost["photos"], true);
        foreach ($photos as $key=>$photo) {
            $photos[$key] = getImageUrl($photo);
        }

        //作者勋章
        $userSelfMedals = json_decode($forumPost["self_medals"], true);
        $userCurrentMedal = (new UserService())->getUserCurrentMedal($userSelfMedals);

        //当前用户是否点赞
        $isUpvote = (int) (bool) Db::name("forum_post_upvote_log")
            ->where("p_uuid", $postUuid)
            ->where("user_uuid", $user["uuid"])
            ->count();

        $returnData = [
            "user" => [
                "nickname" => getNickname($forumPost["nickname"]),
                "head_image_url" => getHeadImageUrl($forumPost["head_image_url"]),
                "medal" => $userCurrentMedal["medal_url"]??"",
            ],
            "post" => [
                "uuid" => $forumPost["uuid"],
                "content" => $forumPost["content"],
                "photos" => $photos,
                "topic" => $forumPost["topic"],
                "t_uuid" => $forumPost["t_uuid"],
                "publish_time" => date("m-d H:i", $forumPost["create_time"]),
                "reply_num" => $forumPost["direct_reply_num"],
                "upvote_num" => $forumPost["upvote_num"],
                "is_upvote" => $isUpvote,
                "is_my_post" => (int) ($user["uuid"] == $forumPost["user_uuid"]),
            ],
        ];

        return $returnData;
    }

    /**
     * 帖子评论列表(按热度排序)
     * @param $user
     * @param $postUuid
     * @param $pageNum
     * @param $pageSize
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function postReplyListByHot($user, $postUuid, $pageNum, $pageSize)
    {
        //评论列表
        $replyList = Db::name("forum_post_reply")
            ->where("p_uuid", $postUuid)
            ->where("is_delete", DbIsDeleteEnum::NO)
            ->order("upvote_num", "desc")
            ->limit(($pageNum-1)*$pageSize, $pageSize)
            ->select();
        if (empty($replyList)) {
            return [];
        }

        //当前用户点赞情况
        $replyUuids = array_column($replyList, "uuid");
        $replyUpvote = Db::name("forum_post_reply_upvote_log")
            ->whereIn("r_uuid", $replyUuids)
            ->where("user_uuid", $user["uuid"])
            ->select();
        $replyUpvoteSign = array_column($replyUpvote, "r_uuid");

        //发表评论的用户信息
        $userUuids = array_column($replyList, "user_uuid");
        $users = Db::name("user_base")->whereIn("uuid", $userUuids)->select();
        $userInfo = [];
        foreach ($users as $replyUser) {
            $userSelfMedals = json_decode($replyUser["self_medals"], true);
            $userCurrentMedal = (new UserService())->getUserCurrentMedal($userSelfMedals);
            $userInfo[$replyUser["uuid"]] = [
                "nickname" => getNickname($replyUser["nickname"]),
                "head_image_url" => getHeadImageUrl($replyUser["head_image_url"]),
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
                    "is_my_reply" => (int) ($user["uuid"] == $item["user_uuid"])
                ],
            ];
        }

        return $returnData;
    }

    public function postReplyListByTime($user, $postUuid, $lastReplyUuid, $pageSize)
    {
        $query = Db::name("forum_post_reply")
            ->where("p_uuid", $postUuid)
            ->where("is_delete", DbIsDeleteEnum::NO);
        //上次查询最后一条id值
        if ($lastReplyUuid) {
            $lastReply = Db::name("forum_post_reply")
                ->where("uuid", $lastReplyUuid)
                ->find();
            if (empty($lastReply)) {
                throw AppException::factory(AppException::COM_INVALID);
            }
            $query->where("id", "<", $lastReply["id"]);
        }

        //评论列表
        $replyList = $query
            ->order("id", "desc")
            ->limit(0, $pageSize)
            ->select();
        if (empty($replyList)) {
            return [];
        }

        //当前用户点赞情况
        $replyUuids = array_column($replyList, "uuid");
        $replyUpvote = Db::name("forum_post_reply_upvote_log")
            ->whereIn("r_uuid", $replyUuids)
            ->where("user_uuid", $user["uuid"])
            ->select();
        $replyUpvoteSign = array_column($replyUpvote, "r_uuid");

        //发表评论的用户信息
        $userUuids = array_column($replyList, "user_uuid");
        $users = Db::name("user_base")->whereIn("uuid", $userUuids)->select();
        $userInfo = [];
        foreach ($users as $replyUser) {
            $userSelfMedals = json_decode($replyUser["self_medals"], true);
            $userCurrentMedal = (new UserService())->getUserCurrentMedal($userSelfMedals);
            $userInfo[$replyUser["uuid"]] = [
                "nickname" => getNickname($replyUser["nickname"]),
                "head_image_url" => getHeadImageUrl($replyUser["head_image_url"]),
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
                    "is_my_reply" => (int) ($user["uuid"] == $item["user_uuid"])
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

    /**
     * 取消点赞评论
     * @param $user
     * @param $replyUuid
     * @return \stdClass
     * @throws \Throwable
     */
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
     * @param $user
     * @param $topicUuid
     * @param $pageNum
     * @param $pageSize
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function postListOnTopic($user, $topicUuid, $pageNum, $pageSize)
    {
        $returnData = [];
        $postList = Db::name("forum_post")->alias("fp")
            ->leftJoin("forum_topic ft", "ft.uuid=fp.t_uuid")
            ->leftJoin("user_base u", "u.uuid=fp.user_uuid")
            ->where("fp.t_uuid", $topicUuid)
            ->where("fp.is_delete", DbIsDeleteEnum::NO)
            ->field("fp.*,ft.topic,u.nickname,u.head_image_url,u.self_medals")
            ->order("fp.id desc")
            ->limit(($pageNum-1)*$pageSize, $pageSize)
            ->select();

        if ($postList) {
            //当前用户点赞情况
            $postUuids = array_column($postList, "uuid");
            $upvoteData = Db::name("forum_post_upvote_log")->where("user_uuid", $user["uuid"])
                ->whereIn("p_uuid", $postUuids)
                ->column("p_uuid");

            $userService = new UserService();
            foreach ($postList as $item) {
                //作者勋章
                $userSelfMedals = json_decode($item["self_medals"], true);
                $userCurrentMedal = $userService->getUserCurrentMedal($userSelfMedals);

                //帖子图集
                $photos = json_decode($item["photos"], true);
                foreach ($photos as $key=>$photo) {
                    $photos[$key] = getImageUrl($photo);
                }

                $returnData[] = [
                    "user" => [
                        "nickname" => getNickname($item["nickname"]),
                        "head_image_url" => getHeadImageUrl($item["head_image_url"]),
                        "medal" => $userCurrentMedal["medal_url"]??"",
                    ],
                    "post" => [
                        "uuid" => $item["uuid"],
                        "content" => $item["content"],
                        "photos" => $photos,
                        "publish_time" => date("m-d H:i", $item["create_time"]),
                        "reply_num" => $item["direct_reply_num"],
                        "upvote_num" => $item["upvote_num"],
                        "is_upvote" => (int) in_array($item["uuid"], $upvoteData),
                        "topic" => $item["topic"],
                        "t_uuid" => $item["t_uuid"],
                        "is_my_post" => (int) ($user["uuid"] == $item["user_uuid"]),
                    ],
                ];
            }
        }

        return $returnData;
    }

    /**
     * 推荐帖子列表
     * @param $user
     * @param $pageNum
     * @param $pageSize
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function recommendPostList($user, $pageNum, $pageSize)
    {
        $returnData = [];
        $postList = Db::name("forum_post")->alias("fp")
            ->leftJoin("forum_topic ft", "ft.uuid=fp.t_uuid")
            ->leftJoin("user_base u", "u.uuid=fp.user_uuid")
            ->where("fp.is_recommend", PostIsRecommendEnum::YES)
            ->where("fp.is_delete", DbIsDeleteEnum::NO)
            ->field("fp.*,ft.topic,u.nickname,u.head_image_url,u.self_medals")
            ->order("fp.id desc")
            ->limit(($pageNum-1)*$pageSize, $pageSize)
            ->select();

        if ($postList) {
            //当前用户点赞情况
            $postUuids = array_column($postList, "uuid");
            $upvoteData = Db::name("forum_post_upvote_log")->where("user_uuid", $user["uuid"])
                ->whereIn("p_uuid", $postUuids)
                ->column("p_uuid");

            $userService = new UserService();
            foreach ($postList as $item) {
                //作者勋章
                $userSelfMedals = json_decode($item["self_medals"], true);
                $userCurrentMedal = $userService->getUserCurrentMedal($userSelfMedals);

                //帖子图集
                $photos = json_decode($item["photos"], true);
                foreach ($photos as $key=>$photo) {
                    $photos[$key] = getImageUrl($photo);
                }

                $returnData[] = [
                    "user" => [
                        "nickname" => getNickname($item["nickname"]),
                        "head_image_url" => getHeadImageUrl($item["head_image_url"]),
                        "medal" => $userCurrentMedal["medal_url"]??"",
                    ],
                    "post" => [
                        "uuid" => $item["uuid"],
                        "content" => $item["content"],
                        "photos" => $photos,
                        "publish_time" => date("m-d H:i", $item["create_time"]),
                        "reply_num" => $item["direct_reply_num"],
                        "upvote_num" => $item["upvote_num"],
                        "is_upvote" => (int) in_array($item["uuid"], $upvoteData),
                        "topic" => $item["topic"],
                        "t_uuid" => $item["t_uuid"],
                        "is_my_post" => (int) ($user["uuid"] == $item["user_uuid"]),
                    ],
                ];
            }
        }

        return $returnData;
    }

    /**
     * 帖子列表
     * @param $user
     * @param $pageNum
     * @param $pageSize
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function postList($user, $pageNum, $pageSize)
    {
        $returnData = [];
        $postList = Db::name("forum_post")->alias("fp")
            ->leftJoin("forum_topic ft", "ft.uuid=fp.t_uuid")
            ->leftJoin("user_base u", "u.uuid=fp.user_uuid")
            ->where("fp.is_delete", DbIsDeleteEnum::NO)
            ->field("fp.*,ft.topic,u.nickname,u.head_image_url,u.self_medals")
            ->order("fp.id desc")
            ->limit(($pageNum-1)*$pageSize, $pageSize)
            ->select();

        if ($postList) {
            //当前用户点赞情况
            $postUuids = array_column($postList, "uuid");
            $upvoteData = Db::name("forum_post_upvote_log")->where("user_uuid", $user["uuid"])
                ->whereIn("p_uuid", $postUuids)
                ->column("p_uuid");

            $userService = new UserService();
            foreach ($postList as $item) {
                //作者勋章
                $userSelfMedals = json_decode($item["self_medals"], true);
                $userCurrentMedal = $userService->getUserCurrentMedal($userSelfMedals);

                //帖子图集
                $photos = json_decode($item["photos"], true);
                foreach ($photos as $key=>$photo) {
                    $photos[$key] = getImageUrl($photo);
                }

                $returnData[] = [
                    "user" => [
                        "nickname" => getNickname($item["nickname"]),
                        "head_image_url" => getHeadImageUrl($item["head_image_url"]),
                        "medal" => $userCurrentMedal["medal_url"]??"",
                    ],
                    "post" => [
                        "uuid" => $item["uuid"],
                        "content" => $item["content"],
                        "photos" => $photos,
                        "publish_time" => date("m-d H:i", $item["create_time"]),
                        "reply_num" => $item["direct_reply_num"],
                        "upvote_num" => $item["upvote_num"],
                        "is_upvote" => (int) in_array($item["uuid"], $upvoteData),
                        "topic" => $item["topic"],
                        "t_uuid" => $item["t_uuid"],
                        "is_my_post" => (int) ($user["uuid"] == $item["user_uuid"]),
                    ],
                ];
            }
        }

        return $returnData;
    }

    /**
     * 我发布的帖子
     * @param $user
     * @param $pageNum
     * @param $pageSize
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function myPublishPostList($user, $pageNum, $pageSize)
    {
        $returnData = [];
        $postList = Db::name("forum_post")->alias("fp")
            ->leftJoin("forum_topic ft", "ft.uuid=fp.t_uuid")
            ->leftJoin("user_base u", "u.uuid=fp.user_uuid")
            ->where("fp.user_uuid", $user["uuid"])
            ->where("fp.is_delete", DbIsDeleteEnum::NO)
            ->field("fp.*,ft.topic,u.nickname,u.head_image_url,u.self_medals")
            ->order("fp.id desc")
            ->limit(($pageNum-1)*$pageSize, $pageSize)
            ->select();

        if ($postList) {
            //当前用户点赞情况
            $postUuids = array_column($postList, "uuid");
            $upvoteData = Db::name("forum_post_upvote_log")->where("user_uuid", $user["uuid"])
                ->whereIn("p_uuid", $postUuids)
                ->column("p_uuid");

            $userService = new UserService();
            foreach ($postList as $item) {
                //作者勋章
                $userSelfMedals = json_decode($item["self_medals"], true);
                $userCurrentMedal = $userService->getUserCurrentMedal($userSelfMedals);

                //帖子图集
                $photos = json_decode($item["photos"], true);
                foreach ($photos as $key=>$photo) {
                    $photos[$key] = getImageUrl($photo);
                }

                $returnData[] = [
                    "user" => [
                        "nickname" => getNickname($item["nickname"]),
                        "head_image_url" => getHeadImageUrl($item["head_image_url"]),
                        "medal" => $userCurrentMedal["medal_url"]??"",
                    ],
                    "post" => [
                        "uuid" => $item["uuid"],
                        "content" => $item["content"],
                        "photos" => $photos,
                        "publish_time" => date("m-d H:i", $item["create_time"]),
                        "reply_num" => $item["direct_reply_num"],
                        "upvote_num" => $item["upvote_num"],
                        "is_upvote" => (int) in_array($item["uuid"], $upvoteData),
                        "topic" => $item["topic"],
                        "t_uuid" => $item["t_uuid"],
                        "is_my_post" => (int) ($user["uuid"] == $item["user_uuid"]),
                    ],
                ];
            }
        }

        return $returnData;
    }

    public function myRelatedPostList($user, $pageNum, $pageSize)
    {
        $returnData = [];

        $postUuids = Db::name("forum_post_reply")->alias("fpr")
            ->leftJoin("forum_post fp", "fp.uuid=fpr.p_uuid")
            ->where("fpr.user_uuid", $user["uuid"])
            ->where("fp.is_delete", DbIsDeleteEnum::NO)
            ->where("fpr.is_delete", DbIsDeleteEnum::NO)
            ->order("fpr.id", "desc")
            ->column("fpr.p_uuid");
        $postUuids = array_unique($postUuids);
        $postUuids = array_slice($postUuids, ($pageNum-1)*$pageSize, $pageSize);

        if ($postUuids) {
            $postList2 = Db::name("forum_post")->alias("fp")
                ->leftJoin("forum_topic ft", "ft.uuid=fp.t_uuid")
                ->leftJoin("user_base u", "u.uuid=fp.user_uuid")
                ->whereIn("fp.uuid", $postUuids)
                ->field("fp.*,ft.topic,u.nickname,u.head_image_url,u.self_medals")
                ->order("fp.id desc")
                ->limit(($pageNum-1)*$pageSize, $pageSize)
                ->select();
            $postList2 = array_column($postList2, null, "uuid");
            $postList = [];
            foreach ($postUuids as $postUuid) {
                $postList[] = $postList2[$postUuid];
            }

            if ($postList) {
                //当前用户点赞情况
                $postUuids = array_column($postList, "uuid");
                $upvoteData = Db::name("forum_post_upvote_log")->where("user_uuid", $user["uuid"])
                    ->whereIn("p_uuid", $postUuids)
                    ->column("p_uuid");

                $userService = new UserService();
                foreach ($postList as $item) {
                    //作者勋章
                    $userSelfMedals = json_decode($item["self_medals"], true);
                    $userCurrentMedal = $userService->getUserCurrentMedal($userSelfMedals);

                    //帖子图集
                    $photos = json_decode($item["photos"], true);
                    foreach ($photos as $key=>$photo) {
                        $photos[$key] = getImageUrl($photo);
                    }

                    $returnData[] = [
                        "user" => [
                            "nickname" => getNickname($item["nickname"]),
                            "head_image_url" => getHeadImageUrl($item["head_image_url"]),
                            "medal" => $userCurrentMedal["medal_url"]??"",
                        ],
                        "post" => [
                            "uuid" => $item["uuid"],
                            "content" => $item["content"],
                            "photos" => $photos,
                            "publish_time" => date("m-d H:i", $item["create_time"]),
                            "reply_num" => $item["direct_reply_num"],
                            "upvote_num" => $item["upvote_num"],
                            "is_upvote" => (int) in_array($item["uuid"], $upvoteData),
                            "topic" => $item["topic"],
                            "t_uuid" => $item["t_uuid"],
                            "is_my_post" => (int) ($user["uuid"] == $item["user_uuid"]),
                        ],
                    ];
                }
            }
        }

        return $returnData;
    }

    /**
     * 删除帖子
     * @param $user
     * @param $postUuid
     * @return \stdClass
     * @throws \Throwable
     */
    public function delPost($user, $postUuid)
    {
        $postModel = new ForumPostModel();

        Db::startTrans();
        try {
            $post = $postModel->where("uuid", $postUuid)->lock(true)->find();
            if ($post) {
                if ($post["user_uuid"] != $user["uuid"]) {
                    throw AppException::factory(AppException::COM_INVALID);
                }
                if ($post->is_delete == DbIsDeleteEnum::NO) {

                    $post->is_delete = DbIsDeleteEnum::YES;
                    $post->save();

                    Db::name("forum_topic")
                        ->where("uuid", $post["t_uuid"])
                        ->setDec("post_num", 1);
                }
            }

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }

        return new \stdClass();
    }

    /**
     * 删除回复
     * @param $user
     * @param $replyUuid
     * @return \stdClass
     * @throws \Throwable
     */
    public function delReply($user, $replyUuid)
    {
        $replyModel = new ForumPostReplyModel();

        Db::startTrans();
        try {

            $reply = $replyModel->where("uuid", $replyUuid)->lock(true)->find();
            if ($reply) {
                if ($reply["user_uuid"] != $user["uuid"]) {
                    throw AppException::factory(AppException::COM_INVALID);
                }
                if ($reply->is_delete == DbIsDeleteEnum::NO) {

                    $reply->is_delete = DbIsDeleteEnum::YES;
                    $reply->save();

                    Db::name("forum_post")
                        ->where("uuid", $reply["p_uuid"])
                        ->dec("direct_reply_num", 1)
                        ->dec("total_reply_num", 1)
                        ->update();
                }
            }

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }

        return new \stdClass();
    }
}