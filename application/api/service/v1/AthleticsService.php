<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-23
 * Time: 17:24
 */

namespace app\api\service\v1;

use app\api\service\Base;
use app\common\AppException;
use app\common\Constant;
use app\common\enum\CompetitionAnswerIsSubmitEnum;
use app\common\enum\InternalCompetitionIsFinishEnum;
use app\common\enum\InternalCompetitionStatusEnum;
use app\common\enum\PkIsInitiatorEnum;
use app\common\enum\PkStatusEnum;
use app\common\enum\PkTypeEnum;
use app\common\enum\QuestionDifficultyLevelEnum;
use app\common\enum\QuestionTypeEnum;
use app\common\enum\UserCoinAddTypeEnum;
use app\common\enum\UserCoinReduceTypeEnum;
use app\common\enum\UserPkCoinAddTypeEnum;
use app\common\enum\UserTalentCoinAddTypeEnum;
use app\common\helper\Redis;
use app\common\model\InternalCompetitionJoinModel;
use app\common\model\InternalCompetitionModel;
use app\common\model\InternalCompetitionRankModel;
use app\common\model\PkJoinModel;
use app\common\model\PkModel;
use app\common\model\SingleChoiceModel;
use app\common\model\UserBaseModel;
use app\common\model\UserCoinLogModel;
use app\common\model\UserPkCoinLogModel;
use app\common\model\UserSynthesizeModel;
use app\common\model\UserTalentCoinLogModel;
use think\Db;

class AthleticsService extends Base
{
    public function synthesizeReportCardList($user, $difficultyLevel, $pageNum, $pageSize)
    {
        $userSynthesizeModel = new UserSynthesizeModel();
        $userSynthesizeList = $userSynthesizeModel->synthesizeReportCardList($user["uuid"], $difficultyLevel, $pageNum, $pageSize);

        $returnData = [];
        foreach ($userSynthesizeList as $item) {
            $returnData[] = [
                "finish_time" => date("Y-m-d H:i:s", $item["finish_time"]),
                "score" => (int) $item["score"],
            ];
        }

        return $returnData;
    }

