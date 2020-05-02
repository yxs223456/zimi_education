<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-05-02
 * Time: 16:01
 */
namespace app\admin\controller;

use app\common\enum\FillTheBlanksAnswerIsSequenceEnum;
use app\common\enum\QuestionTypeEnum;
use app\common\enum\TrueFalseQuestionAnswerEnum;
use app\common\enum\UserBaseChannelEnum;
use app\common\enum\UserCoinAddTypeEnum;
use app\common\enum\UserStudyWritingIsCommentEnum;
use app\common\enum\UserSynthesizeScoreIsFinishEnum;
use app\common\enum\UserWritingIsCommentEnum;
use app\common\enum\UserWritingSourceTypeEnum;
use app\common\helper\Redis;
use app\common\model\UserCoinLogModel;
use think\Db;

class User extends Base
{
    public function convertRequestToWhereSql()
    {

        $whereSql = " 1=1 ";
        $pageMap = [];

        $params = input("param.");

        foreach ($params as $key => $value) {

            if ($value == "-999"
                || isNullOrEmpty($value))
                continue;

            switch ($key) {

                case "channel":
                    $whereSql .= " and channel = '$value'";
                    break;

            }

            $pageMap[$key] = $value;
            $this->assign($key, $value);

        }
        $data["whereSql"] = $whereSql;
        $data["pageMap"] = $pageMap;

        return $data;
    }

    public function userList()
    {
        $condition = $this->convertRequestToWhereSql();
        $list = $this->userBaseService->getListByCondition($condition);
        foreach ($list as $item) {
            if ($item["channel"]) {
                $item["channel"] = UserBaseChannelEnum::getEnumDescByValue($item["channel"]);
            }
            $item["level"] = empty($item["level"])?"无":$item["level"]."星";
        }
        $this->assign('list', $list);

        $userChannel = UserBaseChannelEnum::getAllList();
        $this->assign("userChannel", $userChannel);

        return $this->fetch("userList");
    }
}