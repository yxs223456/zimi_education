<?php

namespace app\admin\validate;
use think\Validate;

class AuthGroupValidate extends Validate
{
    protected $rule = [
        "title|角色名" => 'require',
    ];

    protected $message  =   [

    ];

}