<?php

namespace app\admin\validate;

use think\Validate;

class LevelValidate extends Validate {

    protected $rule = [
        "min_profit" => 'require|float',
        "direct_min_profit" => 'require|float',
        "direct_total_number" => 'require|integer',
        "direct_valid_number" => 'require|integer',
        "indirect_total_number" => 'require|integer',
        "indirect_valid_number" => 'require|integer',
        "all_second_level_number" => 'require|integer',
    ];

    protected $message  =   [

    ];
}