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
        $this->jsonResponse($questionService->getStudyFillTheBlanks($user, $difficultyLevel));
    }
}