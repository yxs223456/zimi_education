<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-31
 * Time: 17:11
 */

namespace app\admin\controller;

use app\common\enum\UserWritingIsCommentEnum;
use app\common\enum\UserWritingSourceTypeEnum;

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

                case "difficulty_level":
                    $whereSql .= " and difficulty_level = '$value'";
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
}