<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-06-04
 * Time: 17:30
 */

namespace app\admin\service;

use app\common\model\PackageChannelModel;

class PackageChannelService extends Base
{

    public function __construct()
    {
        parent::__construct();
        $this->currentModel = new PackageChannelModel();
    }

    public function saveByData($where, $data)
    {
        $this->currentModel->isUpdate(true, $where)->save($data);
    }
}