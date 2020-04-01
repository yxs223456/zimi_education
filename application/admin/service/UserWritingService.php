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
}