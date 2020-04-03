<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-04-02
 * Time: 21:20
 */

namespace app\common\enum;

/**
 * 练习作文作品，老师是否品论
 * Class UserStudyWritingIsCommentEnum
 * @package app\common\enum
 */
class UserStudyWritingIsCommentEnum
{
    use EnumTrait;

    const YES = 1;
    const YES_DSC = "已评论";

    const NO = 0;
    const NO_DESC = "未评论";
}