<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-15
 * Time: 15:33
 */

namespace app\common\model;

class PackageConfigModel extends Base
{
    protected $table = 'package_config';

    public function findByOsAndVersion($os, $version)
    {
        return $this->where("os", $os)
            ->where("version", $version)
            ->find();
    }

    //对应操作系统下的最新版本
    public function findCurrentPackageByOs($os)
    {
        return $this->where("os", $os)
            ->order("version desc")
            ->find();
    }

    //对应操作系统所有版本，按版本从高到低
    public function getAllPackageOrderByVersion($os)
    {
        return $this->where("os", $os)
            ->order("version desc")
            ->select();
    }
}