<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-12
 * Time: 11:28
 */

namespace app\common\enum;

/**
 * 客户端是否绑定微信号
 * Class UserIsBindWeChatEnum
 * @package app\common\enum
 */
class UserIsBindWeChatEnum
{
    use EnumTrait;

    const YES = 1;
    const YES_DSC = "已绑定";

    const NO = 0;
    const NO_DESC = "未绑定";
}