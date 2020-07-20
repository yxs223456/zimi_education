<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-07-13
 * Time: 17:01
 */

namespace app\common\enum;

/**
 * 论坛帖子是否被推荐
 * Class ForumTopicIsHotEnum
 * @package app\common\enum
 */
class ForumPostIsRecommendEnum
{
    use EnumTrait;

    const YES = 1;
    const YES_DSC = "是";

    const NO = 0;
    const NO_DESC = "否";
}