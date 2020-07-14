<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-07-13
 * Time: 17:01
 */

namespace app\common\enum;

/**
 * 论坛话题是否是热门话题
 * Class ForumTopicIsHotEnum
 * @package app\common\enum
 */
class ForumTopicIsHotEnum
{
    use EnumTrait;

    const YES = 1;
    const YES_DSC = "是";

    const NO = 0;
    const NO_DESC = "否";
}