<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-04-16
 * Time: 10:02
 */

namespace app\admin\controller;

use app\common\enum\PkStatusEnum;
use app\common\enum\PkTypeEnum;
use think\Db;

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

                case "is_comment":
                    $whereSql .= " and is_comment = $value";
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

    public function check()
    {
        $uuid = input('param.uuid');
        $info = $this->pkService->findByMap(["uuid"=>$uuid]);
        $this->assign("info", $info);

        return $this->fetch();
    }

    public function doCheck()
    {
        $uuid = input("uuid");
        $status = input("status");
        $auditFailReason = input("audit_fail_reason");

        if (empty($uuid)) {
            $this->error('参数错误');
        }
        if (!in_array($status,[PkStatusEnum::WAIT_JOIN, PkStatusEnum::AUDIT_FAIL])) {
            $this->error('参数错误');
        }
        if ($status == PkStatusEnum::AUDIT_FAIL && $auditFailReason == "") {
            $this->error('请输入审核不通过原因');
        }
        Db::startTrans();
        try {
            $pk = $this->pkService->findByMap(["uuid"=>$uuid]);
            if ($pk == null) {
                $this->error('数据不存在');
            }




            Db::commit();
            $this->success("审核成功",url("pkList"));
        } catch (\Throwable $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }


    }
}