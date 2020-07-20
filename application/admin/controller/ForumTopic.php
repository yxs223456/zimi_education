<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-07-20
 * Time: 10:40
 */

namespace app\admin\controller;


use app\common\enum\ForumTopicIsHotEnum;
use think\Db;

class ForumTopic extends Base
{
    public function convertRequestToWhereSql()
    {

        $whereSql = " 1=1 ";
        $pageMap = [];

        $params = input("param.");

        foreach ($params as $key => $value) {

            if ($value == "-999"
                || $value === null ||
                $value === "")
                continue;

            switch ($key) {

                case "is_hot":
                    $whereSql .= " and is_hot = $value";
                    break;
                case "topic":
                    $whereSql .= " and topic like '%$value%'";
                    break;

            }

            $pageMap[$key] = $value;
            $this->assign($key, $value);

        }
        $data["whereSql"] = $whereSql;
        $data["pageMap"] = $pageMap;

        return $data;

    }

    public function list()
    {
        $condition = $this->convertRequestToWhereSql();
        $list = $this->forumTopicService->getListByCondition($condition);

        $this->assign('list', $list);

        $isHost = ForumTopicIsHotEnum::getAllList();
        $this->assign("isHost", $isHost);

        return $this->fetch();
    }

    public function add()
    {
        return $this->fetch();
    }

    public function addPost()
    {
        $topic = input('topic', '');
        $imageUrl = input('image_url', '');
        $isHot = input("is_hot", 0);

        Db::name("forum_topic")
            ->insert([
                "uuid" => getRandomString(10),
                "topic" => $topic,
                "image_url" => $imageUrl,
                "is_hot" => $isHot,
            ]);


        $this->success("添加成功",url("list"));
    }

    public function edit()
    {
        $id = input("id");
        $info = Db::name("forum_topic")
            ->where("id", $id)
            ->find();
        $this->assign('info', $info);
        return $this->fetch();
    }

    public function editPost()
    {
        $id = input("id");
        $topic = input('topic', '');
        $imageUrl= input('image_url', '');

        Db::name("forum_topic")
            ->where("id", $id)
            ->update([
                "topic" => $topic,
                "image_url" => $imageUrl,
            ]);

        $this->success("编辑成功",url("list"));
    }

    public function isHotNo()
    {
        $id = input("id");

        //跳转参数
        $page = input("page",1);
        $topic = input('topic', '');
        $isHot= input('is_hot', '');

        Db::name("forum_topic")
            ->where("id", $id)
            ->update([
                "is_hot" => ForumTopicIsHotEnum::NO,
                "update_time" => time(),
            ]);
        $this->redirect("list?page=$page&topic=$topic&is_hot=$isHot");
    }

    public function isHotYes()
    {
        $id = input("id");

        //跳转参数
        $page = input("page",1);
        $topic = input('topic', '');
        $isHot= input('is_hot', '');

        Db::name("forum_topic")
            ->where("id", $id)
            ->update([
                "is_hot" => ForumTopicIsHotEnum::YES,
                "update_time" => time(),
            ]);
        $this->redirect("list?page=$page&topic=$topic&is_hot=$isHot");
    }
}