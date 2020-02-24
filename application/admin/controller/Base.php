<?php

namespace app\admin\controller;

use app\admin\service\FillTheBlanksService;
use app\admin\service\SingleChoiceService;
use think\Controller;

use app\admin\service\Admin as adminService;
use app\admin\service\AuthGroup as authGroupService;
use app\admin\service\AuthGroupAccess as authGroupAccessService;
use app\admin\service\AuthRule as authRuleService;

class Base extends Controller {

    protected $adminService;
    protected $authGroupService;
    protected $authGroupAccessService;
    protected $authRuleService;
    protected $fillTheBlanksService;
    protected $singleChoiceService;

    /**
     * 依赖注入
     * Base constructor.
     * @param adminService $adminService
     * @param authGroupService $authGroupService
     * @param authGroupAccessService $authGroupAccessService
     * @param authRuleService $authRuleService
     * @param FillTheBlanksService $fillTheBlanksService
     * @param SingleChoiceService $singleChoiceService
     */
    public function __construct( AdminService $adminService, AuthGroupService $authGroupService,
                                AuthGroupAccessService $authGroupAccessService, AuthRuleService $authRuleService,
                                 FillTheBlanksService $fillTheBlanksService, SingleChoiceService $singleChoiceService){

        parent::__construct();

        $this->adminService = $adminService;
        $this->authGroupService = $authGroupService;
        $this->authGroupAccessService = $authGroupAccessService;
        $this->authRuleService = $authRuleService;
        $this->fillTheBlanksService = $fillTheBlanksService;
        $this->singleChoiceService = $singleChoiceService;
    }
}