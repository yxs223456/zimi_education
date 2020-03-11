<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-04
 * Time: 15:41
 */

namespace app\common\enum;

trait EnumTrait
{
    static public function getAllList()
    {
        $r = new \ReflectionClass(self::class);

        $constantsList = $r->getConstants();

        $list = [];
        $info = [];
        $index = 1;

        foreach ($constantsList as $key=>$value) {
            if ($index % 2 == 1) {
                $info["value"] = $value;
            } else {
                $info["desc"] = $value;
                array_push($list, $info);
            }

            $index++;
        }

        return $list;
    }

    static public function getEnumDescByValue($value)
    {
        $list = self::getAllList();
        $desc = "";

        foreach ($list as $item) {
            if ($item["value"] == $value) {
                $desc = $item["desc"];
                break;
            }
        }

        return $desc;
    }
}