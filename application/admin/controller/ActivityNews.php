<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-05-26
 * Time: 15:29
 */

namespace app\admin\controller;

use app\common\enum\ActivityNewsIsPushAlreadyEnum;
use app\common\enum\ActivityNewsIsPushEnum;
use app\common\enum\ActivityNewsTargetPageTypeEnum;
use app\common\helper\Redis;
use think\Db;

class ActivityNews extends Base
{
    public function convertRequestToWhereSql()
    {
        $whereSql = " 1=1 ";
        $pageMap = [];

        $params = input("param.");

        foreach ($params as $key => $value) {

            if ($value == "-999"
                || isNullOrEmpty($value))
                continue;

            switch ($key) {
                case "name":
                    $whereSql .= " and name like '%$value%'";
                    break;
            }

            $pageMap[$key] = $value;
            $this->assign($key, $value);

        }
        $data["whereSql"] = $whereSql;
        $data["pageMap"] = $pageMap;

        return $data;
    }

    public function index()
    {
        $condition = $this->convertRequestToWhereSql();
        $list = $this->activityNewsService->getListByCondition($condition);
        foreach ($list as $item) {
            $item["target_page_type"] = ActivityNewsTargetPageTypeEnum::getEnumDescByValue($item["target_page_type"]);
            $item["is_push"] = ActivityNewsIsPushEnum::getEnumDescByValue($item["is_push"]);
        }
        $this->assign('list', $list);


        return $this->fetch();
    }

    public function add()
    {
        return $this->fetch();
    }

    public function addPost()
    {
        $content = trim(input("content"));
        $targetPage = trim(input("target_page"));
        $h5Title = input("h5_title");

        $newsInfo = [
            "uuid" => getRandomString(),
            "content" => $content,
            "target_page_type" => ActivityNewsTargetPageTypeEnum::H5,
            "target_page" => $targetPage,
            "page_params" => json_encode(["title"=>$h5Title], JSON_UNESCAPED_UNICODE),
            "is_push" => ActivityNewsIsPushEnum::YES,
            "is_push_already" => ActivityNewsIsPushAlreadyEnum::YES,
        ];
        Db::name("activity_news")->insert($newsInfo);

        $redis = Redis::factory();
        createBroadcastPushTask("你有一份新手手册请注意查收！",
            "送你一份读书伴手礼",
            ActivityNewsTargetPageTypeEnum::H5,
            ["title"=>$h5Title,"url"=>$targetPage],
            $redis);


        $this->success("添加成功",url("index"));
    }
}