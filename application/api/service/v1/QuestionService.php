<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-16
 * Time: 11:58
 */

namespace app\api\service\v1;

use app\api\service\Base;
use app\common\AppException;
use app\common\Constant;
use app\common\enum\NoviceTestIsShowEnum;
use app\common\enum\QuestionDifficultyLevelEnum;
use app\common\enum\QuestionTypeEnum;
use app\common\enum\UserWritingSourceTypeEnum;
use app\common\helper\Redis;
use app\common\model\FillTheBlanksModel;
use app\common\model\SingleChoiceModel;
use app\common\model\TrueFalseQuestionModel;
use app\common\model\UserBaseModel;
use app\common\model\UserStudyWritingModel;
use app\common\model\UserWritingModel;
use app\common\model\WritingModel;
use think\Db;

class QuestionService extends Base
{
    //新手测试题，随机从选择题题库中选取12道题，其中每种星级难度的题2道
    public function getNoviceTestQuestions()
    {
        $redis = Redis::factory();
        $singleChoiceModel = new SingleChoiceModel();

        //从redis中随机获取各星级题目uuid
        $oneStarQuestions = getRandomSingleChoice(QuestionDifficultyLevelEnum::ONE, 2, $redis);
        $twoStarQuestions = getRandomSingleChoice(QuestionDifficultyLevelEnum::TWO, 2, $redis);
        $threeStarQuestions = getRandomSingleChoice(QuestionDifficultyLevelEnum::THREE, 2, $redis);
        $fourStarQuestions = getRandomSingleChoice(QuestionDifficultyLevelEnum::FOUR, 2, $redis);
        $fiveStarQuestions = getRandomSingleChoice(QuestionDifficultyLevelEnum::FIVE, 2, $redis);
        $sixStarQuestions = getRandomSingleChoice(QuestionDifficultyLevelEnum::SIX, 2, $redis);

        //redis缓存失效从数据库获取uuid，并重新生成缓存
        if (!$oneStarQuestions) {
            $oneStarQuestions = $singleChoiceModel->getRandomSingleChoiceUuid(QuestionDifficultyLevelEnum::ONE, 2);
            pushCacheQuestionLibraryList(QuestionTypeEnum::SINGLE_CHOICE, QuestionDifficultyLevelEnum::ONE, $redis);
        }
        if (!$twoStarQuestions) {
            $twoStarQuestions = $singleChoiceModel->getRandomSingleChoiceUuid(QuestionDifficultyLevelEnum::TWO, 2);
            pushCacheQuestionLibraryList(QuestionTypeEnum::SINGLE_CHOICE, QuestionDifficultyLevelEnum::TWO, $redis);
        }
        if (!$threeStarQuestions) {
            $threeStarQuestions = $singleChoiceModel->getRandomSingleChoiceUuid(QuestionDifficultyLevelEnum::THREE, 2);
            pushCacheQuestionLibraryList(QuestionTypeEnum::SINGLE_CHOICE, QuestionDifficultyLevelEnum::THREE, $redis);
        }
        if (!$fourStarQuestions) {
            $fourStarQuestions = $singleChoiceModel->getRandomSingleChoiceUuid(QuestionDifficultyLevelEnum::FOUR, 2);
            pushCacheQuestionLibraryList(QuestionTypeEnum::SINGLE_CHOICE, QuestionDifficultyLevelEnum::FOUR, $redis);
        }
        if (!$fiveStarQuestions) {
            $fiveStarQuestions = $singleChoiceModel->getRandomSingleChoiceUuid(QuestionDifficultyLevelEnum::FIVE, 2);
            pushCacheQuestionLibraryList(QuestionTypeEnum::SINGLE_CHOICE, QuestionDifficultyLevelEnum::FIVE, $redis);
        }
        if (!$sixStarQuestions) {
            $sixStarQuestions = $singleChoiceModel->getRandomSingleChoiceUuid(QuestionDifficultyLevelEnum::SIX, 2);
            pushCacheQuestionLibraryList(QuestionTypeEnum::SINGLE_CHOICE, QuestionDifficultyLevelEnum::SIX, $redis);
        }

        //根据所有题目uuid，从数据库一次性查出所有题目详情
        $uuids = array_merge(
            $oneStarQuestions,
            $twoStarQuestions,
            $threeStarQuestions,
            $fourStarQuestions,
            $fiveStarQuestions,
            $sixStarQuestions);
        $questions = $singleChoiceModel->getByUuidsAndOrderByDifficultyLevel($uuids);

        //格式化返回数据
        $returnData = [];
        foreach ($questions as $item) {
            if (!isset($returnData[$item["difficulty_level"]])) {
                $returnData[$item["difficulty_level"]]["difficulty_level"] = $item["difficulty_level"];
            }
            $returnData[$item["difficulty_level"]]["list"][] = [
                "uuid" => $item["uuid"],
                "question" => $item["question"],
                "possible_answers" => json_decode($item["possible_answers"], true),
                "answer" => $item["answer"],
            ];
        }

        return array_values($returnData);
    }

