<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-05-26
 * Time: 15:39
 */

namespace app\common\enum;

/**
 * 消息是否推送
 * Class NewsIsPushEnum
 * @package app\common\enum
 */
class NewsIsPushEnum
{
    use EnumTrait;

    const YES = 1;
    const YES_DSC = "需要推送";

    const NO = 0;
    const NO_DESC = "不需要推送";
}