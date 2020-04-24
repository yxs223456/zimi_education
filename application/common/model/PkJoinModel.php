<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-24
 * Time: 15:25
 */

namespace app\common\model;

use app\common\enum\PkIsInitiatorEnum;
use app\common\enum\PkStatusEnum;

class PkJoinModel extends Base
{
    protected $table = 'pk_join';

    public function findByUserAndPk($userUuid, $pkUuid)
    {
        return $this->where("pk_uuid", $pkUuid)->where("user_uuid", $userUuid)->find();
    }

    public function getJoinCountByPkUuid($pkUuid)
    {
        return $this->where("pk_uuid", $pkUuid)->count();
    }

    public function getListUserInfoByPkUuids(array $pkUuids)
    {
        return $this->alias("pj")
            ->leftJoin("user_base u", "pj.user_uuid=u.uuid")
            ->whereIn("pj.pk_uuid", $pkUuids)
            ->field("pj.answers,pj.pk_uuid,pj.user_uuid,u.nickname,u.head_image_url")
            ->order("pj.id", "asc")
            ->select();
    }

    public function getListUserInfoByPkUuid($pkUuid)
    {
        return $this->alias("pj")
            ->leftJoin("user_base u", "pj.user_uuid=u.uuid")
            ->where("pj.pk_uuid", $pkUuid)
            ->field("u.nickname,u.head_image_url,pj.*")
            ->order("pj.id", "asc")
            ->select();
    }

    public function getJoinInfoByPkUuid($pkUuid)
    {
        return $this->where("pk_uuid", $pkUuid)->select();
    }

    public function pkReportCard($userUuid, $pageNum, $pageSize)
    {
        return $this->alias("pkj")
            ->leftJoin("pk", "pk.uuid=pkj.pk_uuid")
            ->leftJoin("user_base u", "u.uuid=pk.initiator_uuid")
            ->where("pkj.user_uuid", $userUuid)
            ->where("pk.status", PkStatusEnum::FINISH)
            ->field("pk.uuid,pk.name,u.nickname,u.head_image_url,pkj.create_time,pkj.rank")
            ->order("pkj.id", "desc")
            ->limit(($pageNum - 1) * $pageSize, $pageSize)
            ->select()
            ->toArray();

    }

    public function myJointPk($userUuid, $pageNum, $pageSize)
    {
        return $this->alias("pkj")
            ->leftJoin("pk", "pk.uuid=pkj.pk_uuid")
            ->where("pkj.user_uuid", $userUuid)
            ->where("pkj.is_initiator", PkIsInitiatorEnum::NO)
            ->field("pk.*")
            ->order("pkj.id", "desc")
            ->limit(($pageNum - 1) * $pageSize, $pageSize)
            ->select()
            ->toArray();
    }
}