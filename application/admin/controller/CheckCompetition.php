<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-31
 * Time: 17:59
 */

namespace app\admin\controller;

use app\common\enum\InternalCompetitionJoinIsCommentEnum;

class CheckCompetition extends Base
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
                    $whereSql .= " and u.invite_code = '$value' ";
                    break;
                case "c_uuid":
                    $whereSql .= " and icj.c_uuid = '$value' ";
                    break;
                case "is_comment":
                    $whereSql .= " and icj.is_submit_answer = 1 and icj.is_comment = $value ";
                    break;

            }

            $pageMap[$key] = $value;
            $this->assign($key, $value);

        }

        $data["whereSql"] = $whereSql;
        $data["pageMap"] = $pageMap;

        return $data;
    }

    public function index()
    {
        $condition = $this->convertRequestToWhereSql();
        $list = $this->internalCompetitionJoinService->getListByCondition($condition);
        $this->assign('list', $list);

        foreach ($list as $item) {
            $item["submit_answer_time"] = $item["is_submit_answer"]?
                date("Y-m-d H:i:s", $item["submit_answer_time"]):"未提交";
            $item["comment_time"] = $item["is_comment"]?date("Y-m-d H:i:s", $item["comment_time"]):"--";
            $item["score"] = $item["is_comment"]?$item["score"]:"--";
            $item["is_comment_desc"] = $item["is_submit_answer"]?($item["is_comment"]?"已批改":"未批改"):"--";
        }

        $internalCompetitionJoinIsComment = InternalCompetitionJoinIsCommentEnum::getAllList();
        $this->assign("internalCompetitionJoinIsComment", $internalCompetitionJoinIsComment);

        return $this->fetch();
    }

    public function check()
    {
        $uuid = input("uuid");
        $info = $this->internalCompetitionJoinService->findByMap(["uuid"=>$uuid]);
        if ($info == null) {
            $this->error('数据不存在');
        }

        $info = $info->toArray();
        $question = json_decode($info["question"], true);
        $topic = $question["topic"];
        $requirements = [];
        foreach ($question["requirements"] as $item) {
            $requirements[]["requirement"] = $item;
        }
        $answer = json_decode($info["answer"], true);
        $image = $answer["image"]??[];
        $text = $answer["text"]??[];

        $this->assign("info", $info);
        $this->assign("topic", $topic);
        $this->assign("requirements", json_encode($requirements));
        $this->assign("image", $image);
        $this->assign("text", $text);

        return $this->fetch();
    }

    public function doCheck()
    {
        $param = input();
        if (!isset($param["score"]) || !is_numeric($param["score"]) || $param["score"] < 0 || $param["score"] > 100) {
            $this->error("分值范围错误");
        }
        $param["score"] = bcadd($param["score"], 0, 2);

        $internalCompetitionJoin = $this->internalCompetitionJoinService->findByMap(["uuid"=>$param["uuid"]]);
        if ($internalCompetitionJoin == null) {
            $this->error("参与纪录不存在");
        }

        //前三名的分数不允许有重复
        $highScore = $this->internalCompetitionJoinService->getTopThreeScoreInfo($internalCompetitionJoin["c_uuid"])
            ->toArray();

        foreach ($highScore as $item) {
            if (bccomp($param["score"], $item["score"]) == 0) {
                $this->error("存在相同的高分");
            }
        }

        $internalCompetitionJoin->is_comment = InternalCompetitionJoinIsCommentEnum::YES;
        $internalCompetitionJoin->score = $param["score"];
        $internalCompetitionJoin->comment_time = time();
        $internalCompetitionJoin->save();

        $this->success("批改成功",url("index"));
    }
}