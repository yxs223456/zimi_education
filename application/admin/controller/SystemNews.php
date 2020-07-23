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
        $targetPageType = (int) input("target_page");
        if ($targetPageType == NewsTargetPageTypeEnum::APP) {
            $androidPage = input("android_page");
            $androidParams = json_decode(input("android_params"), true);
            $iosPage = input("ios_page");
            $iosParams = json_decode(input("ios_params"), true);
            if ($androidPage == "") {
                $this->error('请填写Android页面链接');
            } else if (!is_array($androidParams)) {
                $this->error('Android页面参数格式错误');
            } else if ($iosPage == "") {
                $this->error('请填写IOS页面链接');
            } else if (!is_array($iosParams)) {
                $this->error('IOS页面参数格式错误');
            }
            $targetPage = json_encode(["android"=>$androidPage,"ios"=>$iosPage]);
            $targetParams = json_encode(["android"=>$androidParams,"ios"=>$iosParams]);
        } else if ($targetPageType == NewsTargetPageTypeEnum::H5) {
            $h5Title = input("h5_title");
            $h5Url = input("h5_url");
            if ($h5Title == "") {
                $this->error('h5页面标题不能为空');
            } else if ($h5Url == "") {
                $this->error('h5链接不能为空');
            }
            $targetPage = $h5Url;
            $targetParams = json_encode(["title"=>$h5Title]);
        } else {
            $targetPageType = NewsTargetPageTypeEnum::NONE;
            $targetPage = "";
            $targetParams = "";
        }

        $newsInfo = [
            "uuid" => getRandomString(),
            "content" => $content,
            "push_title" => $pushTitle,
            "push_time" => $pushTime,
            "is_push" => NewsIsPushEnum::YES,
            "is_push_already" => NewsIsPushAlreadyEnum::NO,
            "target_page_type" => $targetPageType,
            "target_page" => $targetPage,
            "page_params" => $targetParams,
            "create_time" => time(),
            "update_time" => time(),

        ];
        Db::name("system_news")->insert($newsInfo);

        $this->success("添加成功",url("index"));
    }
}