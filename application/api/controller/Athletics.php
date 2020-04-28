<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-18
 * Time: 11:13
 */

namespace app\api\controller;

use app\api\service\v1\AthleticsService;
use app\api\service\v1\QuestionService;
use app\common\AppException;
use app\common\Constant;
use app\common\enum\PkStatusEnum;
use app\common\enum\PkTypeEnum;
use app\common\enum\QuestionDifficultyLevelEnum;

class Athletics extends Base
{
    protected $beforeActionList = [
        'checkAuth' => [
            'except' => '',
        ],
    ];

    public function getSynthesize()
    {
        $difficultyLevel = input("difficulty_level");
        if ($difficultyLevel === null || !in_array($difficultyLevel, QuestionDifficultyLevelEnum::getAllValues())) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }
        $user = $this->query["user"];

        $questionService = new QuestionService();
        return $this->jsonResponse($questionService->getSynthesize($user, $difficultyLevel));
    }

    public function submitSynthesizeDraft()
    {
        $param = $this->request->getContent();
        $param = json_decode($param, true);

        if (empty($param["uuid"]) || empty($param["answers"])) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];

        $questionService = new QuestionService();
        $returnData = $questionService->submitSynthesizeDraft(
            $user,
            $param["uuid"],
            $param["answers"]
        );
        return $this->jsonResponse($returnData);
    }

    public function submitSynthesize()
    {
        $param = $this->request->getContent();
        $param = json_decode($param, true);
        if (empty($param["uuid"]) || empty($param["answers"])) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];

        $questionService = new QuestionService();
        $returnData = $questionService->submitSynthesize(
            $user,
            $param["uuid"],
            $param["answers"]
        );
        return $this->jsonResponse($returnData);
    }

    public function synthesizeReportCardList()
    {
        $difficultyLevel = input("difficulty_level");
        if ($difficultyLevel === null || !in_array($difficultyLevel, QuestionDifficultyLevelEnum::getAllValues())) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $pageNum = input("page_num");
        $pageSize = input("page_size");
        if (!checkInt($pageNum, false) || !checkInt($pageSize, false)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];

        $athleticsService = new AthleticsService();
        $returnData = $athleticsService->synthesizeReportCardList($user, $difficultyLevel, $pageNum, $pageSize);

        return $this->jsonResponse($returnData);
    }

    public function initPkRule()
    {
        $returnData = [
            [
                "is_finish" => 0,
                "title" => "发起 PK",
                "description" => "发起 PK 需要消耗发起者的 DE 币：新手模式消耗 20DE，简单模式 40DE，困难模式 60DE， 超神模式 100DE。发起者可以根据自己的情况进行题型难度的选择。",
            ],
            [
                "is_finish" => 0,
                "title" => "审核",
                "description" => "发起者发起 PK 后，后台将对 PK 的信息进行审核，审核时间最长为 6 小时，审核内容主要为比赛信息的填写是否符合国家规范。",
            ],
            [
                "is_finish" => 0,
                "title" => "报名",
                "description" => "审核通过后，发起者可在 PK 列表中看到相关报名信息。学员可以在 PK 列表中点击报名，发起者也可以邀请学员进行报名。报名截止时间为审核第二日 24 点。",
            ],
            [
                "is_finish" => 0,
                "title" => "开赛",
                "description" => "1、报名人数已满即为开赛，开赛后开始答题，按钮开放，按钮上面提示当前比赛已开始， 比赛截止时间最长为开赛后的第三天 24 点，请尽快在截止时间内作答。
2、若报名人数未满 3 人，则不能开启比赛。
3、若报名人数满 3 人，但未满足发起者设置的人数，答题按钮正常开放，学员可参照正常开赛要求进行作答。",
            ],
            [
                "is_finish" => 0,
                "title" => "弃赛",
                "description" => "1、若学员在开赛后错过答题时间，则视为弃赛。
2、若学员答题中途断网、强退，不视为弃赛，记录当前已答题结果，上传为 pk 最终成绩。
3、若中途点击返回，不视为弃赛，当前已答题成绩将作为本次 pk 最终成绩。",
            ],
            [
                "is_finish" => 0,
                "title" => "成绩公布",
                "description" => "比赛结束后会在 PK 榜中公布学员成绩，和显示对应奖励。",
            ],
            [
                "is_finish" => 0,
                "title" => "奖励",
                "description" => "1、所有参赛学员的 DE 币累加 80%作为参赛选手前几名奖励，10%平台回收作为 PK 资源消耗，10%用来奖励发起者得到第一名。如发起者没得到第一名，这 10%平台将会回收。
奖励分配如下：
若参赛人数为 3-5 人（包括 5 人），PK 第一名奖励 60% ，第二名奖励 40% 。
若参赛人数为 6-9 人（包括 9 人）， PK 第一名奖励 50% ，第二名奖励 30% ，第三名奖励 20%
若参赛人数为 10 人 ，第一名奖励 40% 第二名奖励 30% 第三名奖励 20% 第四名奖励 10%
2、后台将会记录 PK 隐藏积分，当隐藏积分满足隐藏 PK 称号时触发获得称号提示。学员可以选择替换现有称号。",
            ],
        ];
        return $this->jsonResponse($returnData);
    }

    public function initPk()
    {
        //pk模式验证
        $type = input("type");
        if ($type === null || !in_array($type, PkTypeEnum::getAllValues())) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        //pk挑战时长验证
        $durationHour = input("duration_hour");
        if (!checkInt($durationHour, false) ||
            $durationHour < Constant::PK_VALID_DURATION_HOURS_MIN ||
            $durationHour > Constant::PK_VALID_DURATION_HOURS_MAX) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        //pk挑战人数验证
        $totalNum = input("total_num");
        if (!checkInt($totalNum, false) ||
            $totalNum < Constant::PK_VALID_PEOPLE_NUM_MIN||
            $totalNum > Constant::PK_VALID_PEOPLE_NUM_MAX) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        //pk标题验证
        $name = input("name");
        if (empty($name)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $athleticsService = new AthleticsService();
        $returnData = $athleticsService->initPk($user["uuid"], $type, $durationHour, $totalNum, $name);

        return $this->jsonResponse($returnData);
    }

    public function joinPk()
    {
        $pkUuid = input("uuid");
        if (empty($pkUuid)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $athleticsService = new AthleticsService();
        $returnData = $athleticsService->joinPk($user["uuid"], $pkUuid);

        return $this->jsonResponse($returnData);
    }

    public function pkList()
    {
        $pkType = input("type");
        $pkStatus = input("status");
        if ($pkType === null || !in_array($pkType, PkTypeEnum::getAllValues())) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }
        if (!empty($pkStatus) && !in_array($pkStatus, PkStatusEnum::getAllValues())) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }
        $pageNum = input("page_num");
        $pageSize = input("page_size");
        if (!checkInt($pageNum, false) || !checkInt($pageSize, false)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $athleticsService = new AthleticsService();
        $returnData = $athleticsService->pkList($user, $pkType, $pkStatus,  $pageNum, $pageSize);

        return $this->jsonResponse($returnData);
    }

    public function pkInfo()
    {
        $pkUuid = input("uuid");
        if (empty($pkUuid)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $athleticsService = new AthleticsService();
        $returnData = $athleticsService->pkInfo($user, $pkUuid);
        return $this->jsonResponse($returnData);
    }

    public function submitPkAnswer()
    {
        $param = $this->request->getContent();
        $param = json_decode($param, true);
        $pkUuid = $param["uuid"];
        $answers = $param["answer"];
        $answerTime = $param["answer_time"];
        if (empty($pkUuid) || !is_array($answers) || !checkInt($answerTime, false)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $athleticsService = new AthleticsService();
        $returnData = $athleticsService->submitPkAnswer($user, $pkUuid, $answers, $answerTime);
        return $this->jsonResponse($returnData);
    }

    public function pkReportCard()
    {
        $pageNum = input("page_num");
        $pageSize = input("page_size");
        if (!checkInt($pageNum, false) || !checkInt($pageSize, false)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $athleticsService = new AthleticsService();
        $returnData = $athleticsService->pkReportCard($user, $pageNum, $pageSize);

        return $this->jsonResponse($returnData);
    }

    public function myInitPk()
    {
        $pageNum = input("page_num");
        $pageSize = input("page_size");
        if (!checkInt($pageNum, false) || !checkInt($pageSize, false)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $athleticsService = new AthleticsService();
        $returnData = $athleticsService->myInitPk($user, $pageNum, $pageSize);

        return $this->jsonResponse($returnData);
    }

    public function myJointPk()
    {
        $pageNum = input("page_num");
        $pageSize = input("page_size");
        if (!checkInt($pageNum, false) || !checkInt($pageSize, false)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $athleticsService = new AthleticsService();
        $returnData = $athleticsService->myJointPk($user, $pageNum, $pageSize);

        return $this->jsonResponse($returnData);
    }

    public function competitionSponsorList()
    {
        $returnData = [
            [
                "sponsor" => "内部大赛",
                "id" => 1,
            ],
            [
                "sponsor" => "机构1大赛",
                "id" => 0,
            ],
            [
                "sponsor" => "机构2大赛",
                "id" => 0,
            ],
        ];

        return $this->jsonResponse($returnData);
    }

    public function competitionList()
    {
        $sponsorId = input("sponsor_id", 1);
        $pageNum = input("page_num");
        $pageSize = input("page_size");
        if (!checkInt($pageNum, false) || !checkInt($pageSize, false)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }
        if (!checkInt($sponsorId)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $athleticsService = new AthleticsService();
        $returnData = $athleticsService->competitionList($user, $pageNum, $pageSize, $sponsorId);

        return $this->jsonResponse($returnData);
    }

    public function competitionInfo()
    {
        $competitionUuid = input("uuid");
        if (empty($competitionUuid)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $athleticsService = new AthleticsService();
        $returnData = $athleticsService->competitionInfo($user, $competitionUuid);
        return $this->jsonResponse($returnData);
    }

    public function joinCompetition()
    {
        $competitionUuid = input("uuid");
        if (empty($competitionUuid)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $athleticsService = new AthleticsService();
        $returnData = $athleticsService->joinCompetition($user, $competitionUuid);
        return $this->jsonResponse($returnData);
    }

//    public function submitCompetitionDraft()
//    {
//        $competitionUuid = input("uuid");
//        $answer = input("answer");
//        if (empty($competitionUuid)) {
//            throw AppException::factory(AppException::COM_PARAMS_ERR);
//        }
//        if (!is_array($answer) || count($answer) > 2 || (!isset($answer["text"]) && !isset($answer["images"]))) {
//            throw AppException::factory(AppException::COM_PARAMS_ERR);
//        }
//
//        $user = $this->query["user"];
//        $athleticsService = new AthleticsService();
//        $returnData = $athleticsService->submitCompetitionAnswer($user, $competitionUuid, $answer, true);
//        return $this->jsonResponse($returnData);
//    }

    public function submitCompetition()
    {
        $competitionUuid = input("uuid");
        $answer = input("answer");
        if (empty($competitionUuid)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }
        if (!is_array($answer) || count($answer) > 2 || (!isset($answer["text"]) && !isset($answer["images"]))) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }
        if (empty($answer["text"]) && empty($answer["images"])) {
            throw AppException::factory(AppException::INTERNAL_COMPETITION_ANSWER_EMPTY);
        }

        $user = $this->query["user"];
        $athleticsService = new AthleticsService();
        $returnData = $athleticsService->submitCompetitionAnswer($user, $competitionUuid, $answer);
        return $this->jsonResponse($returnData);
    }

    public function competitionReportCardList()
    {
        $pageNum = input("page_num");
        $pageSize = input("page_size");
        if (!checkInt($pageNum, false) || !checkInt($pageSize, false)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $athleticsService = new AthleticsService();
        $returnData = $athleticsService->competitionReportCardList($user, $pageNum, $pageSize);

        return $this->jsonResponse($returnData);
    }

    public function competitionReportCardInfo()
    {
        $uuid = input("uuid");
        if (empty($uuid)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $athleticsService = new AthleticsService();
        $returnData = $athleticsService->competitionReportCardInfo($user, $uuid);

        return $this->jsonResponse($returnData);
    }

    public function competitionReportCardUserList()
    {
        $uuid = input("uuid");
        if (empty($uuid)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $pageNum = input("page_num");
        $pageSize = input("page_size");
        if (!checkInt($pageNum, false) || !checkInt($pageSize, false)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $athleticsService = new AthleticsService();
        $returnData = $athleticsService->competitionReportCardUserList($user, $uuid, $pageNum, $pageSize);

        return $this->jsonResponse($returnData);
    }
}