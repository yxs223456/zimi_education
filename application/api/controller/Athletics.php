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
use app\common\enum\QuestionTypeEnum;

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
        if ($difficultyLevel === null || !in_array($difficultyLevel, QuestionTypeEnum::getAllValues())) {
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
}