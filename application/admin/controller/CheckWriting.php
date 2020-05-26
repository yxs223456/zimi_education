<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-31
 * Time: 17:11
 */

namespace app\admin\controller;

use app\common\enum\FillTheBlanksAnswerIsSequenceEnum;
use app\common\enum\QuestionTypeEnum;
use app\common\enum\TrueFalseQuestionAnswerEnum;
use app\common\enum\UserCoinAddTypeEnum;
use app\common\enum\UserStudyWritingIsCommentEnum;
use app\common\enum\UserSynthesizeScoreIsFinishEnum;
use app\common\enum\UserWritingIsCommentEnum;
use app\common\enum\UserWritingSourceTypeEnum;
use app\common\helper\Redis;
use app\common\model\NewsModel;
use app\common\model\UserCoinLogModel;
use think\Db;

class CheckWriting extends Base
{
    public function convertRequestToWhereSql() {

        $whereSql = " 1=1 ";
        $pageMap = [];

        $params = input("param.");

        foreach($params as $key => $value) {

            if($value == "-999"
                || isNullOrEmpty($value))
                continue;

            switch ($key) {
                case "invite_code":
                    $whereSql .= " and invite_code = '$value'";
                    break;
                case "is_comment":
                    $whereSql .= " and uw.is_comment = $value";
                    break;

            }

            $pageMap[$key] = $value;
            $this->assign($key, $value);

        }
        $data["whereSql"] = $whereSql;
        $data["pageMap"] = $pageMap;

        return $data;

    }

    public function studyWritingList()
    {
        $condition = $this->convertRequestToWhereSql();
        $condition["whereSql"] .= " and uw.source_type = " . UserWritingSourceTypeEnum::STUDY;
        $list = $this->userWritingService->getListByCondition($condition);
        $this->assign('list', $list);

        $userWritingIsComment = UserWritingIsCommentEnum::getAllList();
        $this->assign("userWritingIsComment", $userWritingIsComment);

        return $this->fetch("studyWritingList");
    }

    public function checkStudyWriting()
    {
        $id = input("param.id");

        $userWriting = $this->userWritingService->findById($id);
        $requirementsArr = json_decode($userWriting["requirements"], true);
        $requirements = [];
        foreach ($requirementsArr as $item) {
            $requirements[]["requirement"] = $item;
        }

        $answer = json_decode($userWriting["content"], true);
        $image = $answer["image"]??[];
        $text = $answer["text"]??[];
        $contentLength = 0;
        if ($text) {
            $contentLength = mb_strlen($text["content"]);
        }

        $this->assign("info", $userWriting);
        $this->assign("requirements", json_encode($requirements));
        $this->assign("image", $image);
        $this->assign("text", $text);
        $this->assign("contentLength", $contentLength);

        return $this->fetch("checkStudyWriting");
    }

