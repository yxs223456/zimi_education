<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-04-03
 * Time: 17:36
 */

namespace app\admin\service;

use app\common\model\UserSynthesizeModel;

class UserSynthesizeService extends Base
{

    public function __construct()
    {
        parent::__construct();
        $this->currentModel = new UserSynthesizeModel();
    }
}