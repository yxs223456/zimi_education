<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-02-23
 * Time: 16:44
 */

namespace app\admin\controller;

use app\common\enum\QuestionIsUseEnum;
use app\common\enum\TrueFalseQuestionAnswerEnum;
use app\common\helper\Redis;
use think\Db;

class TestLibrary extends Common
{
    public function convertRequestToWhereSql() {

        $whereSql = " 1=1";
        $pageMap = [];

        $params = input("param.");

        foreach($params as $key => $value) {

            if($value == "-999"
                || isNullOrEmpty($value))
                continue;

            switch ($key) {

                case "question":
                    $whereSql .= " and question LIKE '%".$value."%'";
                    break;

            }

            $pageMap[$key] = $value;
            $this->assign($key, $value);

        }

        $data["whereSql"] = $whereSql;
        $data["pageMap"] = $pageMap;

        return $data;

    }

    //填空题列表
    public function fillTheBlanksList()
    {
        $condition = $this->convertRequestToWhereSql();

        $list = $this->fillTheBlanksService->getListByCondition($condition);

        $this->assign('list', $list);

        return $this->fetch("fillTheBlanksList");
    }

    //添加填空题页面
    public function addFillTheBlanksList()
    {
        return $this->fetch("addFillTheBlanksList");
    }

    //执行添加填空题动作
    public function doAddFillTheBlanksList()
    {
        $fillTheBlanksList = input("fillTheBlanksList");

        $fillTheBlanksList = json_decode($fillTheBlanksList, true);

        if (!is_array($fillTheBlanksList)) {
            $this->error('数据格式错误');
        }

        $time = time();
        $data = [];
        foreach ($fillTheBlanksList as $fillTheBlanks) {
            if ($fillTheBlanks["question"] == "" ||
                $fillTheBlanks["answer"] == "" ||
                $fillTheBlanks["difficulty_level"] == "") {
                continue;
            }
            $data[] = [
                "uuid" => createUuid(),
                "question" => $fillTheBlanks["question"],
                "answer" => $fillTheBlanks["answer"],
                "difficulty_level" => $fillTheBlanks["difficulty_level"],
                "is_use" => $fillTheBlanks["is_use"],
                "create_time" => $time,
                "update_time" => $time,
            ];
        }

        if ($data) {
            Db::name("fill_the_blanks")->insertAll($data);
            $redis = Redis::factory();
            addFillTheBlanksArray($data, $redis);
        }
        $this->success("添加成功");
    }

    //单选题列表
    public function singleChoiceList()
    {
        $condition = $this->convertRequestToWhereSql();

        $list = $this->singleChoiceService->getListByCondition($condition);

        foreach ($list as $item) {
            $item["possible_answers"] = json_decode($item["possible_answers"], true);
        }

        $this->assign('list', $list);

        return $this->fetch("singleChoiceList");
    }

    //添加单选题页面
    public function addSingleChoiceList()
    {
        return $this->fetch("addSingleChoiceList");
    }

    //执行添加单选题动作
    public function doAddSingleChoiceList()
    {
        $singleChoiceList = input("singleChoiceList");

        $singleChoiceList = json_decode($singleChoiceList, true);

        if (!is_array($singleChoiceList)) {
            $this->error('数据格式错误');
        }

        $time = time();
        $data = [];
        foreach ($singleChoiceList as $singleChoice) {
            if ($singleChoice["question"] == "" ||
                !in_array($singleChoice["answer"], ["A","B","C","D"]) ||
                !is_numeric($singleChoice["difficulty_level"])) {
                continue;
            }
            if ($singleChoice["A"] == "" || $singleChoice["B"] == "" ||
                $singleChoice["C"] == "" || $singleChoice["D"] == "") {
                continue;
            }
            $data[] = [
                "uuid" => createUuid(),
                "question" => $singleChoice["question"],
                "possible_answers" => json_encode([
                    $singleChoice["A"],
                    $singleChoice["B"],
                    $singleChoice["C"],
                    $singleChoice["D"],
                ], JSON_UNESCAPED_UNICODE),
                "answer" => $singleChoice["answer"],
                "difficulty_level" => $singleChoice["difficulty_level"],
                "is_use" => $singleChoice["is_use"],
                "create_time" => $time,
                "update_time" => $time,
            ];
        }

        if ($data) {
            Db::name("single_choice")->insertAll($data);
            $redis = Redis::factory();
            addSingleChoiceArray($data, $redis);
        }

        $this->success("添加成功");
    }

    //作文题列表
    public function writingList()
    {
        $condition = $this->convertRequestToWhereSql();

        $list = $this->writingLibraryService->getListByCondition($condition);

        foreach ($list as $item) {
            $item["requirements"] = json_decode($item["requirements"], true);
        }

        $this->assign('list', $list);

        return $this->fetch("writingList");
    }

    //添加作文题页面
    public function addWriting()
    {
        return $this->fetch("addWriting");
    }

