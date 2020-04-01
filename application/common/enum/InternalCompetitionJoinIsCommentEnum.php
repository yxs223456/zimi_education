<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-31
 * Time: 21:28
 */

namespace app\common\enum;

/**
 * 内部大赛作品，老师是否品论
 * Class InternalCompetitionJoinIsCommentEnum
 * @package app\common\enum
 */
class InternalCompetitionJoinIsCommentEnum
{
    use EnumTrait;

    const YES = 1;
    const YES_DSC = "已评论";

    const NO = 0;
    const NO_DESC = "未评论";
}