<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-02-23
 * Time: 17:30
 */

namespace app\admin\service;

use app\admin\model\FillTheBlanksLibraryModel;

class FillTheBlanksService extends Base
{

    public function __construct()
    {
        parent::__construct();
        $this->currentModel = new FillTheBlanksLibraryModel();
    }
}