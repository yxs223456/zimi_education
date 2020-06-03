<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-05-26
 * Time: 15:27
 */

namespace app\admin\service;

use app\common\model\SystemNewsModel;

class SystemNewsService extends Base
{

    public function __construct()
    {
        parent::__construct();
        $this->currentModel = new SystemNewsModel();
    }
}