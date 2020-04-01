<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-04-01
 * Time: 11:01
 */


namespace app\admin\service;

use app\common\model\InternalCompetitionModel;

class InternalCompetitionService extends Base
{

    public function __construct()
    {
        parent::__construct();
        $this->currentModel = new InternalCompetitionModel();
    }

}