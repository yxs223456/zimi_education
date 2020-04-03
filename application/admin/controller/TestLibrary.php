<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-02-23
 * Time: 16:44
 */

namespace app\admin\controller;

use app\common\enum\DbIsDeleteEnum;
use app\common\enum\QuestionDifficultyLevelEnum;
use app\common\enum\QuestionIsUseEnum;
use app\common\enum\TrueFalseQuestionAnswerEnum;
use app\common\helper\Redis;
use think\Db;

class TestLibrary extends Common
{
    public function convertRequestToWhereSql() {

        $whereSql = " is_delete = 0";
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
                case "difficulty_level":
                    $whereSql .= " and difficulty_level = '$value'";
                    break;

            }

            $pageMap[$key] = $value;
            $this->assign($key, $value);

        }
        $allDifficultyLevel = QuestionDifficultyLevelEnum::getAllList();
        $this->assign("allDifficultyLevel", $allDifficultyLevel);

        $data["whereSql"] = $whereSql;
        $data["pageMap"] = $pageMap;

        return $data;

    }

    //填空题列表
    public function fillTheBlanksList()
    {

        $condition = $this->convertRequestToWhereSql();

        $list = $this->fillTheBlanksService->getListByCondition($condition);

        $countArr = [
            "one" => 0,
            "two" => 0,
            "three" => 0,
            "four" => 0,
            "five" => 0,
            "six" => 0,
        ];
        $allDifficultyLevelCount = $this->fillTheBlanksService->allDifficultyLevelCount();
        foreach ($allDifficultyLevelCount as $item) {
            switch ($item["difficulty_level"]) {
                case "1":
                    $countArr["one"] = $item["total"];
                    break;
                case "2":
                    $countArr["two"] = $item["total"];
                    break;
                case "3":
                    $countArr["three"] = $item["total"];
                    break;
                case "4":
                    $countArr["four"] = $item["total"];
                    break;
                case "5":
                    $countArr["five"] = $item["total"];
                    break;
                case "6":
                    $countArr["six"] = $item["total"];
                    break;
            }
        }

        $this->assign("allCount", $countArr);
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
                $fillTheBlanks["answer"] == [] ||
                $fillTheBlanks["difficulty_level"] == "") {
                continue;
            }
            $answers = array_column($fillTheBlanks["answer"], "answer");
            if (count($answers) != mb_substr_count($fillTheBlanks["question"], '${value}')) {
                continue;
            }

            $data[] = [
                "uuid" => createUuid(),
                "question" => $fillTheBlanks["question"],
                "answer" => json_encode($answers, JSON_UNESCAPED_UNICODE),
                "difficulty_level" => $fillTheBlanks["difficulty_level"],
                "is_use" => $fillTheBlanks["is_use"],
                "is_sequence" => $fillTheBlanks["is_sequence"],
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

    public function editFillTheBlanks()
    {
        $id = input('param.id');
        $info = $this->fillTheBlanksService->findById($id);

        $this->assign("info", $info);

        $answersArr = json_decode($info["answer"], true);
        $answers = [];
        foreach ($answersArr as $item) {
            $answers[]["answer"] = $item;
        }

        $this->assign("info", $info);
        $this->assign("answers", json_encode($answers));

        $this->assign('page', input('page', 1));
        $this->assign('difficultyLevel', input('difficultyLevel', ''));

        return $this->fetch("editFillTheBlanks");
    }

    public function editFillTheBlanksPost()
    {
        $id = input('param.id');
        $info = $this->fillTheBlanksService->findById($id);

        $answers = json_decode(input("answers"), true);
        $answers = array_column($answers, "answer");
        if (count($answers) != mb_substr_count(input("question"), '${value}')) {
            $this->error("答案数量与下划线数量不对应");
        }

        $updateData = [
            "question" => input("question"),
            "answer" => json_encode($answers, JSON_UNESCAPED_UNICODE),
            "difficulty_level" => input("difficulty_level"),
            "is_sequence" => input("is_sequence"),
            "update_time" => time(),
        ];

        $this->fillTheBlanksService->updateByIdAndData($id, $updateData);

        if ($info["difficulty_level"] != $updateData["difficulty_level"]) {
            $redis = Redis::factory();
            removeFillTheBlanks($info["uuid"], $info["difficulty_level"], $redis);
            if ($info["is_use"] == QuestionIsUseEnum::YES) {
                addFillTheBlanks($info["uuid"], $updateData["difficulty_level"], $redis);
            }
        }

        $page = input("page",1);
        $difficultyLevel = input('difficultyLevel', '');
        $this->success("修改成功",url("fillTheBlanksList?page=$page&difficulty_level=$difficultyLevel"));
    }

    public function deleteFillTheBlanks()
    {
        $id = input('param.id');
        $info = $this->fillTheBlanksService->findById($id);
        $redis = Redis::factory();
        removeFillTheBlanks($info["uuid"], $info["difficulty_level"], $redis);

        $updateData = [
            "is_delete" => DbIsDeleteEnum::YES,
            "update_time" => time(),
        ];
        $this->fillTheBlanksService->updateByIdAndData($id, $updateData);

        $page = input("page",1);
        $difficultyLevel = input('difficultyLevel', '');
        $this->success("删除成功", url("fillTheBlanksList?page=$page&difficulty_level=$difficultyLevel"));
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

        $countArr = [
            "one" => 0,
            "two" => 0,
            "three" => 0,
            "four" => 0,
            "five" => 0,
            "six" => 0,
        ];
        $allDifficultyLevelCount = $this->singleChoiceService->allDifficultyLevelCount();
        foreach ($allDifficultyLevelCount as $item) {
            switch ($item["difficulty_level"]) {
                case "1":
                    $countArr["one"] = $item["total"];
                    break;
                case "2":
                    $countArr["two"] = $item["total"];
                    break;
                case "3":
                    $countArr["three"] = $item["total"];
                    break;
                case "4":
                    $countArr["four"] = $item["total"];
                    break;
                case "5":
                    $countArr["five"] = $item["total"];
                    break;
                case "6":
                    $countArr["six"] = $item["total"];
                    break;
            }
        }

        $this->assign("allCount", $countArr);

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
            if (trim($singleChoice["A"]) === "" || trim($singleChoice["B"]) === "") {
                continue;
            }
            $answers = [];
            if (trim($singleChoice["A"]) !== "") {
                $answers[] = trim($singleChoice["A"]);
            }
            if (trim($singleChoice["B"]) !== "") {
                $answers[] = trim($singleChoice["B"]);
            }
            if (trim($singleChoice["C"]) !== "") {
                $answers[] = trim($singleChoice["C"]);
            }
            if (trim($singleChoice["D"]) !== "") {
                $answers[] = trim($singleChoice["D"]);
            }
            $data[] = [
                "uuid" => createUuid(),
                "question" => $singleChoice["question"],
                "possible_answers" => json_encode($answers, JSON_UNESCAPED_UNICODE),
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

    public function editSingleChoice()
    {
        $id = input('param.id');

        $info = $this->singleChoiceService->findById($id);

        $possibleAnswers = json_decode($info["possible_answers"], true);

        $answers = [
            "A" => "",
            "B" => "",
            "C" => "",
            "D" => "",
        ];
        foreach ($possibleAnswers as $key=>$answer) {
            switch ($key) {
                case 0:
                    $answers["A"] = $answer;
                    break;
                case 1:
                    $answers["B"] = $answer;
                    break;
                case 2:
                    $answers["C"] = $answer;
                    break;
                case 3:
                    $answers["D"] = $answer;
                    break;
            }
        }

        $this->assign("info", $info);
        $this->assign("answers", $answers);

        $this->assign('page', input('page', 1));
        $this->assign('difficultyLevel', input('difficultyLevel', ''));

        return $this->fetch("editSingleChoice");
    }

    public function editSingleChoicePost()
    {
        $id = input('param.id');
        $info = $this->singleChoiceService->findById($id);

        $answers = [];
        if (trim(input("answerA")) !== "") {
            $answers[] = trim(input("answerA"));
        }
        if (trim(input("answerB")) !== "") {
            $answers[] = trim(input("answerB"));
        }
        if (trim(input("answerC")) !== "") {
            $answers[] = trim(input("answerC"));
        }
        if (trim(input("answerD")) !== "") {
            $answers[] = trim(input("answerD"));
        }

        $updateData = [
            "question" => input("question"),
            "possible_answers" => json_encode($answers, JSON_UNESCAPED_UNICODE),
            "answer" => trim(input("answer")),
            "difficulty_level" => input("difficulty_level"),
            "update_time" => time(),
        ];
        $this->singleChoiceService->updateByIdAndData($id, $updateData);

        if ($info["difficulty_level"] != $updateData["difficulty_level"]) {
            $redis = Redis::factory();
            removeSingleChoice($info["uuid"], $info["difficulty_level"], $redis);
            if ($info["is_use"] == QuestionIsUseEnum::YES) {
                addSingleChoice($info["uuid"], $updateData["difficulty_level"], $redis);
            }
        }

        $page = input("page",1);
        $difficultyLevel = input('difficultyLevel', '');
        $this->success("修改成功",url("singleChoiceList?page=$page&difficulty_level=$difficultyLevel"));
    }

    public function deleteSingleChoice()
    {
        $id = input('param.id');
        $info = $this->singleChoiceService->findById($id);
        $redis = Redis::factory();
        removeSingleChoice($info["uuid"], $info["difficulty_level"], $redis);

        $updateData = [
            "is_delete" => DbIsDeleteEnum::YES,
            "update_time" => time(),
        ];
        $this->singleChoiceService->updateByIdAndData($id, $updateData);

        $page = input("page",1);
        $difficultyLevel = input('difficultyLevel', '');
        $this->success("删除成功", url("singleChoiceList?page=$page&difficulty_level=$difficultyLevel"));
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

        $countArr = [
            "one" => 0,
            "two" => 0,
            "three" => 0,
            "four" => 0,
            "five" => 0,
            "six" => 0,
        ];
        $allDifficultyLevelCount = $this->writingLibraryService->allDifficultyLevelCount();
        foreach ($allDifficultyLevelCount as $item) {
            switch ($item["difficulty_level"]) {
                case "1":
                    $countArr["one"] = $item["total"];
                    break;
                case "2":
                    $countArr["two"] = $item["total"];
                    break;
                case "3":
                    $countArr["three"] = $item["total"];
                    break;
                case "4":
                    $countArr["four"] = $item["total"];
                    break;
                case "5":
                    $countArr["five"] = $item["total"];
                    break;
                case "6":
                    $countArr["six"] = $item["total"];
                    break;
            }
        }

        $this->assign("allCount", $countArr);

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

    public function editWriting()
    {
        $id = input('param.id');

        $info = $this->writingLibraryService->findById($id);
        $requirementsArr = json_decode($info["requirements"], true);

        $requirements = [];
        foreach ($requirementsArr as $item) {
            $requirements[]["requirement"] = $item;
        }

        $this->assign("info", $info);
        $this->assign("requirements", json_encode($requirements));

        $this->assign('page', input('page', 1));
        $this->assign('difficultyLevel', input('difficultyLevel', ''));

        return $this->fetch("editWriting");
    }

    public function editWritingPost()
    {
        $id = input('param.id');

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

        $info = $this->writingLibraryService->findById($id);

        $updateData = [
            "topic" => $topic,
            "requirements" => json_encode($requirementsData, JSON_UNESCAPED_UNICODE),
            "difficulty_level" => $difficultyLevel,
            "update_time" => time(),
        ];
        $this->writingLibraryService->updateByIdAndData($id, $updateData);

        if ($info["difficulty_level"] != $updateData["difficulty_level"]) {
            $redis = Redis::factory();
            removeWriting($info["uuid"], $info["difficulty_level"], $redis);
            if ($info["is_use"] == QuestionIsUseEnum::YES) {
                addWriting($info["uuid"], $updateData["difficulty_level"], $redis);
            }
        }

        $this->success("修改成功");
    }

    public function deleteWriting()
    {
        $id = input('param.id');
        $info = $this->writingLibraryService->findById($id);
        $redis = Redis::factory();
        removeWriting($info["uuid"], $info["difficulty_level"], $redis);

        $updateData = [
            "is_delete" => DbIsDeleteEnum::YES,
            "update_time" => time(),
        ];
        $this->writingLibraryService->updateByIdAndData($id, $updateData);

        $page = input("page",1);
        $difficultyLevel = input('difficultyLevel', '');
        $this->success("删除成功", url("writingList?page=$page&difficulty_level=$difficultyLevel"));
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

        $countArr = [
            "one" => 0,
            "two" => 0,
            "three" => 0,
            "four" => 0,
            "five" => 0,
            "six" => 0,
        ];
        $allDifficultyLevelCount = $this->trueFalseQuestionService->allDifficultyLevelCount();
        foreach ($allDifficultyLevelCount as $item) {
            switch ($item["difficulty_level"]) {
                case "1":
                    $countArr["one"] = $item["total"];
                    break;
                case "2":
                    $countArr["two"] = $item["total"];
                    break;
                case "3":
                    $countArr["three"] = $item["total"];
                    break;
                case "4":
                    $countArr["four"] = $item["total"];
                    break;
                case "5":
                    $countArr["five"] = $item["total"];
                    break;
                case "6":
                    $countArr["six"] = $item["total"];
                    break;
            }
        }

        $this->assign("allCount", $countArr);

        return $this->fetch("trueFalseQuestionList");
    }

    //添加判断题页面
    public function addTrueFalseQuestionList()
    {
        return $this->fetch("addTrueFalseQuestionList");
    }

    //执行添加判断题
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
                "is_use" => $trueFalseQuestion["is_use"],
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

    public function editTrueFalseQuestion()
    {
        $id = input('param.id');

        $this->assign("info",$this->trueFalseQuestionService->findById($id));

        $this->assign('page', input('page', 1));
        $this->assign('difficultyLevel', input('difficultyLevel', ''));

        return $this->fetch("editTrueFalseQuestion");
    }

    public function editTrueFalseQuestionPost()
    {
        $id = input('param.id');
        $info = $this->trueFalseQuestionService->findById($id);

        $updateData = [
            "question" => input("question"),
            "answer" => trim(input("answer")),
            "difficulty_level" => input("difficulty_level"),
            "update_time" => time(),
        ];
        $this->trueFalseQuestionService->updateByIdAndData($id, $updateData);

        if ($info["difficulty_level"] != $updateData["difficulty_level"]) {
            $redis = Redis::factory();
            removeTrueFalseQuestion($info["uuid"], $info["difficulty_level"], $redis);
            if ($info["is_use"] == QuestionIsUseEnum::YES) {
                addTrueFalseQuestion($info["uuid"], $updateData["difficulty_level"], $redis);
            }
        }

        $page = input("page",1);
        $difficultyLevel = input('difficultyLevel', '');
        $this->success("修改成功",url("trueFalseQuestionList?page=$page&difficulty_level=$difficultyLevel"));
    }

    public function deleteTrueFalseQuestion()
    {
        $id = input('param.id');
        $info = $this->trueFalseQuestionService->findById($id);
        $redis = Redis::factory();
        removeTrueFalseQuestion($info["uuid"], $info["difficulty_level"], $redis);

        $updateData = [
            "is_delete" => DbIsDeleteEnum::YES,
            "update_time" => time(),
        ];
        $this->trueFalseQuestionService->updateByIdAndData($id, $updateData);

        $page = input("page",1);
        $difficultyLevel = input('difficultyLevel', '');
        $this->success("删除成功", url("trueFalseQuestionList?page=$page&difficulty_level=$difficultyLevel"));
    }

    //操作题库缓存
    public function operateLibraryCache()
    {

        $libraryType = input("type");
        $uuid = input("uuid");
        $difficultyLevel = input("difficulty_level");
        $do = input("do");

        //跳转参数
        $page = input("page",1);
        $difficulty_level = input('difficultyLevel', '');

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
                $this->redirect("fillTheBlanksList?page=$page&difficulty_level=$difficulty_level");
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
                $this->redirect("singleChoiceList?page=$page&difficulty_level=$difficulty_level");
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
                $this->redirect("trueFalseQuestionList?page=$page&difficulty_level=$difficulty_level");
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
                $this->redirect("writingList?page=$page&difficulty_level=$difficulty_level");
                break;

        }


    }

}