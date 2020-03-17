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
use app\common\helper\Redis;
use app\common\model\FillTheBlanksModel;
use app\common\model\SingleChoiceModel;
use app\common\model\UserBaseModel;

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
}