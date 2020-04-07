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
use app\common\enum\UserStudyWritingIsCommentEnum;
use app\common\enum\UserWritingIsCommentEnum;
use app\common\enum\UserWritingSourceTypeEnum;
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

                case "is_comment":
                    $whereSql .= " and is_comment = $value";
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
        $condition["whereSql"] .= " and source_type = " . UserWritingSourceTypeEnum::STUDY;
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
        $images = $answer["images"]??[];
        $text = $answer["text"]??[];

        $this->assign("info", $userWriting);
        $this->assign("requirements", json_encode($requirements));
        $this->assign("images", $images);
        $this->assign("text", $text);

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
        $condition["whereSql"] .= " and source_type = " . UserWritingSourceTypeEnum::SYNTHESIZE;
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
        $images = $answer["images"]??[];
        $text = $answer["text"]??[];

        $this->assign("info", $userWriting);
        $this->assign("requirements", json_encode($requirements));
        $this->assign("images", $images);
        $this->assign("text", $text);

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
                            if ($answer == $answerInfo["answer"]) {
                                $isRight = 1;
                                $userScore += 2;
                            }
                        } else {
                            if (count($answerInfo["answer"]) == count($answer)) {
                                $isRight = 1;
                                $userScore += 2;
                                foreach ($answer as $value) {
                                    if (!in_array($value, $answerInfo["answer"])) {
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
                            "user_answer" => $answerInfo["answer"],
                            "is_right" => $isRight,
                            "score" => 2,
                        ];
                    }
                }
            } else if ($item["type"] == QuestionTypeEnum::TRUE_FALSE_QUESTION) {
                foreach ($item["list"] as $answerInfo) {
                    $isRight = 0;
                    if (isset($trueFalseQuestionAnswer[$answerInfo["uuid"]]) &&
                        $trueFalseQuestionAnswer[$answerInfo["uuid"]]["answer"] == $answerInfo["answer"]) {
                        $isRight = 1;
                        $userScore += 2;
                    }
                    $scoreInfoData["list"][] = [
                        "uuid" => $answerInfo["uuid"],
                        "answer" => $trueFalseQuestionAnswer[$answerInfo["uuid"]]["answer"],
                        "user_answer" => $answerInfo["answer"],
                        "is_right" => $isRight,
                        "score" => 2,
                    ];
                }
            }
        }

        var_dump($scoreInfo);
        var_dump($userScore);

        Db::startTrans();
        try {
//            $userWriting->score = $score;
//            $userWriting->comment = $param["comment"];
//            $userWriting->comment_time = time();
//            $userWriting->is_comment = UserWritingIsCommentEnum::YES;
//            $userWriting->save();
//
//            $userSynthesize->is_comment = UserStudyWritingIsCommentEnum::YES;
//            $userSynthesize->save();

            Db::commit();
            $this->success("修改成功");
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }
}