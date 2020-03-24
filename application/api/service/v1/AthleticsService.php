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
use app\common\enum\PkIsInitiatorEnum;
use app\common\enum\PkStatusEnum;
use app\common\enum\PkTypeEnum;
use app\common\enum\QuestionDifficultyLevelEnum;
use app\common\enum\QuestionTypeEnum;
use app\common\enum\UserCoinReduceTypeEnum;
use app\common\helper\Redis;
use app\common\model\SingleChoiceModel;
use app\common\model\UserCoinLogModel;
use think\Db;

class AthleticsService extends Base
{
    public function initPk($userUuid, $pkType, $durationHour, $totalNum, $name)
    {
        $redis = Redis::factory();
        $userCoinLogModel = new UserCoinLogModel();

        //发起pk需要消耗书币计算  判断用户书币数量是否足够
        //生成题目
        //pk命名
        switch ($pkType) {
            case PkTypeEnum::NOVICE:
                $payCoin = Constant::PK_NOVICE_INIT_COIN;
                $questions = $this->getNovicePkQuestions($redis);
                $pkName = $name . "新秀杯";
                break;
            case PkTypeEnum::SIMPLE:
                $payCoin = Constant::PK_SIMPLE_INIT_COIN;
                $questions = $this->getSimplePkQuestions($redis);
                $pkName = $name . "入门杯";
                break;
            case PkTypeEnum::DIFFICULTY:
                $payCoin = Constant::PK_DIFFICULTY_INIT_COIN;
                $questions = $this->getDifficultyPkQuestions($redis);
                $pkName = $name . "实力杯";
                break;
            case PkTypeEnum::GOD:
                $payCoin = Constant::PK_GOD_INIT_COIN;
                $questions = $this->getGodPkQuestions($redis);
                $pkName = $name . "大师杯";
                break;
            default:
                throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        Db::startTrans();
        try {
            $userSql = "select * from user_base where uuid = '$userUuid' for update";
            $userQuery = Db::query($userSql);
            if (!isset($userQuery[0])) {
                throw AppException::factory(AppException::USER_NOT_EXISTS);
            } else {
                $user = $userQuery[0];
            }
            if ($user["coin"] < $payCoin) {
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
                "create_time" => time(),
                "update_time" => time(),
            ];
            Db::name("pk_join")->insert($joinPk);

            //减少用户书币
            Db::name("user_base")->where("uuid", $userUuid)
                ->dec("coin", $payCoin)->update(["update_time"=>time()]);

            //纪录书币流水
            $userCoinLogModel->recordReduceLog(
                $userUuid,
                UserCoinReduceTypeEnum::INIT_PK,
                $payCoin,
                $user["coin"],
                $user["coin"] - $payCoin,
                UserCoinReduceTypeEnum::INIT_PK_DSC,
                $pkData["uuid"]);

            Db::commit();

            //缓存用户信息
            $user["coin"] -= $payCoin;
            cacheUserInfoByToken($user, $redis);

        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }

        return [
            "uuid" => $pkData["uuid"],
        ];
    }

    private function getNovicePkQuestions($redis)
    {
        //新手难度的pk全部是1星题
        $singleChoiceModel = new SingleChoiceModel();
        $difficultyLevel = QuestionDifficultyLevelEnum::ONE;
        $questionCount = Constant::PK_QUESTION_COUNT;
        $questionsUuids = getRandomSingleChoice($difficultyLevel, $questionCount, $redis);

        if (count($questionsUuids) < $questionCount) {
            $questions = $singleChoiceModel->getRandom($difficultyLevel, $questionCount);
            pushCacheQuestionLibraryList(QuestionTypeEnum::SINGLE_CHOICE, $difficultyLevel, $redis);
        } else {
            $questions = $singleChoiceModel->getByUuids($questionsUuids);
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
            $twoStarQuestions = $singleChoiceModel->getRandom(QuestionDifficultyLevelEnum::TWO, $questionCount);
            pushCacheQuestionLibraryList(QuestionTypeEnum::SINGLE_CHOICE, QuestionDifficultyLevelEnum::TWO, $redis);
        }
        $threeStarQuestionUuids = getRandomSingleChoice(QuestionDifficultyLevelEnum::THREE, $questionCount, $redis);
        if (count($threeStarQuestionUuids) < $questionCount) {
            $threeStarQuestions = $singleChoiceModel->getRandom(QuestionDifficultyLevelEnum::THREE, $questionCount);
            pushCacheQuestionLibraryList(QuestionTypeEnum::SINGLE_CHOICE, QuestionDifficultyLevelEnum::THREE, $redis);
        }

        if (!isset($twoStarQuestions) && !isset($threeStarQuestions)) {
            $questionUuids = array_merge($twoStarQuestionUuids, $threeStarQuestionUuids);
            $questions = $singleChoiceModel->getByUuids($questionUuids);
        } else if (!isset($twoStarQuestions) && isset($threeStarQuestions)) {
            $twoStarQuestions = $singleChoiceModel->getByUuids($twoStarQuestionUuids);
            $questions = array_merge($twoStarQuestions, $threeStarQuestions);
        } else if (isset($twoStarQuestions) && !isset($threeStarQuestions)) {
            $threeStarQuestions = $singleChoiceModel->getByUuids($threeStarQuestionUuids);
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
            $fourStarQuestions = $singleChoiceModel->getRandom(QuestionDifficultyLevelEnum::FOUR, $questionCount);
            pushCacheQuestionLibraryList(QuestionTypeEnum::SINGLE_CHOICE, QuestionDifficultyLevelEnum::FOUR, $redis);
        }
        $fiveStarQuestionUuids = getRandomSingleChoice(QuestionDifficultyLevelEnum::FIVE, $questionCount, $redis);
        if (count($fiveStarQuestionUuids) < $questionCount) {
            $fiveStarQuestions = $singleChoiceModel->getRandom(QuestionDifficultyLevelEnum::FIVE, $questionCount);
            pushCacheQuestionLibraryList(QuestionTypeEnum::SINGLE_CHOICE, QuestionDifficultyLevelEnum::FIVE, $redis);
        }

        if (!isset($fourStarQuestions) && !isset($fiveStarQuestions)) {
            $questionUuids = array_merge($fourStarQuestionUuids, $fiveStarQuestionUuids);
            $questions = $singleChoiceModel->getByUuids($questionUuids);
        } else if (!isset($fourStarQuestions) && isset($fiveStarQuestions)) {
            $fourStarQuestions = $singleChoiceModel->getByUuids($fourStarQuestionUuids);
            $questions = array_merge($fourStarQuestions, $fiveStarQuestions);
        } else if (isset($fourStarQuestions) && !isset($fiveStarQuestions)) {
            $fiveStarQuestions = $singleChoiceModel->getByUuids($fiveStarQuestionUuids);
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
            $questions = $singleChoiceModel->getRandom($difficultyLevel, $questionCount);
            pushCacheQuestionLibraryList(QuestionTypeEnum::SINGLE_CHOICE, $difficultyLevel, $redis);
        } else {
            $questions = $singleChoiceModel->getByUuids($questionsUuids);
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