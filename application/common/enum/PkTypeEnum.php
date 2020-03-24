<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-23
 * Time: 17:34
 */

namespace app\common\enum;

/**
 * pk难度
 * Class PkTypeEnum
 * @package app\common\enum
 */
class PkTypeEnum
{
    use EnumTrait;

    const NOVICE = 1;
    const NOVICE_DSC = "新手PK";

    const SIMPLE = 2;
    const SIMPLE_DESC = "简单模式";

    const DIFFICULTY = 3;
    const DIFFICULTY_DESC = "困难模式";

    const GOD = 4;
    const GOD_DESC = "超神模式";
}