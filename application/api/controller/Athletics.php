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
        if ($pkType === null || !in_array($pkType, PkTypeEnum::getAllValues())) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }
        $pageNum = input("pageNum");
        $pageSize = input("pageSize");
        if (!checkInt($pageNum, false) || !checkInt($pageSize, false)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $athleticsService = new AthleticsService();
        $returnData = $athleticsService->pkList($user, $pkType, $pageNum, $pageSize);

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

    public function competitionList()
    {
        $pageNum = input("pageNum");
        $pageSize = input("pageSize");
        if (!checkInt($pageNum, false) || !checkInt($pageSize, false)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $athleticsService = new AthleticsService();
        $returnData = $athleticsService->competitionList($user, $pageNum, $pageSize);

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

    public function submitCompetitionDraft()
    {
        $competitionUuid = input("uuid");
        $answer = input("answer");
        if (empty($competitionUuid)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }
        if (!is_array($answer) || count($answer) > 2 || (!isset($answer["text"]) && !isset($answer["images"]))) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $athleticsService = new AthleticsService();
        $returnData = $athleticsService->submitCompetitionAnswer($user, $competitionUuid, $answer, true);
        return $this->jsonResponse($returnData);
    }

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
}