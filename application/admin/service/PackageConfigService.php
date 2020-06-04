<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-06-04
 * Time: 17:29
 */

namespace app\admin\service;

use app\common\model\PackageConfigModel;

class PackageConfigService extends Base
{
    public function __construct()
    {
        parent::__construct();
        $this->currentModel = new PackageConfigModel();
    }

    public function getListByCondition($condition)
    {

        $list = $this->currentModel
            ->where($condition['whereSql'])
            ->order('version desc')
            ->paginate(\config("paginate.list_rows"), false,
                ["query" => $condition['pageMap']]);

        return $list;
    }
}