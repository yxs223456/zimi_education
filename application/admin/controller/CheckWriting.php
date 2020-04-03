<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-31
 * Time: 17:11
 */

namespace app\admin\controller;

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
}