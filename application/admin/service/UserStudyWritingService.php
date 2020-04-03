<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-04-03
 * Time: 11:02
 */

namespace app\admin\service;

use app\common\model\UserStudyWritingModel;

class UserStudyWritingService extends Base
{

    public function __construct()
    {
        parent::__construct();
        $this->currentModel = new UserStudyWritingModel();
    }
}