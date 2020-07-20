<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-07-20
 * Time: 10:48
 */

namespace app\admin\service;

use app\common\model\ForumPostModel;

class ForumPostService extends Base
{
    public function __construct()
    {
        parent::__construct();
        $this->currentModel = new ForumPostModel();
    }
}