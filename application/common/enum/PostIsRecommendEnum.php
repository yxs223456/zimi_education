<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-07-14
 * Time: 09:47
 */

namespace app\common\enum;

/**
 * 帖子是否被推荐
 * Class PostIsRecommendEnum
 * @package app\common\enum
 */
class PostIsRecommendEnum
{
    use EnumTrait;

    const YES = 1;
    const YES_DSC = "是";

    const NO = 0;
    const NO_DESC = "否";
}