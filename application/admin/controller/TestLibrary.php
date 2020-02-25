<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-02-23
 * Time: 16:44
 */

namespace app\admin\controller;

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

                case "name":
                    $whereSql .= " and name LIKE '%".$value."%'";
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
            if ($fillTheBlanks["question"] == "" || $fillTheBlanks["answer"] == "") {
                continue;
            }
            $data[] = [
                "uuid" => createUuid(),
                "question" => $fillTheBlanks["question"],
                "answer" => $fillTheBlanks["answer"],
                "create_time" => $time,
                "update_time" => $time,
            ];
        }

        if ($data) {
            Db::name("fill_the_blanks")->insertAll($data);
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
            if ($singleChoice["question"] == "" || !in_array($singleChoice["answer"], ["A","B","C","D"])) {
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
                "create_time" => $time,
                "update_time" => $time,
            ];
        }

        if ($data) {
            Db::name("single_choice")->insertAll($data);
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
        if ($topic === "") {
            $this->error('题目不能为空');
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
            "create_time" => $time,
            "update_time" => $time,
        ];

        Db::name("writing")->insert($writingData);

        $this->success("添加成功");
    }

}