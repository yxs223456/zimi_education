<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-05-26
 * Time: 10:56
 */


namespace app\common\enum;

/**
 * 消息目标跳转页面类型
 * Class NewsTargetPageTypeEnum
 * @package app\common\enum
 */
class NewsTargetPageTypeEnum
{
    use EnumTrait;

    const NONE = 0;
    const NONE_DESC = "不跳转";

    const APP = 1;
    const APP_DSC = "app页面";

    const H5 = 2;
    const H5_DESC = "h5页面";
}