    public function doStudyWritingCheck()
    {
        $param = input();

        $id = $param["id"];
        $userWriting = $this->userWritingService->findById($id);
        if ($userWriting == null) {
            $this->error('数据不存在');
        }

        if (!isset($param["score"]) || !is_numeric($param["score"]) ||
            $param["score"] < 0 || $param["score"] > $userWriting["total_score"]) {
            $this->error("分值范围错误");
        }
        $score = (int) $param["score"];

        if (empty($param["comment"])) {
            $this->error("评语不能为空");
        }

        $userStudyWriting = $this->userStudyWritingService->findByMap(["uuid"=>$userWriting["source_uuid"]]);
        if ($userStudyWriting == null) {
            $this->error('数据不存在');
        }

        Db::startTrans();
        try {
            $userWriting->score = $score;
            $userWriting->comment = $param["comment"];
            $userWriting->comment_time = time();
            $userWriting->is_comment = UserWritingIsCommentEnum::YES;
            $userWriting->save();

            $userStudyWriting->is_comment = UserStudyWritingIsCommentEnum::YES;
            $userStudyWriting->save();

            Db::commit();
            $this->success("修改成功",url("studyWritingList"));
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    public function synthesizeWritingList()
    {
        $condition = $this->convertRequestToWhereSql();
        $condition["whereSql"] .= " and uw.source_type = " . UserWritingSourceTypeEnum::SYNTHESIZE;
        $list = $this->userWritingService->getListByCondition($condition);
        $this->assign('list', $list);

        $userWritingIsComment = UserWritingIsCommentEnum::getAllList();
        $this->assign("userWritingIsComment", $userWritingIsComment);

        return $this->fetch("synthesizeWritingList");
    }

    public function checkSynthesizeWriting()
    {
        $id = input("param.id");

        $userWriting = $this->userWritingService->findById($id);
        $requirementsArr = json_decode($userWriting["requirements"], true);
        $requirements = [];
        foreach ($requirementsArr as $item) {
            $requirements[]["requirement"] = $item;
        }

        $answer = json_decode($userWriting["content"], true);
        $image = $answer["image"]??[];
        $text = $answer["text"]??[];
        $contentLength = 0;
        if ($text) {
            $contentLength = mb_strlen($text["content"]);
        }

        $this->assign("info", $userWriting);
        $this->assign("requirements", json_encode($requirements));
        $this->assign("image", $image);
        $this->assign("text", $text);
        $this->assign("contentLength", $contentLength);

        return $this->fetch("checkSynthesizeWriting");
    }

    public function doSynthesizeWritingCheck()
    {
        $param = input();

        $id = $param["id"];
        $userWriting = $this->userWritingService->findById($id);
        if ($userWriting == null) {
            $this->error('数据不存在');
        }

        if (!isset($param["score"]) || !is_numeric($param["score"]) ||
            $param["score"] < 0 || $param["score"] > $userWriting["total_score"]) {
            $this->error("分值范围错误");
        }
        $score = (int) $param["score"];

        if (empty($param["comment"])) {
            $this->error("评语不能为空");
        }

        $userSynthesize = $this->userSynthesizeService->findByMap(["uuid"=>$userWriting["source_uuid"]]);
        if ($userSynthesize == null) {
            $this->error('综合测试不存在');
        }

        $user = $this->userBaseService->findByMap(["uuid"=>$userSynthesize["user_uuid"]]);
        if ($user == null) {
            $this->error('用户不存在');
        }

        $singleChoiceUuids = [];
        $fillTheBlanksUuids = [];
        $trueFalseQuestionUuids = [];

        $questions = json_decode($userSynthesize["questions"], true);
        foreach ($questions as $item) {
            switch ($item["type"]) {
                case QuestionTypeEnum::SINGLE_CHOICE:
                    $singleChoiceUuids = $item["uuids"];
                    break;
                case QuestionTypeEnum::FILL_THE_BLANKS:
                    $fillTheBlanksUuids = $item["uuids"];
                    break;
                case QuestionTypeEnum::TRUE_FALSE_QUESTION:
                    $trueFalseQuestionUuids = $item["uuids"];
                    break;
            }
        }

        $singleChoice = $this->singleChoiceService->getByUuids($singleChoiceUuids);
        $singleChoiceAnswer = array_column($singleChoice, null, "uuid");

        $fillTheBlanks = $this->fillTheBlanksService->getByUuids($fillTheBlanksUuids);
        $fillTheBlanksAnswer = array_column($fillTheBlanks, null, "uuid");

        $trueFalseQuestion = $this->trueFalseQuestionService->getByUuids($trueFalseQuestionUuids);
        $trueFalseQuestionAnswer = array_column($trueFalseQuestion, null, "uuid");

        $answers = json_decode($userSynthesize["answers"], true);
        $userScore = $score;
        $scoreInfo = [];
        foreach ($answers as $item) {
            $scoreInfoData = [
                "type" => $item["type"],
                "list" => []
            ];
            if ($item["type"] == QuestionTypeEnum::SINGLE_CHOICE) {
                foreach ($item["list"] as $answerInfo) {
                    $isRight = 0;
                    if (isset($singleChoiceAnswer[$answerInfo["uuid"]]) &&
                        $singleChoiceAnswer[$answerInfo["uuid"]]["answer"] == $answerInfo["answer"]) {
                        $isRight = 1;
                        $userScore += 2;
                    }
                    $scoreInfoData["list"][] = [
                        "uuid" => $answerInfo["uuid"],
                        "answer" => $singleChoiceAnswer[$answerInfo["uuid"]]["answer"],
                        "user_answer" => $answerInfo["answer"],
                        "is_right" => $isRight,
                        "score" => 2,
                    ];
                }
            } else if ($item["type"] == QuestionTypeEnum::FILL_THE_BLANKS) {
                foreach ($item["list"] as $answerInfo) {
                    $isRight = 0;
                    if (isset($fillTheBlanksAnswer[$answerInfo["uuid"]])) {
                        $answer = json_decode($fillTheBlanksAnswer[$answerInfo["uuid"]]["answer"], true);
                        if ($fillTheBlanksAnswer[$answerInfo["uuid"]]["is_sequence"] == FillTheBlanksAnswerIsSequenceEnum::YES) {
                            if ($answer == $answerInfo["answers"]) {
                                $isRight = 1;
                                $userScore += 2;
                            }
                        } else {
                            if (count($answerInfo["answers"]) == count($answer)) {
                                $isRight = 1;
                                $userScore += 2;
                                foreach ($answer as $value) {
                                    if (!in_array($value, $answerInfo["answers"])) {
                                        $isRight = 0;
                                        $userScore -= 2;
                                        break;
                                    }
                                }
                            }
                        }
                        $scoreInfoData["list"][] = [
                            "uuid" => $answerInfo["uuid"],
                            "answer" => $answer,
                            "user_answer" => $answerInfo["answers"],
                            "is_right" => $isRight,
                            "score" => 2,
                        ];
                    }
                }
            } else if ($item["type"] == QuestionTypeEnum::TRUE_FALSE_QUESTION) {
                foreach ($item["list"] as $answerInfo) {
                    $isRight = 0;
                    $userAnswer = 0;
                    if (isset($trueFalseQuestionAnswer[$answerInfo["uuid"]])) {
                        $userAnswer = $answerInfo["answer"]=="A"?TrueFalseQuestionAnswerEnum::DESC_TRUE:
                            ($answerInfo["answer"]=="B"?TrueFalseQuestionAnswerEnum::DESC_FALSE:0);
                        if ($trueFalseQuestionAnswer[$answerInfo["uuid"]]["answer"] == $userAnswer) {
                            $isRight = 1;
                            $userScore += 2;
                        }
                    }
                    $scoreInfoData["list"][] = [
                        "uuid" => $answerInfo["uuid"],
                        "answer" => $trueFalseQuestionAnswer[$answerInfo["uuid"]]["answer"],
                        "user_answer" => $userAnswer,
                        "is_right" => $isRight,
                        "score" => 2,
                    ];
                }
            }

            $scoreInfo[] = $scoreInfoData;
        }

        $userCoinLogModel = new UserCoinLogModel();

        Db::startTrans();
        try {
            $userWriting->score = $score;
            $userWriting->comment = $param["comment"];
            $userWriting->comment_time = time();
            $userWriting->is_comment = UserWritingIsCommentEnum::YES;
            $userWriting->save();

            $userSynthesize->score = $userScore;
            $userSynthesize->score_info = json_encode($scoreInfo, JSON_UNESCAPED_UNICODE);
            $userSynthesize->score_is_finish = UserSynthesizeScoreIsFinishEnum::YES;
            $userSynthesize->save();

            //当前星级题大于80分即可获得当前星级称
            $isUpdate = false;
            $unUpdateNews = false;
            if ($userScore >= 80 && $userSynthesize["difficulty_level"] > $user["level"]) {
                $user->level = $userSynthesize->difficulty_level;
                //用户等级勋章
                $medals = json_decode($user["medals"], true);
                $selfMedals = json_decode($user["self_medals"], true);
                $medals["level"] = $userSynthesize->difficulty_level;
                $user->medals = json_encode($medals, JSON_UNESCAPED_UNICODE);
                if (count($selfMedals) == 0 || (count($selfMedals) == 1 && isset($selfMedals["novice_level"]))) {
                    $user->self_medals = json_encode(["level"=>$userSynthesize->difficulty_level], JSON_UNESCAPED_UNICODE);
                }
                $user->save();
                $isUpdate = true;

                //发放DE币
                Db::name("user_base")->where("uuid", $userSynthesize["user_uuid"])
                    ->inc("coin", $userSynthesize["difficulty_level"] * 10)
                    ->update(["update_time"=>time()]);

                //纪录书币流水
                $userCoinLogModel->recordAddLog(
                    $userSynthesize["user_uuid"],
                    UserCoinAddTypeEnum::LEVEL_UP,
                    $userSynthesize["difficulty_level"] * 10,
                    $user["coin"],
                    $user["coin"]+($userSynthesize["difficulty_level"] * 10),
                    UserCoinAddTypeEnum::LEVEL_UP_DESC);
            } else if ($userSynthesize["difficulty_level"] > $user["level"] && $userScore < 80) {
                $unUpdateNews = true;
            }

            $userSynthesizeRank = $this->userSynthesizeRankService
                ->findByUserUuidAndDifficultyLevel($userSynthesize["user_uuid"], $userSynthesize["difficulty_level"]);
            if ($userSynthesizeRank) {
                $userSynthesizeRank->total_score += $userScore;
                $userSynthesizeRank->save();
            } else {
                $userSynthesizeRankData = [
                    "user_uuid" => $userSynthesize["user_uuid"],
                    "difficulty_level" => $userSynthesize["difficulty_level"],
                    "total_score" => $userScore,
                ];
                $this->userSynthesizeRankService->saveByData($userSynthesizeRankData);
            }

            Db::commit();

            if ($isUpdate == true) {
                $redis = Redis::factory();
                $userInfo = $this->userBaseService->findByMap(["uuid"=>$userSynthesize["user_uuid"]])->toArray();
                cacheUserInfoByToken($userInfo, $redis);
                pushSynthesizeUpdateList($userInfo["nickname"], $userSynthesize["difficulty_level"], $redis);

                //升级消息
                $newsModel =  new NewsModel();
                $content = "恭喜你成功通过了{$userSynthesize['difficulty_level']}星综合测试，获得".
                    $userSynthesize["difficulty_level"] * 10 .
                "DE 奖励，系统将自动为你升级为{$userSynthesize['difficulty_level']}星学员称号。";
                $newsModel->addNews($user["uuid"], $content);
                $title = "你的综合测试结果出来啦，快来看看吧！";
                createUnicastPushTask($user["os"], $user["uuid"], $content, "", [], $redis, $title);
            }
            if ($unUpdateNews) {
                //未升级消息
                $redis = Redis::factory();
                $newsModel =  new NewsModel();
                $content = "很遗憾你没有通过{$userSynthesize['difficulty_level']}星综合测试，可以通过专项训 练提高自己的水平哦~";
                $newsModel->addNews($user["uuid"], $content);
                $title = "你的综合测试结果出来啦，快来看看吧！";
                createUnicastPushTask($user["os"], $user["uuid"], $content, "", [], $redis, $title);
            }
            $this->success("评分成功");
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }
}