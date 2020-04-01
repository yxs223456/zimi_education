<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-12
 * Time: 22:18
 */

namespace app\common\enum;

/**
 * 用户pk值增减类型
 * Class UserPkCoinLogTypeEnum
 * @package app\common\enum
 */
class UserPkCoinLogTypeEnum
{
    use EnumTrait;

    const ADD = 1;
    const ADD_DSC = "增加";

    const REDUCE = 2;
    const REDUCE_DESC = "减少";
}