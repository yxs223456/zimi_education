<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-04-07
 * Time: 18:35
 */

namespace app\api\controller;

use think\Controller;

class Index extends Controller
{
    public function index()
    {
        $this->redirect(config("web.self_domain") . "/appapple-app-site-association");
    }
}