    public function submitNoviceResult($userInfo, $noviceLevel)
    {
        $userModel = new UserBaseModel();
        $user = $userModel->findByUuid($userInfo["uuid"]);

        if (!$user) {
            throw AppException::factory(AppException::USER_NOT_EXISTS);
        }

        if ($user["novice_test_time"] != 0) {
            throw AppException::factory(AppException::USER_NOVICE_TEST_ALREADY);
        }

        //保存用户信息
        $user->novice_level = $noviceLevel;
        $user->novice_test_time = time();
        $user->novice_test_is_show = NoviceTestIsShowEnum::NO;
        $user->update_time = time();
        $user->save();

        cacheUserInfoByToken($user->toArray(), Redis::factory());
        return new \stdClass();
    }

    public function getStudyFillTheBlanks($user, $difficultyLevel)
    {
        $fillTheBlanksModel = new FillTheBlanksModel();
        $redis = Redis::factory();

        //每套题暂定30题，开始答题后必须答完当前这套题才可以答下一套题
        $uuids = getStudyFillTheBlanksCache($user["uuid"], $difficultyLevel, $redis);
        if (empty($uuids)) {
            $uuids = getRandomFillTheBlanks($difficultyLevel, Constant::STUDY_FILL_THE_BLANKS_COUNT, $redis);
            if (empty($uuids)) {
                $uuids = $fillTheBlanksModel->getRandomUuid($difficultyLevel, Constant::STUDY_FILL_THE_BLANKS_COUNT);
                pushCacheQuestionLibraryList(QuestionTypeEnum::FILL_THE_BLANKS, $difficultyLevel, $redis);
            }
            cacheStudyFillTheBlanks($user["uuid"], $difficultyLevel, $uuids, $redis);
        }
        $questions = $fillTheBlanksModel->getByUuids($uuids);

        //格式化数据
        $returnData = [];
        foreach ($questions as $question) {
            $returnData[] = [
                "uuid" => $question["uuid"],
                "question" => $question["question"],
                "answer" => $question["answer"],
            ];
        }

        return $returnData;
    }

    public function getStudySingleChoice($user, $difficultyLevel)
    {
        $singleChoiceModel = new SingleChoiceModel();
        $redis = Redis::factory();

        //每套题暂定30题，开始答题后必须答完当前这套题才可以答下一套题
        $uuids = getStudySingleChoiceCache($user["uuid"], $difficultyLevel, $redis);
        if (empty($uuids)) {
            $uuids = getRandomSingleChoice($difficultyLevel, Constant::STUDY_SINGLE_CHOICE_COUNT, $redis);
            if (empty($uuids)) {
                $uuids = $singleChoiceModel->getRandomUuid($difficultyLevel, Constant::STUDY_SINGLE_CHOICE_COUNT);
                pushCacheQuestionLibraryList(QuestionTypeEnum::SINGLE_CHOICE, $difficultyLevel, $redis);
            }
            cacheStudySingleChoice($user["uuid"], $difficultyLevel, $uuids, $redis);
        }
        $questions = $singleChoiceModel->getByUuids($uuids);

        //格式化数据
        $returnData = [];
        foreach ($questions as $question) {
            $returnData[] = [
                "uuid" => $question["uuid"],
                "question" => $question["question"],
                "possible_answers" => json_decode($question["possible_answers"], true),
                "answer" => $question["answer"],
            ];
        }

        return $returnData;
    }