    public function initPk($userUuid, $pkType, $durationHour, $totalNum, $name)
    {
        $redis = Redis::factory();
        $userCoinLogModel = new UserCoinLogModel();

        //发起pk需要消耗书币计算  判断用户书币数量是否足够
        //参与pk需要消耗书币计算
        //生成题目
        //pk命名
        switch ($pkType) {
            case PkTypeEnum::NOVICE:
                $initPayCoin = Constant::PK_NOVICE_INIT_COIN;
                $joinPayCoin = Constant::PK_NOVICE_JOIN_COIN;
                $questions = $this->getNovicePkQuestions($redis);
                $pkName = $name . "新秀杯";
                break;
            case PkTypeEnum::SIMPLE:
                $initPayCoin = Constant::PK_SIMPLE_INIT_COIN;
                $joinPayCoin = Constant::PK_SIMPLE_JOIN_COIN;
                $questions = $this->getSimplePkQuestions($redis);
                $pkName = $name . "入门杯";
                break;
            case PkTypeEnum::DIFFICULTY:
                $initPayCoin = Constant::PK_DIFFICULTY_INIT_COIN;
                $joinPayCoin = Constant::PK_DIFFICULTY_JOIN_COIN;
                $questions = $this->getDifficultyPkQuestions($redis);
                $pkName = $name . "实力杯";
                break;
            case PkTypeEnum::GOD:
                $initPayCoin = Constant::PK_GOD_INIT_COIN;
                $joinPayCoin = Constant::PK_GOD_JOIN_COIN;
                $questions = $this->getGodPkQuestions($redis);
                $pkName = $name . "大师杯";
                break;
            default:
                throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        Db::startTrans();
        try {
            //判断用户书币
            $userSql = "select * from user_base where uuid = '$userUuid' for update";
            $userQuery = Db::query($userSql);
            if (!isset($userQuery[0])) {
                throw AppException::factory(AppException::USER_NOT_EXISTS);
            } else {
                $user = $userQuery[0];
            }
            if ($user["coin"] < $initPayCoin) {
                throw AppException::factory(AppException::USER_COIN_NOT_ENOUGH);
            }

            //发起pk
            $pkData = [
                "uuid" => getRandomString(),
                "name" => $pkName,
                "initiator_uuid" => $userUuid,
                "type" => $pkType,
                "status" => PkStatusEnum::AUDITING,
                "questions" => $questions,
                "total_num" => $totalNum,
                "current_num" => 1,
                "need_num" => $totalNum - 1,
                "duration_hour" => $durationHour,
                "join_coin" => $joinPayCoin,
                "total_coin" => $initPayCoin,
                "create_time" => time(),
                "update_time" => time(),
            ];
            Db::name("pk")->insert($pkData);

            //参与pk纪录
            $joinPk = [
                "uuid" => getRandomString(),
                "pk_uuid" => $pkData["uuid"],
                "user_uuid" => $userUuid,
                "is_initiator" => PkIsInitiatorEnum::YES,
                "coin" => $initPayCoin,
                "create_time" => time(),
                "update_time" => time(),
            ];
            Db::name("pk_join")->insert($joinPk);

            //减少用户书币
            Db::name("user_base")->where("uuid", $userUuid)
                ->dec("coin", $initPayCoin)->update(["update_time"=>time()]);

            //纪录书币流水
            $userCoinLogModel->recordReduceLog(
                $userUuid,
                UserCoinReduceTypeEnum::INIT_PK,
                $initPayCoin,
                $user["coin"],
                $user["coin"] - $initPayCoin,
                UserCoinReduceTypeEnum::INIT_PK_DSC,
                $pkData["uuid"]);

            Db::commit();

            //缓存用户信息
            $user["coin"] -= $initPayCoin;
            cacheUserInfoByToken($user, $redis);

        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }

        return [
            "uuid" => $pkData["uuid"],
        ];
    }

    public function joinPk($userUuid, $pkUuid)
    {
        $redis = Redis::factory();
        $userCoinLogModel = new UserCoinLogModel();
        $pkJoinModel = new PkJoinModel();

        //用户是否已参与pk
        if ($pkJoinModel->findByUserAndPk($userUuid, $pkUuid)) {
            throw AppException::factory(AppException::PK_JOIN_ALREADY);
        }

        Db::startTrans();
        try {
            $pkSql = "select * from pk where uuid = '$pkUuid' for update";
            $pkQuery = Db::query($pkSql);
            if (!isset($pkQuery[0])) {
                throw AppException::factory(AppException::PK_NOT_EXISTS);
            } else {
                $pk = $pkQuery[0];
            }
            if ($pk["need_num"] == 0) {
                //人数已满
                throw AppException::factory(AppException::PK_PEOPLE_ENOUGH);
            } else if ($pk["status"] != PkStatusEnum::WAIT_JOIN || $pk["audit_time"] + Constant::PK_WAIT_JOIN_TIME < time()) {
                //pk不是待加入状态，或已超时
                throw AppException::factory(AppException::PK_STATUS_NOT_WAIT_JOIN);
            }

            //判断用户书币
            $userSql = "select * from user_base where uuid = '$userUuid' for update";
            $userQuery = Db::query($userSql);
            if (!isset($userQuery[0])) {
                throw AppException::factory(AppException::USER_NOT_EXISTS);
            } else {
                $user = $userQuery[0];
            }
            if ($user["coin"] < $pk["join_coin"]) {
                throw AppException::factory(AppException::USER_COIN_NOT_ENOUGH);
            }

            //减少用户书币
            Db::name("user_base")->where("uuid", $userUuid)
                ->dec("coin", $pk["join_coin"])->update(["update_time"=>time()]);

            //纪录书币流水
            $userCoinLogModel->recordReduceLog(
                $userUuid,
                UserCoinReduceTypeEnum::JOIN_PK,
                $pk["join_coin"],
                $user["coin"],
                $user["coin"] - $pk["join_coin"],
                UserCoinReduceTypeEnum::JOIN_PK_DESC,
                $pkUuid);

            //修改pk状态
            $pkExec = Db::name("pk")->where("uuid", $pkUuid)
                ->inc("current_num", 1)
                ->inc("total_coin", $pk["join_coin"])
                ->dec("need_num", 1);
            if ($pk["need_num"] == 1) {
                //人数已满，pk赛开始
                $pkUpdateData = [
                    "status" => PkStatusEnum::UNDERWAY,
                    "begin_time" => time(),
                    "deadline" => time() + ($pk["duration_hour"] * 3600),
                    "update_time" => time(),
                ];
            } else {
                //任务依然不足，等待其他用户加入
                $pkUpdateData = [
                    "update_time" => time(),
                ];
            }
            $pkExec->update($pkUpdateData);

            //参与pk
            $joinPk = [
                "uuid" => getRandomString(),
                "pk_uuid" => $pkUuid,
                "user_uuid" => $userUuid,
                "is_initiator" => PkIsInitiatorEnum::NO,
                "coin" => $pk["join_coin"],
                "create_time" => time(),
                "update_time" => time(),
            ];
            Db::name("pk_join")->insert($joinPk);

            Db::commit();

            //修改用户缓存
            $user["coin"] -= $pk["join_coin"];
            cacheUserInfoByToken($user, $redis);

        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }

        return new \stdClass();
    }

    public function pkList($user, $pkType, $pkStatus, $pageNum, $pageSize)
    {
        $pkModel = new PkModel();

        $pkList = $pkModel->getListByType($pkType, $pkStatus, $pageNum, $pageSize)->toArray();

        $returnData = [];
        if ($pkList) {
            $pkUuids = array_column($pkList, "uuid");
            $pkJoinModel = new PkJoinModel();
            $pkListUserInfo = $pkJoinModel->getListUserInfoByPkUuids($pkUuids);
            $pkUserInfo = [];
            $pkUserUuids = [];
            foreach ($pkListUserInfo as $item) {
                $pkUserInfo[$item["pk_uuid"]][] = [
                    "nickname" => getNickname($item["nickname"]),
                    "head_image_url" => getHeadImageUrl($item["head_image_url"])
                ];
                $pkUserUuids[$item["pk_uuid"]][] = $item["uuid"];
            }

            foreach ($pkList as $item) {
                $returnData[] = [
                    "uuid" => $item["uuid"],
                    "name" => $item["name"],
                    "total_num" => $item["total_num"],
                    "initiator_head_image_url" => $pkUserInfo[$item["uuid"]][0]["head_image_url"],
                    "initiator_nickname" => $pkUserInfo[$item["uuid"]][0]["nickname"],
                    "join_users" => $pkUserInfo[$item["uuid"]],
                    "type" => $item["type"],
                    "status" => $item["status"],
                    "is_join" => (int) in_array($user["uuid"], $pkUserUuids[$item["uuid"]])
                ];
            }
        }

        return $returnData;
    }

    public function pkInfo($user, $pkUuid)
    {
        //获取pk
        $pkModel = new PkModel();
        $pk = $pkModel->findByUuid($pkUuid);
        if ($pk == null) {
            throw AppException::factory(AppException::PK_NOT_EXISTS);
        }

        //获取pk参与信息
        $pkJoinModel = new PkJoinModel();
        $pkJoinInfo = $pkJoinModel->getListUserInfoByPkUuid($pkUuid);

        //初始化返回信息
        $returnData = [
            "uuid" => $pk["uuid"],
            "name" => $pk["name"],
            "initiator_nickname" => $pkJoinInfo[0]["nickname"],
            "begin_time" => date("Y-m-d H:i", $pk["begin_time"]),
            "deadline" => date("Y-m-d H:i", $pk["deadline"]),
            "status" => $pk["status"],
            "type" => $pk["type"],
            "is_initiator" => 0,
            "is_join" => 0,
            "is_submit_answer" => 0,
            "audit_fail_reason" => $pk["audit_fail_reason"],
            "champion_nickname" => "",
            "my_performance" => "",
        ];

        $questions = json_decode($pk["questions"], true);
        foreach ($questions as $question) {
            $returnData["questions"][] = [
                "uuid" => $question["uuid"],
                "question" => $question["question"],
                "possible_answers" => $question["possible_answers"],
            ];
        }

        foreach ($pkJoinInfo as $item) {
            $returnData["users"][] = [
                "nickname" => $item["nickname"],
                "head_image_url" => getImageUrl($item["head_image_url"]),
            ];

            if ($item["user_uuid"] == $user["uuid"]) {
                $returnData["is_join"] = 1;
                if ($item["is_initiator"] == PkIsInitiatorEnum::YES) {
                    $returnData["is_initiator"] = 1;
                }
                if ($pk["status"] == PkStatusEnum::FINISH) {
                    $returnData["my_performance"] = "亲爱的学员 ".$item["nickname"]." 你好 你的成绩为 第".$item["rank"]."名，
                    请继续努力加油，快去PK榜看看你有没有上榜吧";
                }
            }
            if ($item["rank"] == 1) {
                $returnData["champion_nickname"] = $item["nickname"];
            }
        }

        return $returnData;
    }

    public function submitPkAnswer($user, $pkUuid, $pkAnswers, $answerTime)
    {
        $pkModel = new PkModel();
        $pkJoinModel = new PkJoinModel();

        //只有pk处于进行中事，才可提交答案
        $pk = $pkModel->findByUuid($pkUuid);
        if ($pk == null) {
            throw AppException::factory(AppException::PK_NOT_EXISTS);
        }
        if ($pk["status"] != PkStatusEnum::UNDERWAY || $pk["deadline"] < time()) {
            throw AppException::factory(AppException::PK_STATUS_NOT_UNDERWAY);
        }

        //用户参与pk且还没有提交答案时才可提交答案
        $pkJoin = $pkJoinModel->findByUserAndPk($user["uuid"], $pkUuid);
        if ($pkJoin == null) {
            throw AppException::factory(AppException::PK_NOT_JOIN);
        }
        if ($pkJoin["answers"] != "") {
            throw AppException::factory(AppException::PK_SUBMIT_ANSWERS_ALREADY);
        }

        //计算分数，每题一分
        $questionsArray = json_decode($pk["questions"], true);
        $score = 0;
        $pkAnswersArray = array_column($pkAnswers, "answer", "uuid");
        foreach ($questionsArray as $item) {
            if (isset($pkAnswersArray[$item["uuid"]]) && $pkAnswersArray[$item["uuid"]] == $item["answer"]) {
                $score++;
            }
        }

        //纪录用户答题情况
        $pkJoin->answers = json_encode($pkAnswers, JSON_UNESCAPED_UNICODE);
        $pkJoin->submit_answer_time = time();
        $pkJoin->answer_time = $answerTime;
        $pkJoin->score = $score;
        $pkJoin->save();

        //如果用户都答题完成，结算PK
        $returnData = new \stdClass();
        $pkJoins = $pkJoinModel->getJoinInfoByPkUuid($pkUuid);
        foreach ($pkJoins as $item) {
            if ($item["answers"] == "") {
                return $returnData;
            }
        }
        $redis = Redis::factory();
        pushPkFinishList($pkUuid, $redis);
        return $returnData;
    }

    public function pkReportCard($user, $pageNum, $pageSize)
    {
        $pkJoinModel = new PkJoinModel();

        $pkList = $pkJoinModel->pkReportCard($user["uuid"], $pageNum, $pageSize);

        $returnData = [];
        if ($pkList) {
            foreach ($pkList as $item) {
                $returnData[] = [
                    "uuid" => $item["uuid"],
                    "name" => $item["name"],
                    "join_time" => date("Y-m-d", strtotime($item["create_time"])),
                    "rank" => $item["rank"],
                    "initiator_head_image_url" => getHeadImageUrl($item["head_image_url"]),
                    "initiator_nickname" => getNickname($item["nickname"]),

                ];
            }
        }

        return $returnData;
    }

    public function myInitPk($user, $pageNum, $pageSize)
    {
        $pkModel = new PkModel();

        $pkList = $pkModel->myInitList($user["uuid"], $pageNum, $pageSize);

        $returnData = [];
        if ($pkList) {
            $pkUuids = array_column($pkList, "uuid");
            $pkJoinModel = new PkJoinModel();
            $pkListUserInfo = $pkJoinModel->getListUserInfoByPkUuids($pkUuids);
            $pkUserInfo = [];
            $pkUserUuids = [];
            foreach ($pkListUserInfo as $item) {
                $pkUserInfo[$item["pk_uuid"]][] = [
                    "nickname" => getNickname($item["nickname"]),
                    "head_image_url" => getHeadImageUrl($item["head_image_url"])
                ];
                $pkUserUuids[$item["pk_uuid"]][] = $item["uuid"];
            }

            foreach ($pkList as $item) {
                $returnData[] = [
                    "uuid" => $item["uuid"],
                    "name" => $item["name"],
                    "initiator_head_image_url" => $pkUserInfo[$item["uuid"]][0]["head_image_url"],
                    "initiator_nickname" => $pkUserInfo[$item["uuid"]][0]["nickname"],
                    "join_users" => $pkUserInfo[$item["uuid"]],
                    "type" => $item["type"],
                    "status" => $item["status"],
                ];
            }
        }

        return $returnData;
    }

    public function myJointPk($user, $pageNum, $pageSize)
    {
        $pkJoinModel = new PkJoinModel();

        $pkList = $pkJoinModel->myJointPk($user["uuid"], $pageNum, $pageSize);

        $returnData = [];
        if ($pkList) {
            $pkUuids = array_column($pkList, "uuid");
            $pkListUserInfo = $pkJoinModel->getListUserInfoByPkUuids($pkUuids);
            $pkUserInfo = [];
            $pkUserUuids = [];
            foreach ($pkListUserInfo as $item) {
                $pkUserInfo[$item["pk_uuid"]][] = [
                    "nickname" => getNickname($item["nickname"]),
                    "head_image_url" => getHeadImageUrl($item["head_image_url"])
                ];
                $pkUserUuids[$item["pk_uuid"]][] = $item["uuid"];
            }

            foreach ($pkList as $item) {
                $returnData[] = [
                    "uuid" => $item["uuid"],
                    "name" => $item["name"],
                    "initiator_head_image_url" => $pkUserInfo[$item["uuid"]][0]["head_image_url"],
                    "initiator_nickname" => $pkUserInfo[$item["uuid"]][0]["nickname"],
                    "join_users" => $pkUserInfo[$item["uuid"]],
                    "type" => $item["type"],
                    "status" => $item["status"],
                ];
            }
        }

        return $returnData;
    }

    public function competitionList($user, $pageNum, $pageSize)
    {
        $internalCompetitionModel = new InternalCompetitionModel();
        $internalCompetitions = $internalCompetitionModel->getList($pageNum, $pageSize);

        $returnData = [];
        foreach ($internalCompetitions as $item) {
            $returnData[] = [
                "uuid" => $item["uuid"],
                "image_url" => getImageUrl($item["image_url"]),
                "name" => $item["name"],
                "status" => $this->getInternalCompetitionStatus($item),
            ];
        }

        return $returnData;
    }

    public function competitionInfo($user, $competitionUuid)
    {
        $internalCompetitionModel = new InternalCompetitionModel();
        $competition = $internalCompetitionModel->findByUuid($competitionUuid);
        if ($competition == null) {
            throw AppException::factory(AppException::INTERNAL_COMPETITION_NOT_EXISTS);
        }

        if ($competition["user_level_floor"] == 0) {
            $requirement = "所有学员均可参加";
        } else {
            $requirement = $competition["user_level_floor"]."星及以上学员均可参加";
        }

        $returnData = [
            "uuid" => $competitionUuid,
            "status" => $this->getInternalCompetitionStatus($competition),
            "image_url" => getImageUrl($competition["image_url"]),
            "name" => $competition["name"],
            "description" => $competition["description"],
            "rules" => [
                "报名时间：".date("Y-m-d", $competition["online_time"])."~".
                date("Y-m-d", $competition["apply_deadline"]),
                "提交作品时间：报名后一小时内",
                "参赛要求：".$requirement,
                "参赛形式：大赛入口按要求上传作品、可在线作答或者拍照纸质作品。（范文及抄袭按作弊处理）"
            ],
            "user_level_floor" => $competition["user_level_floor"],
            "is_join" => 0,
            "question" => null,
            "is_submit_answer" => 0,
            "submit_answer_ttl" => 0,
        ];

        $internalCompetitionJoinModel = new InternalCompetitionJoinModel();
        $competitionJoin = $internalCompetitionJoinModel->findByUserAndCompetition($user["uuid"], $competitionUuid);
        if ($competitionJoin != null) {
            $ttl = 3600 - (time() - strtotime($competitionJoin["create_time"]));
            $ttl = $ttl<=0?0:$ttl;
            $returnData["is_join"] = 1;
            $returnData["is_submit_answer"] = $competitionJoin["is_submit_answer"];
            $returnData["question"] = json_decode($competitionJoin["question"]);
            $returnData["submit_answer_ttl"] = $ttl;
        }

        return $returnData;
    }

    public function joinCompetition($user, $competitionUuid)
    {
        $internalCompetitionModel = new InternalCompetitionModel();
        $competition = $internalCompetitionModel->findByUuid($competitionUuid);
        if ($competition == null) {
            throw AppException::factory(AppException::INTERNAL_COMPETITION_NOT_EXISTS);
        }

        //用户不能重复参与大赛
        $internalCompetitionJoinModel = new InternalCompetitionJoinModel();
        $competitionJoin = $internalCompetitionJoinModel->findByUserAndCompetition($user["uuid"], $competitionUuid);
        if ($competitionJoin != null) {
            throw AppException::factory(AppException::INTERNAL_COMPETITION_JOIN_ALREADY);
        }

        //大赛在报名阶段才能报名
        $competitionStatus = $this->getInternalCompetitionStatus($competition);
        if ($competitionStatus != InternalCompetitionStatusEnum::APPLYING) {
            throw AppException::factory(AppException::INTERNAL_COMPETITION_STATUS_NOT_APPLYING);
        }

        //三星以上用户才能参赛
        $userModel = new UserBaseModel();
        $user = $userModel->findByUuid($user["uuid"]);
        if ($user == null) {
            throw AppException::factory(AppException::USER_NOT_EXISTS);
        } else if ($user["level"] < $competition["user_level_floor"]) {
            throw AppException::factory(AppException::INTERNAL_COMPETITION_USER_LEVEL_LOW);
        }

        //为用户随机选择题目
        $allQuestion = json_decode($competition["question"], true);
        $randomKey = random_int(0, count($allQuestion) - 1);
        $randomQuestion = $allQuestion[$randomKey];
        $userCoinLogModel = new UserCoinLogModel();
        $userPkCoinLogModel = new UserPkCoinLogModel();
        $userTalentCoinLogModel = new UserTalentCoinLogModel();
        $internalCompetitionRankModel = new InternalCompetitionRankModel();

        Db::startTrans();
        try {
            //添加参与纪录
            $joinData = [
                "uuid" => getRandomString(),
                "c_uuid" => $competitionUuid,
                "user_uuid" => $user["uuid"],
                "question" => json_encode($randomQuestion, JSON_UNESCAPED_UNICODE),
            ];
            $internalCompetitionJoinModel->save($joinData);

            //大赛参与人数+1
            $internalCompetitionModel->where("uuid", $competitionUuid)
                ->inc("join_num", 1)
                ->update(["update_time"=>time()]);

            //参赛学生可获得 10DE，10PK值,1 才情值
            $userModel->where("uuid", $user["uuid"])
                ->inc("coin", Constant::JOIN_INTERNAL_COMPETITION_REWARD["coin"])
                ->inc("pk_coin", Constant::JOIN_INTERNAL_COMPETITION_REWARD["pk_coin"])
                ->inc("talent_coin", Constant::JOIN_INTERNAL_COMPETITION_REWARD["talent_coin"])
                ->update(["update_time"=>time()]);
            $newUser = $userModel->findByUuid($user["uuid"])->toArray();

            $userCoinLogModel->recordAddLog(
                $user["uuid"],
                UserCoinAddTypeEnum::JOIN_INTERNAL_COMPETITION,
                Constant::JOIN_INTERNAL_COMPETITION_REWARD["coin"],
                $newUser["coin"] - Constant::JOIN_INTERNAL_COMPETITION_REWARD["coin"],
                $newUser["coin"],
                UserCoinAddTypeEnum::JOIN_INTERNAL_COMPETITION_DESC,
                $competitionUuid);

            $userPkCoinLogModel->recordAddLog(
                $user["uuid"],
                UserPkCoinAddTypeEnum::JOIN_INTERNAL_COMPETITION,
                Constant::JOIN_INTERNAL_COMPETITION_REWARD["pk_coin"],
                $newUser["pk_coin"] - Constant::JOIN_INTERNAL_COMPETITION_REWARD["pk_coin"],
                $newUser["pk_coin"],
                UserPkCoinAddTypeEnum::JOIN_INTERNAL_COMPETITION_DESC,
                $competitionUuid
            );

            $userTalentCoinLogModel->recordAddLog(
                $user["uuid"],
                UserTalentCoinAddTypeEnum::JOIN_INTERNAL_COMPETITION,
                Constant::JOIN_INTERNAL_COMPETITION_REWARD["talent_coin"],
                $newUser["talent_coin"] - Constant::JOIN_INTERNAL_COMPETITION_REWARD["talent_coin"],
                $newUser["talent_coin"],
                UserTalentCoinAddTypeEnum::JOIN_INTERNAL_COMPETITION_DESC,
                $competitionUuid
            );

            //才情排行榜增加才情值
            $internalCompetitionRankModel->addTalentCoin($user["uuid"], Constant::JOIN_INTERNAL_COMPETITION_REWARD["talent_coin"]);

            Db::commit();

            //缓存用户信息
            $redis = Redis::factory();
            cacheUserInfoByToken($newUser, $redis);
            $returnData = $randomQuestion;
            $returnData["submit_answer_ttl"] = 3600;

            return $randomQuestion;
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    public function submitCompetitionAnswer($user, $competitionUuid, $answer, $isDraft = false)
    {
        $internalCompetitionModel = new InternalCompetitionModel();
        $competition = $internalCompetitionModel->findByUuid($competitionUuid);
        if ($competition == null) {
            throw AppException::factory(AppException::INTERNAL_COMPETITION_NOT_EXISTS);
        }

        //用户需参与了大赛
        //用户提交答案后不能再提交
        //不能超过提交答案截止时间
        //答题时间限制在一小时以内
        $internalCompetitionJoinModel = new InternalCompetitionJoinModel();
        $competitionJoin = $internalCompetitionJoinModel->findByUserAndCompetition($user["uuid"], $competitionUuid);
        if ($competitionJoin == null) {
            throw AppException::factory(AppException::INTERNAL_COMPETITION_NOT_JOIN);
        } else if ($competitionJoin["is_submit_answer"] == CompetitionAnswerIsSubmitEnum::YES) {
            throw AppException::factory(AppException::INTERNAL_COMPETITION_SUBMIT_ANSWER_ALREADY);
        } else if ($competition["submit_answer_deadline"] <= time()) {
            throw AppException::factory(AppException::INTERNAL_COMPETITION_SUBMIT_ANSWER_TIMEOUT);
        } else if (time() - strtotime($competitionJoin["create_time"]) > Constant::INTERNAL_COMPETITION_SUBMIT_ANSWER_TIME_LIMIT) {
            throw AppException::factory(AppException::INTERNAL_COMPETITION_SUBMIT_ANSWER_TIME_LIMIT);
        }

        //纪录用户答案
        $competitionJoin->answer = json_encode($answer, JSON_UNESCAPED_UNICODE);
        if ($isDraft == false) {
            $competitionJoin->is_submit_answer = CompetitionAnswerIsSubmitEnum::YES;
            $competitionJoin->submit_answer_time = time();
            $competitionJoin->submit_answer_second = time() - strtotime($competitionJoin->create_time);
        }
        $competitionJoin->save();

        return new \stdClass();
    }

    public function getInternalCompetitionStatus($competition)
    {
        if ($competition["is_finish"] == InternalCompetitionIsFinishEnum::YES) {
            $status = InternalCompetitionStatusEnum::FINISH;
        } else if ($competition["submit_answer_deadline"] <= time()) {
            $status = InternalCompetitionStatusEnum::SUBMIT_ANSWER_FINISH;
        } else if ($competition["apply_deadline"] <= time()) {
            $status = InternalCompetitionStatusEnum::UNDERWAY;
        } else {
            $status = InternalCompetitionStatusEnum::APPLYING;
        }

        return $status;
    }

    public function competitionReportCardList($user, $pageNum, $pageSize)
    {
        $internalCompetitionModel = new InternalCompetitionModel();
        $internalCompetitions = $internalCompetitionModel->competitionReportCard($pageNum, $pageSize);
        if (!$internalCompetitions) {
            return [];
        }

        $internalCompetitionUuids = array_column($internalCompetitions, "uuid");
        $internalCompetitionJoinModel = new InternalCompetitionJoinModel();
        $userJoinInternalCompetitions = $internalCompetitionJoinModel
            ->getByUserAndCompetitions($user["uuid"], $internalCompetitionUuids)->toArray();
        $userJoinInternalCompetitions = array_column($userJoinInternalCompetitions, null, "c_uuid");

        $returnData = [];
        foreach ($internalCompetitions as $item) {
            $isJoin = (int) isset($userJoinInternalCompetitions[$item["uuid"]]);
            $rank = $isJoin?$userJoinInternalCompetitions[$item["uuid"]]["rank"]:0;
            $returnData[] = [
                "uuid" => $item["uuid"],
                "image_url" => getImageUrl($item["image_url"]),
                "name" => $item["name"],
                "is_join" => $isJoin,
                "rank" => $rank,
            ];
        }

        return $returnData;
    }

    public function competitionReportCardInfo($user, $competitionUuid)
    {
        //大赛
        $internalCompetitionModel = new InternalCompetitionModel();
        $competition = $internalCompetitionModel->findByUuid($competitionUuid);
        if (empty($competition)) {
            throw AppException::factory(AppException::INTERNAL_COMPETITION_NOT_EXISTS);
        }

        //参与用户
        $internalCompetitionJoinModel = new InternalCompetitionJoinModel();
        $joinUsers = $internalCompetitionJoinModel->getJoinUserInfoList($competitionUuid, 1, 8);
        foreach ($joinUsers as $key=>$joinUser) {
            $joinUsers[$key]["nickname"] = getNickname($joinUser["nickname"]);
            $joinUsers[$key]["head_image_url"] = getHeadImageUrl($joinUser["head_image_url"]);
        }

        //成绩上榜用户
        $myDescription = "";
        if ($competition["is_finish"] == InternalCompetitionIsFinishEnum::NO) {
            $winUsers = [];
        } else {
            $redis = Redis::factory();
            $winUsers = getCompetitionWinUserInfo($competitionUuid, $redis);
            if (!is_array($winUsers)) {
                $winUsers = $internalCompetitionJoinModel->getWinUserInfoList($competitionUuid);
                cacheCompetitionWinUserInfo($competitionUuid, $winUsers, $redis);
            }
            foreach ($winUsers as $key=>$winUser) {
                if ($winUser["uuid"] == $user["uuid"]) {
                    $myDescription = "恭喜你在本次大赛中获得第{$winUser["rank"]}名的好成绩，快去分享给你的好友，邀请他一起加入";
                }
                $winUsers[$key]["nickname"] = getNickname($winUser["nickname"]);
                $winUsers[$key]["head_image_url"] = getHeadImageUrl($winUser["head_image_url"]);
            }
        }


        $returnData = [
            "uuid" => $competitionUuid,
            "description" => $competition["description"],
            "organizers" => $competition["organizers"],
            "join_users" => $joinUsers,
            "win_users" => $winUsers,
            "prize" => "奖励才情值等",
            "my_description" => $myDescription,
        ];

        return $returnData;
    }

    public function competitionReportCardUserList($user, $competitionUuid, $pageNum, $pageSize)
    {
        $internalCompetitionJoinModel = new InternalCompetitionJoinModel();
        $joinUsers = $internalCompetitionJoinModel->getJoinUserInfoList($competitionUuid, $pageNum, $pageSize);
        foreach ($joinUsers as $key=>$joinUser) {
            $joinUsers[$key]["nickname"] = getNickname($joinUser["nickname"]);
            $joinUsers[$key]["head_image_url"] = getHeadImageUrl($joinUser["head_image_url"]);
        }

        return $joinUsers;
    }

    private function getNovicePkQuestions($redis)
    {
        //新手难度的pk全部是1星题
        $singleChoiceModel = new SingleChoiceModel();
        $difficultyLevel = QuestionDifficultyLevelEnum::ONE;
        $questionCount = Constant::PK_QUESTION_COUNT;
        $questionsUuids = getRandomSingleChoice($difficultyLevel, $questionCount, $redis);

        if (count($questionsUuids) < $questionCount) {
            $questions = $singleChoiceModel->getRandom($difficultyLevel, $questionCount)->toArray();
            pushCacheQuestionLibraryList(QuestionTypeEnum::SINGLE_CHOICE, $difficultyLevel, $redis);
        } else {
            $questions = $singleChoiceModel->getByUuids($questionsUuids)->toArray();
            $questions = (new QuestionService())->questionOrderByUuid($questionsUuids, $questions);
        }

        $returnData = [];
        foreach ($questions as $item) {
            $returnData[] = [
                "uuid" => $item["uuid"],
                "question" => $item["question"],
                "possible_answers" => json_decode($item["possible_answers"], true),
                "answer" => $item["answer"],
            ];
        }
        return json_encode($returnData, JSON_UNESCAPED_UNICODE);
    }

    private function getSimplePkQuestions($redis)
    {
        //简单难度的pk 2星题和3星题各一半
        $singleChoiceModel = new SingleChoiceModel();
        $questionCount = Constant::PK_QUESTION_COUNT / 2;

        $twoStarQuestionUuids = getRandomSingleChoice(QuestionDifficultyLevelEnum::TWO, $questionCount, $redis);
        if (count($twoStarQuestionUuids) < $questionCount) {
            $twoStarQuestions = $singleChoiceModel->getRandom(QuestionDifficultyLevelEnum::TWO, $questionCount)->toArray();
            pushCacheQuestionLibraryList(QuestionTypeEnum::SINGLE_CHOICE, QuestionDifficultyLevelEnum::TWO, $redis);
        }
        $threeStarQuestionUuids = getRandomSingleChoice(QuestionDifficultyLevelEnum::THREE, $questionCount, $redis);
        if (count($threeStarQuestionUuids) < $questionCount) {
            $threeStarQuestions = $singleChoiceModel->getRandom(QuestionDifficultyLevelEnum::THREE, $questionCount)->toArray();
            pushCacheQuestionLibraryList(QuestionTypeEnum::SINGLE_CHOICE, QuestionDifficultyLevelEnum::THREE, $redis);
        }

        if (!isset($twoStarQuestions) && !isset($threeStarQuestions)) {
            $questionUuids = array_merge($twoStarQuestionUuids, $threeStarQuestionUuids);
            $questions = $singleChoiceModel->getByUuids($questionUuids)->toArray();
        } else if (!isset($twoStarQuestions) && isset($threeStarQuestions)) {
            $twoStarQuestions = $singleChoiceModel->getByUuids($twoStarQuestionUuids)->toArray();
            $questions = array_merge($twoStarQuestions, $threeStarQuestions);
        } else if (isset($twoStarQuestions) && !isset($threeStarQuestions)) {
            $threeStarQuestions = $singleChoiceModel->getByUuids($threeStarQuestionUuids)->toArray();
            $questions = array_merge($twoStarQuestions, $threeStarQuestions);
        } else {
            $questions = array_merge($twoStarQuestions, $threeStarQuestions);
        }

        $returnData = [];
        foreach ($questions as $item) {
            $returnData[] = [
                "uuid" => $item["uuid"],
                "question" => $item["question"],
                "possible_answers" => json_decode($item["possible_answers"], true),
                "answer" => $item["answer"],
            ];
        }
        //随机排序题目
        shuffle($returnData);
        return json_encode($returnData, JSON_UNESCAPED_UNICODE);
    }

    private function getDifficultyPkQuestions($redis)
    {
        //困难难度的pk 4星题和5星题各一半
        $singleChoiceModel = new SingleChoiceModel();
        $questionCount = Constant::PK_QUESTION_COUNT / 2;

        $fourStarQuestionUuids = getRandomSingleChoice(QuestionDifficultyLevelEnum::FOUR, $questionCount, $redis);
        if (count($fourStarQuestionUuids) < $questionCount) {
            $fourStarQuestions = $singleChoiceModel->getRandom(QuestionDifficultyLevelEnum::FOUR, $questionCount)->toArray();
            pushCacheQuestionLibraryList(QuestionTypeEnum::SINGLE_CHOICE, QuestionDifficultyLevelEnum::FOUR, $redis);
        }
        $fiveStarQuestionUuids = getRandomSingleChoice(QuestionDifficultyLevelEnum::FIVE, $questionCount, $redis);
        if (count($fiveStarQuestionUuids) < $questionCount) {
            $fiveStarQuestions = $singleChoiceModel->getRandom(QuestionDifficultyLevelEnum::FIVE, $questionCount)->toArray();
            pushCacheQuestionLibraryList(QuestionTypeEnum::SINGLE_CHOICE, QuestionDifficultyLevelEnum::FIVE, $redis);
        }

        if (!isset($fourStarQuestions) && !isset($fiveStarQuestions)) {
            $questionUuids = array_merge($fourStarQuestionUuids, $fiveStarQuestionUuids);
            $questions = $singleChoiceModel->getByUuids($questionUuids)->toArray();
        } else if (!isset($fourStarQuestions) && isset($fiveStarQuestions)) {
            $fourStarQuestions = $singleChoiceModel->getByUuids($fourStarQuestionUuids)->toArray();
            $questions = array_merge($fourStarQuestions, $fiveStarQuestions);
        } else if (isset($fourStarQuestions) && !isset($fiveStarQuestions)) {
            $fiveStarQuestions = $singleChoiceModel->getByUuids($fiveStarQuestionUuids)->toArray();
            $questions = array_merge($fourStarQuestions, $fiveStarQuestions);
        } else {
            $questions = array_merge($fourStarQuestions, $fiveStarQuestions);
        }

        $returnData = [];
        foreach ($questions as $item) {
            $returnData[] = [
                "uuid" => $item["uuid"],
                "question" => $item["question"],
                "possible_answers" => json_decode($item["possible_answers"], true),
                "answer" => $item["answer"],
            ];
        }
        //随机排序题目
        shuffle($returnData);
        return json_encode($returnData, JSON_UNESCAPED_UNICODE);
    }

    private function getGodPkQuestions($redis)
    {
        //超神难度的pk全部是6星题
        $singleChoiceModel = new SingleChoiceModel();
        $difficultyLevel = QuestionDifficultyLevelEnum::SIX;
        $questionCount = Constant::PK_QUESTION_COUNT;
        $questionsUuids = getRandomSingleChoice($difficultyLevel, $questionCount, $redis);

        if (count($questionsUuids) < $questionCount) {
            $questions = $singleChoiceModel->getRandom($difficultyLevel, $questionCount)->toArray();
            pushCacheQuestionLibraryList(QuestionTypeEnum::SINGLE_CHOICE, $difficultyLevel, $redis);
        } else {
            $questions = $singleChoiceModel->getByUuids($questionsUuids)->toArray();
            $questions = (new QuestionService())->questionOrderByUuid($questionsUuids, $questions);
        }

        $returnData = [];
        foreach ($questions as $item) {
            $returnData[] = [
                "uuid" => $item["uuid"],
                "question" => $item["question"],
                "possible_answers" => json_decode($item["possible_answers"], true),
                "answer" => $item["answer"],
            ];
        }
        return json_encode($returnData, JSON_UNESCAPED_UNICODE);
    }
}