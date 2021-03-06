<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-07-13
 * Time: 16:50
 */

namespace app\api\controller;


use app\api\service\v1\ForumService;
use app\common\AppException;

class Forum extends Base
{
    protected $beforeActionList = [
        'checkAuth' => [
            'except' => 'topic',
        ],
    ];

    /**
     * 全部话题
     */
    public function topic()
    {
        $service = new ForumService();
        return $this->jsonResponse($service->topic());
    }

    /**
     * 发布帖子
     */
    public function publishPost()
    {
        $topicUuid = input("t_uuid");
        $content = input("content");
        $photos = input("photos", []);

        if (empty($topicUuid) || empty($content)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }
        if (!is_array($photos)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $service = new ForumService();
        return $this->jsonResponse($service->publishPost($user, $topicUuid, $content, $photos));
    }

    /**
     * 帖子详情
     */
    public function postInfo()
    {
        $postUuid = input("p_uuid");

        if (empty($postUuid)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $service = new ForumService();
        return $this->jsonResponse($service->postInfo($user, $postUuid));
    }

    /**
     * 评论帖子
     */
    public function replyPost()
    {
        $postUuid = input("p_uuid");
        $content = input("content");

        if (empty($postUuid) || empty($content)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $service = new ForumService();
        return $this->jsonResponse($service->replyPost($user, $postUuid, $content));
    }

    /**
     * 点赞帖子
     */
    public function upvotePost()
    {
        $postUuid = input("p_uuid");

        if (empty($postUuid)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $service = new ForumService();
        return $this->jsonResponse($service->upvotePost($user, $postUuid));
    }

    /**
     * 取消点赞帖子
     */
    public function cancelUpvotePost()
    {
        $postUuid = input("p_uuid");

        if (empty($postUuid)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $service = new ForumService();
        return $this->jsonResponse($service->cancelUpvotePost($user, $postUuid));
    }

    /**
     * 帖子评论列表(按热度排序)
     */
    public function postReplyListByHot()
    {
        $postUuid = input("p_uuid");
        $pageNum = input("page_num");
        $pageSize = input("page_size");

        if (!checkInt($pageNum, false) || !checkInt($pageSize, false)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }
        if (empty($postUuid)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $service = new ForumService();
        return $this->jsonResponse($service->postReplyListByHot($user, $postUuid, $pageNum, $pageSize));
    }

    /**
     * 帖子评论列表(按热度排序)
     */
    public function postReplyListByTime()
    {
        $postUuid = input("p_uuid");
        $lastReplyUuid = input("last_r_uuid", "");
        $pageSize = input("page_size");

        if (!checkInt($pageSize, false)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }
        if (empty($postUuid)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $service = new ForumService();
        return $this->jsonResponse($service->postReplyListByTime($user, $postUuid, $lastReplyUuid, $pageSize));
    }

    /**
     * 点赞评论
     */
    public function upvoteReply()
    {
        $replyUuid = input("r_uuid");

        if (empty($replyUuid)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $service = new ForumService();
        return $this->jsonResponse($service->upvoteReply($user, $replyUuid));
    }

    /**
     * 取消点赞评论
     */
    public function cancelUpvoteReply()
    {
        $replyUuid = input("r_uuid");

        if (empty($replyUuid)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $service = new ForumService();
        return $this->jsonResponse($service->cancelUpvoteReply($user, $replyUuid));
    }

    /**
     * 某话题下的帖子列表
     */
    public function postListOnTopic()
    {
        $topicUuid = input("t_uuid");
        $pageNum = input("page_num");
        $pageSize = input("page_size");

        if (!checkInt($pageNum, false) || !checkInt($pageSize, false)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }
        if (empty($topicUuid)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $service = new ForumService();
        return $this->jsonResponse($service->postListOnTopic($user, $topicUuid, $pageNum, $pageSize));
    }

    /**
     * 推荐帖子列表
     */
    public function recommendPostList()
    {
        $pageNum = input("page_num");
        $pageSize = input("page_size");

        if (!checkInt($pageNum, false) || !checkInt($pageSize, false)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $service = new ForumService();
        return $this->jsonResponse($service->recommendPostList($user, $pageNum, $pageSize));
    }

    /**
     * 帖子列表
     */
    public function postList()
    {
        $pageNum = input("page_num");
        $pageSize = input("page_size");

        if (!checkInt($pageNum, false) || !checkInt($pageSize, false)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $service = new ForumService();
        return $this->jsonResponse($service->postList($user, $pageNum, $pageSize));
    }

    /**
     * 我发布的帖子列表
     */
    public function myPublishPostList()
    {
        $pageNum = input("page_num");
        $pageSize = input("page_size");

        if (!checkInt($pageNum, false) || !checkInt($pageSize, false)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $service = new ForumService();
        return $this->jsonResponse($service->myPublishPostList($user, $pageNum, $pageSize));
    }

    /**
     * 我参与的帖子列表
     */
    public function myRelatedPostList()
    {
        $pageNum = input("page_num");
        $pageSize = input("page_size");

        if (!checkInt($pageNum, false) || !checkInt($pageSize, false)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $service = new ForumService();
        return $this->jsonResponse($service->myRelatedPostList($user, $pageNum, $pageSize));
    }

    /**
     * 删除帖子
     */
    public function delPost()
    {
        $postUuid = input("p_uuid");

        if (empty($postUuid)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $service = new ForumService();
        return $this->jsonResponse($service->delPost($user, $postUuid));
    }

    /**
     * 删除评论
     */
    public function delReply()
    {
        $replayUuid = input("r_uuid");

        if (empty($replayUuid)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $service = new ForumService();
        return $this->jsonResponse($service->delReply($user, $replayUuid));
    }
}