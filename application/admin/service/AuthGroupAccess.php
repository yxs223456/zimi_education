<?php

namespace app\admin\service;

use app\admin\service\Base;
use app\admin\model\AuthGroupAccess as AuthGroupAccessModel;

class AuthGroupAccess extends Base {

     public function __construct() {
         parent::__construct();
         $this->currentModel = new AuthGroupAccessModel();
     }

}