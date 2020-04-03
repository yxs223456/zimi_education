<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-04-03
 * Time: 17:39
 */

namespace app\admin\service;

use app\common\model\UserBaseModel;

class UserBaseService extends Base
{

    public function __construct()
    {
        parent::__construct();
        $this->currentModel = new UserBaseModel();
    }
}