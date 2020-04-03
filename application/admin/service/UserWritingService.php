<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-31
 * Time: 18:10
 */

namespace app\admin\service;

use app\common\model\UserWritingModel;

class UserWritingService extends Base
{

    public function __construct()
    {
        parent::__construct();
        $this->currentModel = new UserWritingModel();
    }

    public function getListByCondition($condition)
    {
        $list = $this->currentModel
            ->where($condition['whereSql'])
            ->order(['is_comment'=>'asc', 'id'=>'asc'])
            ->paginate(\config("paginate.list_rows"), false,
                ["query" => $condition['pageMap']]);

        return $list;
    }
}