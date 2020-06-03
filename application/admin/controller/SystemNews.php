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
use app\common\enum\NewsIsPushAlreadyEnum;
use app\common\enum\NewsIsPushEnum;
use app\common\enum\NewsTargetPageTypeEnum;
use app\common\helper\Redis;
use think\Db;

class SystemNews extends Base
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
        $list = $this->systemNewsService->getListByCondition($condition);
        foreach ($list as $item) {
            $item["target_page_type"] = NewsTargetPageTypeEnum::getEnumDescByValue($item["target_page_type"]);
            $item["is_push"] = NewsIsPushEnum::getEnumDescByValue($item["is_push"]);
            $item["push_time"] = $item["push_time"] ? date("Y-m-d H:i", $item["push_time"]) : "";
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
        $pushTitle = input("push_title");
        $pushTime = strtotime(input("push_time"));

        $newsInfo = [
            "uuid" => getRandomString(),
            "content" => $content,
            "push_title" => $pushTitle,
            "push_time" => $pushTime,
            "is_push" => NewsIsPushEnum::YES,
            "is_push_already" => NewsIsPushAlreadyEnum::NO,
            "create_time" => time(),
            "update_time" => time(),

        ];
        Db::name("system_news")->insert($newsInfo);

        $this->success("添加成功",url("index"));
    }
}