<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-16
 * Time: 20:02
 */

namespace app\api\controller;

use app\api\service\v1\QuestionService;
use app\common\AppException;

class Study extends Base
{
    protected $beforeActionList = [
        'checkAuth' => [
            'except' => '',
        ],
    ];

    public function getFillTheBlanks()
    {
        $difficultyLevel = input("difficulty_level");
        if ($difficultyLevel === null || !in_array($difficultyLevel, [1,2,3,4,5,6])) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }
        $user = $this->query["user"];

        $questionService = new QuestionService();
        return $this->jsonResponse($questionService->getStudyFillTheBlanks($user, $difficultyLevel));
    }

    public function getSingleChoice()
    {
        $difficultyLevel = input("difficulty_level");
        if ($difficultyLevel === null || !in_array($difficultyLevel, [1,2,3,4,5,6])) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }
        $user = $this->query["user"];

        $questionService = new QuestionService();
        return $this->jsonResponse($questionService->getStudySingleChoice($user, $difficultyLevel));
    }

    public function getTrueFalseQuestion()
    {
        $difficultyLevel = input("difficulty_level");
        if ($difficultyLevel === null || !in_array($difficultyLevel, [1,2,3,4,5,6])) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }
        $user = $this->query["user"];

        $questionService = new QuestionService();
        return $this->jsonResponse($questionService->getStudyTrueFalseQuestion($user, $difficultyLevel));
    }

    public function getWriting()
    {
        $difficultyLevel = input("difficulty_level");
        if ($difficultyLevel === null || !in_array($difficultyLevel, [1,2,3,4,5,6])) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }
        $user = $this->query["user"];

        $questionService = new QuestionService();
        return $this->jsonResponse($questionService->getStudyWriting($user, $difficultyLevel));
    }

    public function submitFillTheBlanks()
    {
        $param = $this->request->getContent();
        $param = json_decode($param, true);
        if (empty($param["difficulty_level"]) || !in_array($param["difficulty_level"], [1,2,3,4,5,6])) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $questionService = new QuestionService();
        $returnData = $questionService->submitStudyFillTheBlanks($user, $param["difficulty_level"]);

        return $this->jsonResponse($returnData);
    }

    public function submitSingleChoice()
    {
        $param = $this->request->getContent();
        $param = json_decode($param, true);
        if (empty($param["difficulty_level"]) || !in_array($param["difficulty_level"], [1,2,3,4,5,6])) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $questionService = new QuestionService();
        $returnData = $questionService->submitStudySingleChoice($user, $param["difficulty_level"]);

        return $this->jsonResponse($returnData);
    }

    public function submitTrueFalseQuestion()
    {
        $param = $this->request->getContent();
        $param = json_decode($param, true);
        if (empty($param["difficulty_level"]) || !in_array($param["difficulty_level"], [1,2,3,4,5,6])) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $questionService = new QuestionService();
        $returnData = $questionService->submitStudyTrueFalseQuestion($user, $param["difficulty_level"]);

        return $this->jsonResponse($returnData);
    }

    public function submitWriting()
    {
        $param = $this->request->getContent();
        $param = json_decode($param, true);

        if (empty($param["difficulty_level"]) || !in_array($param["difficulty_level"], [1,2,3,4,5,6])) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }
        if (empty($param["uuid"]) || empty($param["content"])) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }
        if (empty($param["content"]["text"]) && empty($param["content"]["images"])) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];

        $questionService = new QuestionService();
        $returnData = $questionService->submitStudyWriting(
            $user,
            $param["uuid"],
            $param["content"],
            $param["difficulty_level"]
        );
        return $this->jsonResponse($returnData);
    }
}