<?php

namespace app\admin\controller;

use app\admin\service\FillTheBlanksService;
use app\admin\service\SingleChoiceService;
use app\admin\service\Admin as adminService;
use app\admin\service\AuthGroup as authGroupService;
use app\admin\service\AuthGroupAccess as authGroupAccessService;
use app\admin\service\AuthRule as authRuleService;
use app\admin\service\WritingLibraryService;
use think\Controller;

class Base extends Controller {

    protected $adminService;
    protected $authGroupService;
    protected $authGroupAccessService;
    protected $authRuleService;
    protected $fillTheBlanksService;
    protected $singleChoiceService;
    protected $writingLibraryService;

    /**
     * 依赖注入
     * Base constructor.
     * @param adminService $adminService
     * @param authGroupService $authGroupService
     * @param authGroupAccessService $authGroupAccessService
     * @param authRuleService $authRuleService
     * @param FillTheBlanksService $fillTheBlanksService
     * @param SingleChoiceService $singleChoiceService
     * @param WritingLibraryService $writingLibraryService
     */
    public function __construct( AdminService $adminService, AuthGroupService $authGroupService,
                                AuthGroupAccessService $authGroupAccessService, AuthRuleService $authRuleService,
                                 FillTheBlanksService $fillTheBlanksService, SingleChoiceService $singleChoiceService,
                                 WritingLibraryService $writingLibraryService){

        parent::__construct();

        $this->adminService = $adminService;
        $this->authGroupService = $authGroupService;
        $this->authGroupAccessService = $authGroupAccessService;
        $this->authRuleService = $authRuleService;
        $this->fillTheBlanksService = $fillTheBlanksService;
        $this->singleChoiceService = $singleChoiceService;
        $this->writingLibraryService = $writingLibraryService;
    }
}