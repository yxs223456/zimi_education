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
}