    public function getStudyTrueFalseQuestion($user, $difficultyLevel)
    {
        $trueFalseQuestionModel = new TrueFalseQuestionModel();
        $redis = Redis::factory();

        //每套题暂定30题，开始答题后必须答完当前这套题才可以答下一套题
        $uuids = getStudyTrueFalseQuestionCache($user["uuid"], $difficultyLevel, $redis);
        if (empty($uuids)) {
            $uuids = getRandomTrueFalseQuestion($difficultyLevel, Constant::STUDY_TRUE_FALSE_QUESTION_COUNT, $redis);
            if (empty($uuids)) {
                $uuids = $trueFalseQuestionModel->getRandomUuid($difficultyLevel, Constant::STUDY_TRUE_FALSE_QUESTION_COUNT);
                pushCacheQuestionLibraryList(QuestionTypeEnum::TRUE_FALSE_QUESTION, $difficultyLevel, $redis);
            }
            cacheStudyTrueFalseQuestion($user["uuid"], $difficultyLevel, $uuids, $redis);
        }
        $questions = $trueFalseQuestionModel->getByUuids($uuids);

        //格式化数据
        $returnData = [];
        foreach ($questions as $question) {
            $returnData[] = [
                "uuid" => $question["uuid"],
                "question" => $question["question"],
                "answer" => $question["answer"],
            ];
        }

        return $returnData;
    }

    public function getStudyWriting($user, $difficultyLevel)
    {
        $writingModel = new WritingModel();
        $redis = Redis::factory();

        //开始答题后必须答完当前这套题才可以答下一套题
        $uuid = getStudyWritingCache($user["uuid"], $difficultyLevel, $redis);
        if (empty($uuid)) {
            $uuidArray = getRandomWriting($difficultyLevel, 1, $redis);
            if (empty($uuidArray)) {
                $uuidInfo = $writingModel->getRandomUuid($difficultyLevel);
                $uuid = $uuidInfo["uuid"];
                pushCacheQuestionLibraryList(QuestionTypeEnum::WRITING, $difficultyLevel, $redis);
            } else {
                $uuid = $uuidArray[0];
            }
            cacheStudyWriting($user["uuid"], $difficultyLevel, $uuid, $redis);
        }
        $question = $writingModel->getByUuid($uuid);

        //格式化数据
        $returnData = [
            "uuid" => $question["uuid"],
            "topic" => $question["topic"],
            "requirements" => json_decode($question["requirements"], true),
        ];

        return $returnData;
    }

    public function submitStudyFillTheBlanks($user, $difficultyLevel)
    {
        //开始答题后必须答完当前这套题才可以答下一套题，所以用户提交后删除原题缓存
        $redis = Redis::factory();
        removeStudyFillTheBlanksCache($user["uuid"], $difficultyLevel, $redis);
        return new \stdClass();
    }

    public function submitStudySingleChoice($user, $difficultyLevel)
    {
        //开始答题后必须答完当前这套题才可以答下一套题，所以用户提交后删除原题缓存
        $redis = Redis::factory();
        removeStudySingleChoiceCache($user["uuid"], $difficultyLevel, $redis);
        return new \stdClass();
    }

    public function submitStudyTrueFalseQuestion($user, $difficultyLevel)
    {
        //开始答题后必须答完当前这套题才可以答下一套题，所以用户提交后删除原题缓存
        $redis = Redis::factory();
        removeStudyTrueFalseQuestionCache($user["uuid"], $difficultyLevel, $redis);
        return new \stdClass();
    }

    public function submitStudyWriting($user, $writingUuid, $content, $difficultyLevel)
    {
        $writingModel = new WritingModel();

        //作文题信息
        $writing = $writingModel->findByUuid($writingUuid);
        if ($writing == null) {
            throw AppException::factory(AppException::QUESTION_WRITING_NOT_EXISTS);
        }

        $userStudyWritingModel = new UserStudyWritingModel();
        $userWritingModel = new UserWritingModel();

        //纪录作文提交信息
        Db::startTrans();
        try {
            $userStudyWritingData = [
                "uuid" => getRandomString(),
                "user_uuid" => $user["uuid"],
                "writing_uuid" => $writingUuid,
                "difficulty_level" => $writing["difficulty_level"],
                "requirements" => $writing["requirements"],
                "topic" => $writing["topic"],
                "create_time" => time(),
                "update_time" => time(),
            ];
            $userStudyWritingModel->insert($userStudyWritingData);

            $userWritingData = [
                "user_uuid" => $user["uuid"],
                "source_type" => UserWritingSourceTypeEnum::STUDY,
                "source_uuid" => $userStudyWritingData["uuid"],
                "difficulty_level" => $writing["difficulty_level"],
                "requirements" => $writing["requirements"],
                "topic" => $writing["topic"],
                "content" => json_encode($content, JSON_UNESCAPED_UNICODE),
                "create_time" => time(),
                "update_time" => time(),
            ];
            $userWritingModel->insert($userWritingData);

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }

        //开始答题后必须答完当前这套题才可以答下一套题，所以用户提交后删除原题缓存
        $redis = Redis::factory();
        removeStudyWritingCache($user["uuid"], $difficultyLevel, $redis);
        return new \stdClass();
    }
}