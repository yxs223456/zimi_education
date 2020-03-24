<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-24
 * Time: 11:21
 */

namespace app\common\enum;

/**
 * 用户书币消耗方式
 * Class UserCoinReduceTypeEnum
 * @package app\common\enum
 */
class UserCoinReduceTypeEnum
{
    use EnumTrait;

    const INIT_PK = 1;
    const INIT_PK_DSC = "发起pk";

    const JOIN_PK = 2;
    const JOIN_PK_DESC = "参与pk";
}