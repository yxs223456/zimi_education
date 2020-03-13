<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-13
 * Time: 15:33
 */

namespace app\common\enum;

/**
 * 客户端操作系统
 * Class OperatingSystemEnum
 * @package app\common\enum
 */
class OperatingSystemEnum
{
    use EnumTrait;

    const IOS = "ios";
    const IOS_DSC = "IOS";

    const ANDROID = "android";
    const ANDROID_DESC = "安卓";
}