<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-04-07
 * Time: 16:50
 */

namespace app\api\controller;

use app\api\service\v1\RankService;
use app\common\AppException;
use app\common\enum\QuestionDifficultyLevelEnum;

class Rank extends Base
{
    protected $beforeActionList = [
        'checkAuth' => [
            'except' => '',
        ],
    ];

    public function synthesizeRank()
    {
        $difficultyLevel = input("difficulty_level");
        if ($difficultyLevel === null || !in_array($difficultyLevel, QuestionDifficultyLevelEnum::getAllValues())) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }
        $user = $this->query["user"];

        $rankService = new RankService();
        return $this->jsonResponse($rankService->synthesizeRank($user, $difficultyLevel));
    }
}