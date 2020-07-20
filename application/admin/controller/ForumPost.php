<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-07-20
 * Time: 10:40
 */

namespace app\admin\controller;


use app\common\enum\ForumPostIsRecommendEnum;
use think\Db;

class ForumPost extends Base
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

                case "is_recommend":
                    $whereSql .= " and is_recommend = $value";
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
        $list = $this->forumPostService->getListByCondition($condition);

        $this->assign('list', $list);

        $isRecommend = ForumPostIsRecommendEnum::getAllList();
        $this->assign("isRecommend", $isRecommend);

        return $this->fetch();
    }

    public function isRecommendNo()
    {
        $id = input("id");

        //跳转参数
        $page = input("page",1);
        $isRecommend = input('is_recommend', '');

        Db::name("forum_post")
            ->where("id", $id)
            ->update([
                "is_recommend" => ForumPostIsRecommendEnum::NO,
                "update_time" => time(),
            ]);
        $this->redirect("list?page=$page&is_hot=$isRecommend");
    }

    public function isRecommendYes()
    {
        $id = input("id");

        //跳转参数
        $page = input("page",1);
        $isRecommend = input('is_recommend', '');

        Db::name("forum_post")
            ->where("id", $id)
            ->update([
                "is_recommend" => ForumPostIsRecommendEnum::YES,
                "update_time" => time(),
            ]);
        $this->redirect("list?page=$page&is_hot=$isRecommend");
    }
}