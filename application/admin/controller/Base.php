<?php

namespace app\admin\controller;

use app\admin\service\FillTheBlanksService;
use app\admin\service\InternalCompetitionJoinService;
use app\admin\service\InternalCompetitionService;
use app\admin\service\SingleChoiceService;
use app\admin\service\Admin as adminService;
use app\admin\service\AuthGroup as authGroupService;
use app\admin\service\AuthGroupAccess as authGroupAccessService;
use app\admin\service\AuthRule as authRuleService;
use app\admin\service\TrueFalseQuestionService;
use app\admin\service\UserBaseService;
use app\admin\service\UserStudyWritingService;
use app\admin\service\UserSynthesizeRankService;
use app\admin\service\UserSynthesizeService;
use app\admin\service\UserWritingService;
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
    protected $trueFalseQuestionService;
    protected $userBaseService;
    protected $userWritingService;
    protected $userStudyWritingService;
    protected $userSynthesizeService;
    protected $userSynthesizeRankService;
    protected $internalCompetitionJoinService;
    protected $internalCompetitionService;

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
     * @param TrueFalseQuestionService $trueFalseQuestionService
     * @param UserBaseService $userBaseService
     * @param UserWritingService $userWritingService
     * @param UserStudyWritingService $userStudyWritingService
     * @param UserSynthesizeRankService $userSynthesizeRankService
     * @param UserSynthesizeService $userSynthesizeService
     * @param InternalCompetitionJoinService $internalCompetitionJoinService
     * @param InternalCompetitionService $internalCompetitionService
     */
    public function __construct( AdminService $adminService, AuthGroupService $authGroupService,
                                 AuthGroupAccessService $authGroupAccessService, AuthRuleService $authRuleService,
                                 FillTheBlanksService $fillTheBlanksService, SingleChoiceService $singleChoiceService,
                                 WritingLibraryService $writingLibraryService,
                                 TrueFalseQuestionService $trueFalseQuestionService,
                                 UserBaseService $userBaseService,
                                 UserWritingService $userWritingService,
                                 UserStudyWritingService $userStudyWritingService,
                                 UserSynthesizeRankService $userSynthesizeRankService,
                                 UserSynthesizeService $userSynthesizeService,
                                 InternalCompetitionJoinService $internalCompetitionJoinService,
                                 InternalCompetitionService $internalCompetitionService){

        parent::__construct();

        $this->adminService = $adminService;
        $this->authGroupService = $authGroupService;
        $this->authGroupAccessService = $authGroupAccessService;
        $this->authRuleService = $authRuleService;
        $this->fillTheBlanksService = $fillTheBlanksService;
        $this->singleChoiceService = $singleChoiceService;
        $this->writingLibraryService = $writingLibraryService;
        $this->trueFalseQuestionService = $trueFalseQuestionService;
        $this->userBaseService = $userBaseService;
        $this->userWritingService = $userWritingService;
        $this->userStudyWritingService = $userStudyWritingService;
        $this->userSynthesizeRankService = $userSynthesizeRankService;
        $this->userSynthesizeService = $userSynthesizeService;
        $this->internalCompetitionJoinService = $internalCompetitionJoinService;
        $this->internalCompetitionService = $internalCompetitionService;
    }
}