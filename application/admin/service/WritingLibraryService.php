<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-02-25
 * Time: 15:04
 */

namespace app\admin\service;

use app\admin\model\WritingLibraryModel;

class WritingLibraryService extends Base
{

    public function __construct()
    {
        parent::__construct();
        $this->currentModel = new WritingLibraryModel();
    }
}