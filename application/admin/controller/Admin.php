<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-04-29
 * Time: 16:34
 */


namespace app\admin\controller;

use think\Db;

class Admin extends Base
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

                case "username":
                    $whereSql .= " and a.username like '%$value%'";
                    break;

            }

            $pageMap[$key] = $value;
            $this->assign($key, $value);

        }
        $data["whereSql"] = $whereSql;
        $data["pageMap"] = $pageMap;

        return $data;

    }

    public function adminList()
    {
        $condition = $this->convertRequestToWhereSql();
        $list = $this->adminService->getListByCondition($condition);
        $this->assign('list', $list);

        return $this->fetch("adminList");
    }

    public function add()
    {
        $group = Db::name("auth_group")
            ->where("status",1)
            ->select();
        $this->assign("groups", $group);
        return $this->fetch();
    }

    public function addPost()
    {
        $username = trim(input("username"));
        $password = md5(trim(input("password")));
        $groupId = input("group_id");

        Db::name("admin")->insert([
            "username" => $username,
            "real_name" => $username,
            "password" => $password,
            "status" => 1,
            "group_id" => $groupId,
        ]);

        $this->success("添加成功",url("adminList"));
    }

    public function edit()
    {
        $id = input("id");
        $group = Db::name("auth_group")
            ->where("status",1)
            ->select();
        $this->assign("groups", $group);
        $info = $this->adminService->findById($id);
        $this->assign("info", $info);
        return $this->fetch();
    }

    public function editPost()
    {
        $id = input("id");

        $username = trim(input("username"));
        $password = md5(trim(input("password")));
        $groupId = input("group_id");
        $this->adminService->updateByIdAndData($id, [
            "username" => $username,
            "real_name" => $username,
            "password" => $password,
            "group_id" => $groupId,
        ]);
        $this->success("修改成功",url("adminList"));
    }
}