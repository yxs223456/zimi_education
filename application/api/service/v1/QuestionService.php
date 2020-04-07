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
use app\common\enum\UserSynthesizeIsFinishEnum;
use app\common\enum\UserWritingSourceTypeEnum;
use app\common\helper\Redis;
use app\common\model\FillTheBlanksModel;
use app\common\model\SingleChoiceModel;
use app\common\model\TrueFalseQuestionModel;
use app\common\model\UserBaseModel;
use app\common\model\UserStudyWritingModel;
use app\common\model\UserSynthesizeModel;
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
        if (count($oneStarQuestions) < 2) {
            $oneStarQuestions = $singleChoiceModel->getRandomUuid(QuestionDifficultyLevelEnum::ONE, 2);
            pushCacheQuestionLibraryList(QuestionTypeEnum::SINGLE_CHOICE, QuestionDifficultyLevelEnum::ONE, $redis);
        }
        if (count($twoStarQuestions) < 2) {
            $twoStarQuestions = $singleChoiceModel->getRandomUuid(QuestionDifficultyLevelEnum::TWO, 2);
            pushCacheQuestionLibraryList(QuestionTypeEnum::SINGLE_CHOICE, QuestionDifficultyLevelEnum::TWO, $redis);
        }
        if (count($threeStarQuestions) < 2) {
            $threeStarQuestions = $singleChoiceModel->getRandomUuid(QuestionDifficultyLevelEnum::THREE, 2);
            pushCacheQuestionLibraryList(QuestionTypeEnum::SINGLE_CHOICE, QuestionDifficultyLevelEnum::THREE, $redis);
        }
        if (count($fourStarQuestions) < 2) {
            $fourStarQuestions = $singleChoiceModel->getRandomUuid(QuestionDifficultyLevelEnum::FOUR, 2);
            pushCacheQuestionLibraryList(QuestionTypeEnum::SINGLE_CHOICE, QuestionDifficultyLevelEnum::FOUR, $redis);
        }
        if (count($fiveStarQuestions) < 2) {
            $fiveStarQuestions = $singleChoiceModel->getRandomUuid(QuestionDifficultyLevelEnum::FIVE, 2);
            pushCacheQuestionLibraryList(QuestionTypeEnum::SINGLE_CHOICE, QuestionDifficultyLevelEnum::FIVE, $redis);
        }
        if (count($sixStarQuestions) < 2) {
            $sixStarQuestions = $singleChoiceModel->getRandomUuid(QuestionDifficultyLevelEnum::SIX, 2);
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
            $returnData[] = [
                "difficulty_level" => $item["difficulty_level"],
                "uuid" => $item["uuid"],
                "question" => $item["question"],
                "possible_answers" => json_decode($item["possible_answers"], true),
                "answer" => [$item["answer"]],
            ];
        }

        return $returnData;
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
                "answer" => json_decode($question["answer"], true),
                "is_sequence" => $question["is_sequence"],
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
                "answer" => [$question["answer"]],
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
                "answer" => [$question["answer"]],
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
                "answer" => json_encode($content, JSON_UNESCAPED_UNICODE),
            ];
            $userStudyWritingModel->save($userStudyWritingData);

            $userWritingData = [
                "user_uuid" => $user["uuid"],
                "source_type" => UserWritingSourceTypeEnum::STUDY,
                "source_uuid" => $userStudyWritingData["uuid"],
                "difficulty_level" => $writing["difficulty_level"],
                "requirements" => $writing["requirements"],
                "topic" => $writing["topic"],
                "content" => json_encode($content, JSON_UNESCAPED_UNICODE),
                "total_score" => 100,
            ];
            $userWritingModel->save($userWritingData);

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

    //综合测试
    //每种类型的题全部为同等难度的星级。
    //测试依次秩序选择题-填空题-判断题-作文题。测试不限时间，当前测试没有完成不会下发新的测试题，下次进入从上次答题开始。
    public function getSynthesize($user, $difficultyLevel)
    {
        $singleChoiceModel = new SingleChoiceModel();
        $fillTheBlanksModel = new FillTheBlanksModel();
        $trueFalseQuestionModel = new TrueFalseQuestionModel();
        $writingModel = new WritingModel();
        $userSynthesizeModel = new UserSynthesizeModel();

        //用户最后一套未答完的综合测试
        $synthesizeData = $userSynthesizeModel->getLastUnFinish($user["uuid"], $difficultyLevel);

        //没有未答完的综合测试题，生成一套新的题
        if ($synthesizeData == null) {
            $redis = Redis::factory();
            //单选题
            $randomSingleChoiceUuid = getRandomSingleChoice($difficultyLevel, Constant::SYNTHESIZE_SINGLE_CHOICE_COUNT, $redis);
            if (count($randomSingleChoiceUuid) < Constant::SYNTHESIZE_SINGLE_CHOICE_COUNT) {
                $randomSingleChoice = $singleChoiceModel->getRandom($difficultyLevel, Constant::SYNTHESIZE_SINGLE_CHOICE_COUNT)->toArray();
                $randomSingleChoiceUuid = array_column($randomSingleChoice, "uuid");
                pushCacheQuestionLibraryList(QuestionTypeEnum::SINGLE_CHOICE, $difficultyLevel, $redis);
            } else {
                $randomSingleChoice = $singleChoiceModel->getByUuids($randomSingleChoiceUuid)->toArray();
                $randomSingleChoice = $this->questionOrderByUuid($randomSingleChoiceUuid, $randomSingleChoice);
            }

            //填空题
            $randomFillTheBlanksUuid = getRandomFillTheBlanks($difficultyLevel, Constant::SYNTHESIZE_FILL_THE_BLANKS_COUNT, $redis);
            if (count($randomFillTheBlanksUuid) < Constant::SYNTHESIZE_FILL_THE_BLANKS_COUNT) {
                $randomFillTheBlanks = $fillTheBlanksModel->getRandom($difficultyLevel, Constant::SYNTHESIZE_FILL_THE_BLANKS_COUNT)->toArray();
                $randomFillTheBlanksUuid = array_column($randomFillTheBlanks, "uuid");
                pushCacheQuestionLibraryList(QuestionTypeEnum::FILL_THE_BLANKS, $difficultyLevel, $redis);
            } else {
                $randomFillTheBlanks = $fillTheBlanksModel->getByUuids($randomFillTheBlanksUuid)->toArray();
                $randomFillTheBlanks = $this->questionOrderByUuid($randomFillTheBlanksUuid, $randomFillTheBlanks);

            }

            //判断题
            $randomTrueFalseQuestionUuid = getRandomTrueFalseQuestion($difficultyLevel, Constant::SYNTHESIZE_TRUE_FALSE_QUESTION_COUNT, $redis);
            if (count($randomTrueFalseQuestionUuid) < Constant::SYNTHESIZE_TRUE_FALSE_QUESTION_COUNT) {
                $randomTrueFalseQuestion = $trueFalseQuestionModel->getRandom($difficultyLevel, Constant::SYNTHESIZE_TRUE_FALSE_QUESTION_COUNT)->toArray();
                $randomTrueFalseQuestionUuid = array_column($randomTrueFalseQuestion, "uuid");
                pushCacheQuestionLibraryList(QuestionTypeEnum::TRUE_FALSE_QUESTION, $difficultyLevel, $redis);
            } else {
                $randomTrueFalseQuestion = $trueFalseQuestionModel->getByUuids($randomTrueFalseQuestionUuid)->toArray();
                $randomTrueFalseQuestion = $this->questionOrderByUuid($randomTrueFalseQuestionUuid, $randomTrueFalseQuestion);
            }

            //作文题
            $randomWritingUuid = getRandomWriting($difficultyLevel, 1, $redis);
            if (!$randomWritingUuid) {
                $randomWriting = $writingModel->getRandom($difficultyLevel);
                $randomWritingUuid = [$randomWriting["uuid"]];
                pushCacheQuestionLibraryList(QuestionTypeEnum::WRITING, $difficultyLevel, $redis);
            } else {
                $randomWriting = $writingModel->getByUuid($randomWritingUuid[0]);
            }

            //数据库纪录随机生成的问题
            $insertData = [
                "uuid" => getRandomString(),
                "user_uuid" => $user["uuid"],
                "difficulty_level" => $difficultyLevel,
                "questions" => json_encode([
                    ["type" => QuestionTypeEnum::SINGLE_CHOICE,"uuids" => $randomSingleChoiceUuid,],
                    ["type" => QuestionTypeEnum::FILL_THE_BLANKS,"uuids" => $randomFillTheBlanksUuid,],
                    ["type" => QuestionTypeEnum::TRUE_FALSE_QUESTION,"uuids" => $randomTrueFalseQuestionUuid,],
                    ["type" => QuestionTypeEnum::WRITING,"uuids" => $randomWritingUuid,],
                ]),
                "create_time" => time(),
                "update_time" => time(),
            ];
            $userSynthesizeModel->insert($insertData);
            $synthesizeUuid = $insertData["uuid"];
        } else {
            $questions = json_decode($synthesizeData["questions"], true);
            foreach ($questions as $item) {
                switch($item["type"]) {
                    case QuestionTypeEnum::SINGLE_CHOICE:
                        $randomSingleChoice = $singleChoiceModel->getByUuids($item["uuids"]);
                        break;
                    case QuestionTypeEnum::FILL_THE_BLANKS:
                        $randomFillTheBlanks = $fillTheBlanksModel->getByUuids($item["uuids"]);
                        break;
                    case QuestionTypeEnum::TRUE_FALSE_QUESTION:
                        $randomTrueFalseQuestion = $trueFalseQuestionModel->getByUuids($item["uuids"]);
                        break;
                    case QuestionTypeEnum::WRITING:
                        $randomWriting = $writingModel->getByUuid($item["uuids"][0]);

                }
            }
            $synthesizeUuid = $synthesizeData["uuid"];
        }

        //格式化返回数据
        if (!empty($synthesizeData["answers"])) {
            $answers = json_decode($synthesizeData["answers"], true);
            foreach ($answers as $item) {
                switch ($item["type"]) {
                    case QuestionTypeEnum::SINGLE_CHOICE:
                        $singleChoiceAnswers = array_column($item["list"], "answer", "uuid");
                        break;
                    case QuestionTypeEnum::FILL_THE_BLANKS:
                        $fillTheBlanksAnswers = array_column($item["list"], "answers", "uuid");
                        break;
                    case QuestionTypeEnum::TRUE_FALSE_QUESTION:
                        $trueFalseQuestionAnswers = array_column($item["list"], "answer", "uuid");
                        break;
                    case QuestionTypeEnum::WRITING:
                        $writingAnswers = array_column($item["list"], "content", "uuid");
                        break;
                }
            }
        }
        $returnData["uuid"] = $synthesizeUuid;
        $returnData["location"] = [
            "type"=>QuestionTypeEnum::SINGLE_CHOICE,
            "index"=>0
        ];
        $returnData["exercises"] = [];
        foreach ($randomSingleChoice as $key=>$singleChoice) {
            if (!isset($returnData["exercises"]["singleChoice"])) {
                $returnData["exercises"]["singleChoice"]["type"] = QuestionTypeEnum::SINGLE_CHOICE;
            }

            if (isset($singleChoiceAnswers[$singleChoice["uuid"]])) {
                $userAnswer = $singleChoiceAnswers[$singleChoice["uuid"]];
                if ($key == count($randomSingleChoice) - 1) {
                    $returnData["location"] = [
                        "type"=>QuestionTypeEnum::FILL_THE_BLANKS,
                        "index"=>0
                    ];
                } else {
                    $returnData["location"]["index"] = $key+1;
                }
            } else {
                $userAnswer = "";
            }
            $returnData["exercises"]["singleChoice"]["list"][] = [
                "uuid" => $singleChoice["uuid"],
                "question" => $singleChoice["question"],
                "possible_answers" => json_decode($singleChoice["possible_answers"], true),
                "answer" => $userAnswer,
            ];

        }
        foreach ($randomFillTheBlanks as $key=>$fillTheBlanks) {
            if (!isset($returnData["exercises"]["fillTheBlanks"])) {
                $returnData["exercises"]["fillTheBlanks"]["type"] = QuestionTypeEnum::FILL_THE_BLANKS;
            }
            if (isset($fillTheBlanksAnswers[$fillTheBlanks["uuid"]])) {
                $userAnswer = $fillTheBlanksAnswers[$fillTheBlanks["uuid"]];
                if ($key == count($randomFillTheBlanks) - 1) {
                    $returnData["location"] = [
                        "type"=>QuestionTypeEnum::TRUE_FALSE_QUESTION,
                        "index"=>0
                    ];
                } else {
                    $returnData["location"]["index"] = $key+1;
                }
            } else {
                $userAnswer = [];
            }
            $returnData["exercises"]["fillTheBlanks"]["list"][] = [
                "uuid" => $fillTheBlanks["uuid"],
                "question" => $fillTheBlanks["question"],
                "answers" => $userAnswer,
            ];
        }
        foreach ($randomTrueFalseQuestion as $key=>$trueFalseQuestion) {
            if (!isset($returnData["exercises"]["trueFalseQuestion"])) {
                $returnData["exercises"]["trueFalseQuestion"]["type"] = QuestionTypeEnum::TRUE_FALSE_QUESTION;
            }
            if (isset($trueFalseQuestionAnswers[$trueFalseQuestion["uuid"]])) {
                $userAnswer = $trueFalseQuestionAnswers[$trueFalseQuestion["uuid"]];
                if ($key == count($randomTrueFalseQuestion) - 1) {
                    $returnData["location"] = [
                        "type"=>QuestionTypeEnum::WRITING,
                        "index"=>0
                    ];
                } else {
                    $returnData["location"]["index"] = $key+1;
                }
            } else {
                $userAnswer = 0;
            }
            $returnData["exercises"]["trueFalseQuestion"]["list"][] = [
                "uuid" => $trueFalseQuestion["uuid"],
                "question" => $trueFalseQuestion["question"],
                "answer" => $userAnswer,
            ];
        }
        $returnData["exercises"]["writing"] = [
            "type" => QuestionTypeEnum::WRITING,
            "list" => [
                [
                    "uuid" => $randomWriting["uuid"],
                    "topic"=> $randomWriting["topic"],
                    "requirements" => json_decode($randomWriting["requirements"], true),
                    "contents" => isset($writingAnswers[$randomWriting["uuid"]])?$writingAnswers[$randomWriting["uuid"]]:["text"=>["title"=>"","content"=>""],"images"=>[]],
                ]
            ],
        ];

        $returnData["exercises"] = array_values($returnData["exercises"]);
        return $returnData;
    }

    public function submitSynthesizeDraft($user, $uuid, $answers)
    {
        $userSynthesizeModel = new UserSynthesizeModel();
        $synthesize = $userSynthesizeModel->findByUuid($uuid);
        if ($synthesize == null || $synthesize["user_uuid"] != $user["uuid"]) {
            throw AppException::factory(AppException::SYNTHESIZE_NOT_EXISTS);
        }

        //答案不允许重复提交
        if ($synthesize["is_finish"] == UserSynthesizeIsFinishEnum::YES) {
            throw AppException::factory(AppException::SYNTHESIZE_SUBMIT_ANSWER_ALREADY);
        }

        $synthesize->answers = json_encode($answers, JSON_UNESCAPED_UNICODE);
        $synthesize->save();

        return new \stdClass();
    }

    public function submitSynthesize($user, $uuid, $answers)
    {
        $userSynthesizeModel = new UserSynthesizeModel();
        $synthesize = $userSynthesizeModel->findByUuid($uuid);
        if ($synthesize == null || $synthesize["user_uuid"] != $user["uuid"]) {
            throw AppException::factory(AppException::SYNTHESIZE_NOT_EXISTS);
        }

        //答案不允许重复提交
        if ($synthesize["is_finish"] == UserSynthesizeIsFinishEnum::YES) {
            throw AppException::factory(AppException::SYNTHESIZE_SUBMIT_ANSWER_ALREADY);
        }

        $writingModel = new WritingModel();
        $userWritingModel = new UserWritingModel();

        Db::startTrans();
        try {
            //纪录用户答案
            $synthesize->answers = json_encode($answers, JSON_UNESCAPED_UNICODE);
            $synthesize->is_finish = UserSynthesizeIsFinishEnum::YES;
            $synthesize->save();

            //同步作文内容以便统一审核
            foreach ($answers as $item) {
                if ($item["type"] == QuestionTypeEnum::WRITING) {
                    foreach ($item["list"] as $answerInfo) {
                        $writing = $writingModel->findByUuid($answerInfo["uuid"]);

                        $userWritingData = [
                            "user_uuid" => $user["uuid"],
                            "source_type" => UserWritingSourceTypeEnum::SYNTHESIZE,
                            "source_uuid" => $uuid,
                            "difficulty_level" => $writing["difficulty_level"],
                            "requirements" => $writing["requirements"],
                            "topic" => $writing["topic"],
                            "content" => json_encode($answerInfo["contents"], JSON_UNESCAPED_UNICODE),
                            "total_score" => 30,
                        ];
                        $userWritingModel->save($userWritingData);
                    }
                }
            }
            Db::commit();

            return new \stdClass();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    public function questionOrderByUuid($uuids, $questions)
    {
        $questions = array_column($questions, null, "uuid");
        $returnData = [];
        foreach ($uuids as $uuid) {
            $returnData[] = $questions[$uuid];
        }
        return $returnData;
    }
}