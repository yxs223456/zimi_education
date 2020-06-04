<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-06-04
 * Time: 17:39
 */

namespace app\admin\controller;

use app\common\enum\PackageChannelEnum;

class PackageChannel extends Base
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
        $list = $this->packageChannelService->getListByCondition($condition);

        $this->assign('list', $list);

        foreach ($list as $item) {
            $item["channel"] = PackageChannelEnum::getEnumDescByValue($item["channel"]);
        }

        return $this->fetch();
    }

    public function add()
    {
        $allChannel = PackageChannelEnum::getAllList();
        $this->assign("allChannel", $allChannel);
        return $this->fetch();
    }

    public function addPost()
    {
        $version = trim(input("version"));
        $channel = input("channel");
        $packageLink = input("package_link");

        $this->packageChannelService->saveByWhereAndData(["channel"=>$channel], [
            "version" => $version,
            "channel" => $channel,
            "package_link" => $packageLink,
        ]);

        $this->success("操作成功",url("index"));
    }
}