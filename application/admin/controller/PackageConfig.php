<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-06-04
 * Time: 17:39
 */

namespace app\admin\controller;

use app\common\enum\DbIsDeleteEnum;
use think\Db;

class PackageConfig extends Base
{
    public function convertRequestToWhereSql()
    {
        $whereSql = " is_delete = 0 ";
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
        $list = $this->packageConfigService->getListByCondition($condition);

        $this->assign('list', $list);

        return $this->fetch();
    }

    public function add()
    {
        return $this->fetch();
    }

    public function addPost()
    {
        $os = trim(input("os"));
        $version = trim(input("version"));
        $changeLog = input("change_log");
        $packageLink= input("package_link");
        $forced = input("forced");

        Db::name("package_config")->insert([
            "os" => $os,
            "version" => $version,
            "forced" => $forced,
            "package_link" => $packageLink,
            "change_log" => $changeLog,
            "create_time" => time(),
            "update_time" => time(),
        ]);

        $this->success("添加成功",url("index"));
    }

    public function delete()
    {
        $id = input("id");
        Db::name("package_config")->where("id", $id)->update([
            "is_delete" => DbIsDeleteEnum::YES,
        ]);

        $this->success("操作成功",url("index"));
    }
}