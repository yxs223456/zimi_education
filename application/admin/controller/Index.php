<?php

namespace app\admin\controller;

use think\Request;

class Index extends Common {

    public function index(Request $request) {

        return $this->fetch('/index');
    }

    /**
     * 后台首页
     * @return mixed
     */
    public function indexPage() {

        $info = array(
            'web_server' => $_SERVER['SERVER_SOFTWARE'],
            'onload'     => ini_get('upload_max_filesize'),
            'think_v'    => "5.1",
            'phpversion' => phpversion(),
        );

        $this->assign('info',$info);
        return $this->fetch('index');

    }

    /**
     * 清除缓存
     */
    public function clear() {
        if (delete_dir_file(CACHE_PATH) || delete_dir_file(TEMP_PATH)) {
            $this->result("",1,"清除缓存成功");
        } else {
            $this->result("",0,"清除缓存失败");
        }
    }

}
