<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-24
 * Time: 16:28
 */

namespace app\common\model;

use app\common\enum\PkStatusEnum;

class PkModel extends Base
{
    protected $table = 'pk';

    public function getListByType($pkType, $pkStatus, $pageNum, $pageSize)
    {
        $data = $this->where("type", $pkType);
        if ($pkStatus != 0) {
            $data->where("status", $pkStatus);
        }
        return $data->whereIn("status", [
                PkStatusEnum::WAIT_JOIN,
                PkStatusEnum::UNDERWAY,
                PkStatusEnum::FINISH,
            ])->order("id", "desc")
            ->limit(($pageNum - 1) * $pageSize, $pageSize)
            ->select();
    }
}