<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-09
 * Time: 17:18
 */

namespace app\common\enum;

/**
 * 数据库数据是否被删除
 * Class DbDataIsDeleteEnum
 * @package app\common\enum
 */
class DbIsDeleteEnum
{
    use EnumTrait;

    const YES = 1;
    const YES_DSC = "被删除";

    const NO = 0;
    const NO_DESC = "有效";
}