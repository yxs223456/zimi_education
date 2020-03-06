<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-02-25
 * Time: 19:45
 */

namespace app\admin\service;

use app\admin\model\TrueFalseQuestionModel;

class TrueFalseQuestionService extends Base
{

    public function __construct()
    {
        parent::__construct();
        $this->currentModel = new TrueFalseQuestionModel();
    }
}