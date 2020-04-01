<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-12
 * Time: 22:18
 */

namespace app\common\enum;

/**
 * 用户才情值增减类型
 * Class UserTalentCoinLogTypeEnum
 * @package app\common\enum
 */
class UserTalentCoinLogTypeEnum
{
    use EnumTrait;

    const ADD = 1;
    const ADD_DSC = "增加";

    const REDUCE = 2;
    const REDUCE_DESC = "减少";
}