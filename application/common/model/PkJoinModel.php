<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-24
 * Time: 15:25
 */

namespace app\common\model;

class PkJoinModel extends Base
{
    protected $table = 'pk_join';

    public function findByUserAndPk($userUuid, $pkUuid)
    {
        return $this->where("pk_uuid", $pkUuid)->where("user_uuid", $userUuid)->find();
    }

    public function getListUserInfoByPkUuids(array $pkUuids)
    {
        return $this->alias("pj")
            ->leftJoin("user_base u", "pj.user_uuid=u.uuid")
            ->whereIn("pj.pk_uuid", $pkUuids)
            ->field("pj.pk_uuid,u.uuid,u.nickname,u.head_image_url")
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
}