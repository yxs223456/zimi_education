<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-04-16
 * Time: 10:00
 */

namespace app\admin\service;

use app\common\model\PkModel;

class PkService extends Base
{

    public function __construct()
    {
        parent::__construct();
        $this->currentModel = new PkModel();
    }
}