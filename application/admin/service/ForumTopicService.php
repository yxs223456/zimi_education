<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-07-20
 * Time: 10:48
 */

namespace app\admin\service;

use app\common\model\ForumTopicModel;

class ForumTopicService extends Base
{
    public function __construct()
    {
        parent::__construct();
        $this->currentModel = new ForumTopicModel();
    }
}