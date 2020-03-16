<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-16
 * Time: 11:55
 */

namespace app\api\controller;

use app\api\service\v1\QuestionService;
use app\common\AppException;

class Novice extends Base
{
    protected $beforeActionList = [
        'checkAuth' => [
            'except' => '',
        ],
    ];

    public function getQuestions()
    {
        $questionService = new QuestionService();
        $returnData = $questionService->getNoviceTestQuestions();

        return $this->jsonResponse($returnData);
    }

    public function submitResult()
    {
        $noviceLevel = input("novice_level");
        if ($noviceLevel === null || !in_array($noviceLevel, [0,1,2,3,4,5,6])) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $user = $this->query["user"];
        $questionService = new QuestionService();
        $returnData = $questionService->submitNoviceResult($user, $noviceLevel);

        return $this->jsonResponse($returnData);
    }
}