<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-17
 * Time: 15:11
 */

namespace app\common\enum;

/**
 * 用户作文来源
 * Class UserWritingSourceTypeEnum
 * @package app\common\enum
 */
class UserWritingSourceTypeEnum
{
    use EnumTrait;

    const STUDY = 1;
    const STUDY_DSC = "专项测试";

    const SYNTHESIZE = 2;
    const SYNTHESIZE_DSC = "综合测试";
}