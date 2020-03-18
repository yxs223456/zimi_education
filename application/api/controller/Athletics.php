<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-18
 * Time: 11:13
 */

namespace app\api\controller;

use app\api\service\v1\QuestionService;
use app\common\AppException;

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
        if ($difficultyLevel === null || !in_array($difficultyLevel, [1,2,3,4,5,6])) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }
        $user = $this->query["user"];

        $questionService = new QuestionService();
        return $this->jsonResponse($questionService->getSynthesize($user, $difficultyLevel));
    }
}