    //执行添加作文题
    public function doAddWriting()
    {
        $topic = input("topic", "");
        $requirements = input("requirements", "");
        $difficultyLevel = input("difficulty_level", "");
        if ($topic === "") {
            $this->error('题目不能为空');
        }

        if (empty($difficultyLevel) || !is_numeric($difficultyLevel)) {
            $this->error('难度等级格式错误');
        }

        $requirements = json_decode($requirements, true);

        if (!is_array($requirements)) {
            $this->error('要求不能为空');
        }

        $requirementsData = [];
        foreach ($requirements as $requirement) {
            if ($requirement["requirement"] == "") {
                continue;
            }
            $requirementsData[] = $requirement["requirement"];
        }

        if (count($requirementsData) == 0) {
            $this->error('要求不能为空');
        }

        $time = time();
        $writingData = [
            "uuid" => createUuid(),
            "topic" => $topic,
            "requirements" => json_encode($requirementsData, JSON_UNESCAPED_UNICODE),
            "difficulty_level" => $difficultyLevel,
            "is_use" => input("is_use"),
            "create_time" => $time,
            "update_time" => $time,
        ];

        Db::name("writing")->insert($writingData);
        if ($writingData["is_use"] == QuestionIsUseEnum::YES) {
            $redis = Redis::factory();
            addWriting($writingData["uuid"], $difficultyLevel, $redis);
        }

        $this->success("添加成功");
    }

    //判断题列表
    public function trueFalseQuestionList()
    {
        $condition = $this->convertRequestToWhereSql();

        $list = $this->trueFalseQuestionService->getListByCondition($condition);

        foreach ($list as $item) {
            $item["answer"] = $item["answer"] == TrueFalseQuestionAnswerEnum::DESC_TRUE ? "✅" : "❌";
        }

        $this->assign('list', $list);

        return $this->fetch("trueFalseQuestionList");
    }

    //添加单选题页面
    public function addTrueFalseQuestionList()
    {
        return $this->fetch("addTrueFalseQuestionList");
    }

    //执行添加单选题
    public function doAddTrueFalseQuestionList()
    {
        $trueFalseQuestionList = input("trueFalseQuestionList");

        $trueFalseQuestionList = json_decode($trueFalseQuestionList, true);

        if (!is_array($trueFalseQuestionList)) {
            $this->error('数据格式错误');
        }

        $time = time();
        $data = [];
        foreach ($trueFalseQuestionList as $trueFalseQuestion) {
            if ($trueFalseQuestion["question"] == "" ||
                $trueFalseQuestion["answer"] == "" ||
                $trueFalseQuestion["difficulty_level"] == "") {
                continue;
            }
            $data[] = [
                "uuid" => createUuid(),
                "question" => $trueFalseQuestion["question"],
                "answer" => $trueFalseQuestion["answer"],
                "difficulty_level" => $trueFalseQuestion["difficulty_level"],
                "create_time" => $time,
                "update_time" => $time,
            ];
        }

        if ($data) {
            Db::name("true_false_question")->insertAll($data);
            $redis = Redis::factory();
            addTrueFalseQuestionArray($data, $redis);
        }

        $this->success("添加成功");
    }

    //操作题库缓存
    public function operateLibraryCache()
    {

        $libraryType = input("type");
        $uuid = input("uuid");
        $difficultyLevel = input("difficulty_level");
        $do = input("do");
        $redis = Redis::factory();
        if ($do == "add") {
            $dbUpdateData = [
                "is_use" => QuestionIsUseEnum::YES,
                "update_time" => time(),
            ];
        } else {
            $dbUpdateData = [
                "is_use" => QuestionIsUseEnum::NO,
                "update_time" => time(),
            ];
        }

        switch ($libraryType) {
            case "fillTheBlanks":
                if ($do == "add") {
                    addFillTheBlanks($uuid, $difficultyLevel, $redis);
                } else {
                    removeFillTheBlanks($uuid, $difficultyLevel, $redis);
                }
                Db::name("fill_the_blanks")
                    ->where("uuid", $uuid)
                    ->update($dbUpdateData);
                $this->redirect("fillTheBlanksList");
                break;
            case "singleChoice":
                if ($do == "add") {
                    addSingleChoice($uuid, $difficultyLevel, $redis);
                } else {
                    removeSingleChoice($uuid, $difficultyLevel, $redis);
                }
                Db::name("single_choice")
                    ->where("uuid", $uuid)
                    ->update($dbUpdateData);
                $this->redirect("singleChoiceList");
                break;
            case "trueFalseQuestion":
                if ($do == "add") {
                    addTrueFalseQuestion($uuid, $difficultyLevel, $redis);
                } else {
                    removeTrueFalseQuestion($uuid, $difficultyLevel, $redis);
                }
                Db::name("true_false_question")
                    ->where("uuid", $uuid)
                    ->update($dbUpdateData);
                $this->redirect("trueFalseQuestionList");
                break;
            case "writing":
                if ($do == "add") {
                    addWriting($uuid, $difficultyLevel, $redis);
                } else {
                    removeWriting($uuid, $difficultyLevel, $redis);
                }
                Db::name("writing")
                    ->where("uuid", $uuid)
                    ->update($dbUpdateData);
                $this->redirect("writingList");
                break;

        }


    }

}