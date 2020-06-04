<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-05-02
 * Time: 16:16
 */
namespace app\common\enum;

/**
 * 包渠道
 * Class PackageChannelEnum
 * @package app\common\enum
 */
class PackageChannelEnum
{

    use EnumTrait;

    const WEB = 1;
    const WEB_DESC = "官网";

    const DS = 2;
    const DS_DESC = "大山";
}