<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-12
 * Time: 13:43
 */

namespace app\common\enum;

/**
 * 用户Pk值来源
 * Class UserPkCoinAddTypeEnum
 * @package app\common\enum
 */
class UserPkCoinAddTypeEnum
{
    use EnumTrait;

    const JOIN_INTERNAL_COMPETITION = 1;
    const JOIN_INTERNAL_COMPETITION_DSC = "参与DE内部大赛";

    const INTERNAL_COMPETITION_WIN = 1;
    const INTERNAL_COMPETITION_WIN_DSC = "DE内部大赛取得名次";
}