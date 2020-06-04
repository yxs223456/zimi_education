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

    public function saveByWhereAndData($where, $data)
    {
        $packageCount = $this->currentModel->where($where)->find();
        if (!$packageCount) {
            $this->currentModel->save($data);
        } else {
            $this->currentModel->where($where)->update(array_merge($data, ["update_time"=>time()]));
        }

    }
}