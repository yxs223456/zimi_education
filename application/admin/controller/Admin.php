<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-04-29
 * Time: 16:34
 */


namespace app\admin\controller;

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

                case "a.status":
                    $whereSql .= " and status = $value";
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
}