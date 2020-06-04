<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-05-26
 * Time: 15:40
 */

namespace app\common\enum;

/**
 * 需要推送的消息是否已推送
 * Class NewsIsPushAlreadyEnum
 * @package app\common\enum
 */
class NewsIsPushAlreadyEnum
{
    use EnumTrait;

    const YES = 1;
    const YES_DSC = "已推送";

    const NO = 0;
    const NO_DESC = "未推送";
}