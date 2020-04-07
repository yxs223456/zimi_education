<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-04-07
 * Time: 16:26
 */

namespace app\admin\service;

use app\common\model\UserSynthesizeRankModel;

class UserSynthesizeRankService extends Base
{

    public function __construct()
    {
        parent::__construct();
        $this->currentModel = new UserSynthesizeRankModel();
    }

    public function findByUserUuidAndDifficultyLevel($userUuid, $difficultyLevel)
    {
        return $this->currentModel
            ->where("difficulty_level", $difficultyLevel)
            ->where("user_uuid", $userUuid)
            ->find();
    }
}