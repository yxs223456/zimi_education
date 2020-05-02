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
        $list = $this->currentModel->alias("uw")
            ->leftJoin("user_base u", 'uw.user_uuid=u.uuid')
            ->where($condition['whereSql'])
            ->field("uw.*,u.invite_code")
            ->order(['uw.is_comment'=>'asc', 'uw.id'=>'asc'])
            ->paginate(\config("paginate.list_rows"), false,
                ["query" => $condition['pageMap']]);

        return $list;
    }
}