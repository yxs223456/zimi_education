<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-12
 * Time: 22:18
 */

namespace app\common\enum;

/**
 * 用户书币增减类型
 * Class UserCoinLogTypeEnum
 * @package app\common\enum
 */
class UserCoinLogTypeEnum
{
    use EnumTrait;

    const ADD = 1;
    const ADD_DSC = "增加";

    const REDUCE = 2;
    const REDUCE_DESC = "减少";
}