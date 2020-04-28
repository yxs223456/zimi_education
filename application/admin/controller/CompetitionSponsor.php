<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-04-28
 * Time: 17:54
 */

namespace app\admin\controller;

use app\common\enum\PkStatusEnum;
use app\common\enum\PkTypeEnum;
use app\common\enum\UserCoinAddTypeEnum;
use app\common\enum\UserCoinLogTypeEnum;
use app\common\helper\Redis;
use think\Db;
use think\exception\PDOException;

class Pk extends Base
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

                case "sponsor":
                    $whereSql .= " and sponsor like '%$value%'";
                    break;

            }

            $pageMap[$key] = $value;
            $this->assign($key, $value);

        }
        $data["whereSql"] = $whereSql;
        $data["pageMap"] = $pageMap;

        return $data;

    }

    public function pkList()
    {
        $condition = $this->convertRequestToWhereSql();
        $list = $this->pkService->getListByCondition($condition);
        foreach ($list as $item) {
            $item["typeDesc"] = PkTypeEnum::getEnumDescByValue($item["type"]);
            $item["statusDesc"] = PkStatusEnum::getEnumDescByValue($item["status"]);
        }
        $this->assign('list', $list);

        $pkStatus = PkStatusEnum::getAllList();
        $this->assign("pkStatus", $pkStatus);

        return $this->fetch("pkList");
    }
}