<?php

namespace app\admin\validate;

use think\Validate;

class AdminValidate extends Validate
{
    protected $rule = [
        "username|用户名" => 'require',
        "group_id" => 'require',
        "real_name|真实姓名" => 'require',
    ];

    protected $message  =   [

    ];

}