<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-05-11
 * Time: 10:30
 */

namespace app\common\enum;

/**
 * 消息是否已读
 * Class NewsIsReadEnum
 * @package app\common\enum
 */
class NewsIsReadEnum
{
    use EnumTrait;

    const YES = 1;
    const YES_DSC = "已读";

    const NO = 0;
    const NO_DESC = "未读";
}