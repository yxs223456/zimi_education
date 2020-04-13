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
    const JOIN_INTERNAL_COMPETITION_DESC = "参与DE内部大赛";

    const INTERNAL_COMPETITION_WIN = 2;
    const INTERNAL_COMPETITION_WIN_DESC = "DE内部大赛取得名次";
}