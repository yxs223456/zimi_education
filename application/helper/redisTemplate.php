<?php

//缓存用户信息
function cacheUserInfoByToken(array $userInfo, Redis $redis) {
    $key = "de_education:userInfoByToken:" . $userInfo["token"];
    $redis->hMSet($key, $userInfo);
    //缓存有效期72小时
    $redis->expire($key, 259200);

}

//通过token获取用户信息
function getUserInfoByToken($token, Redis $redis) {
    if ($token == "") {
        return [];
    }
    $key = "de_education:userInfoByToken:$token";
    return $redis->hGetAll($key);
}

//将单选题放入题库缓存
function addFillTheBlanksArray(array $fillTheBlanksList, Redis $redis) {
    $list = [
        "one" => [],
        "two" => [],
        "three" => [],
        "four" => [],
        "five" => [],
        "six" => [],
    ];

    foreach ($fillTheBlanksList as $fillTheBlanks) {
        if ($fillTheBlanks["is_use"] == 1) {
            switch ($fillTheBlanks["difficulty_level"]) {
                case 1:
                    $list["one"][] = $fillTheBlanks["uuid"];
                    break;
                case 2:
                    $list["two"][] = $fillTheBlanks["uuid"];
                    break;
                case 3:
                    $list["three"][] = $fillTheBlanks["uuid"];
                    break;
                case 4:
                    $list["four"][] = $fillTheBlanks["uuid"];
                    break;
                case 5:
                    $list["five"][] = $fillTheBlanks["uuid"];
                    break;
                case 6:
                    $list["six"][] = $fillTheBlanks["uuid"];
                    break;
            }
        }
    }
    if (count($list["one"]) != 0) {
        $key = "de_education:fillTheBlanksLibrary:oneStar";
        $redis->sAddArray($key, $list["one"]);
    }
    if (count($list["two"]) != 0) {
        $key = "de_education:fillTheBlanksLibrary:twoStar";
        $redis->sAddArray($key, $list["two"]);
    }
    if (count($list["three"]) != 0) {
        $key = "de_education:fillTheBlanksLibrary:threeStar";
        $redis->sAddArray($key, $list["three"]);
    }
    if (count($list["four"]) != 0) {
        $key = "de_education:fillTheBlanksLibrary:fourStar";
        $redis->sAddArray($key, $list["four"]);
    }
    if (count($list["five"]) != 0) {
        $key = "de_education:fillTheBlanksLibrary:fiveStar";
        $redis->sAddArray($key, $list["five"]);
    }if (count($list["six"]) != 0) {
        $key = "de_education:fillTheBlanksLibrary:sixStar";
        $redis->sAddArray($key, $list["six"]);
    }
}

//将单选题放入题库缓存
function addAllFillTheBlanks(array $fillTheBlanksUuids, $difficultyLevel, Redis $redis) {
    $key = "";
    switch ($difficultyLevel) {
        case 1:
            $key = "de_education:fillTheBlanksLibrary:oneStar";
            break;
        case 2:
            $key = "de_education:fillTheBlanksLibrary:twoStar";
            break;
        case 3:
            $key = "de_education:fillTheBlanksLibrary:threeStar";
            break;
        case 4:
            $key = "de_education:fillTheBlanksLibrary:fourStar";
            break;
        case 5:
            $key = "de_education:fillTheBlanksLibrary:fiveStar";
            break;
        case 6:
            $key = "de_education:fillTheBlanksLibrary:sixStar";
            break;
    }
    if ($key) {
        $redis->sAddArray($key, $fillTheBlanksUuids);
    }
}

//将单选题放入题库缓存
function addFillTheBlanks($fillTheBlanksUuid, $difficultyLevel, Redis $redis) {
    $key = "";
    switch ($difficultyLevel) {
        case 1:
            $key = "de_education:fillTheBlanksLibrary:oneStar";
            break;
        case 2:
            $key = "de_education:fillTheBlanksLibrary:twoStar";
            break;
        case 3:
            $key = "de_education:fillTheBlanksLibrary:threeStar";
            break;
        case 4:
            $key = "de_education:fillTheBlanksLibrary:fourStar";
            break;
        case 5:
            $key = "de_education:fillTheBlanksLibrary:fiveStar";
            break;
        case 6:
            $key = "de_education:fillTheBlanksLibrary:sixStar";
            break;
    }
    if ($key) {
        $redis->sadd($key, $fillTheBlanksUuid);
    }
}

//随机获取填空题
function getRandomFillTheBlanks($difficultyLevel, $count, Redis $redis) {
    $key = "";
    switch ($difficultyLevel) {
        case 1:
            $key = "de_education:fillTheBlanksLibrary:oneStar";
            break;
        case 2:
            $key = "de_education:fillTheBlanksLibrary:twoStar";
            break;
        case 3:
            $key = "de_education:fillTheBlanksLibrary:threeStar";
            break;
        case 4:
            $key = "de_education:fillTheBlanksLibrary:fourStar";
            break;
        case 5:
            $key = "de_education:fillTheBlanksLibrary:fiveStar";
            break;
        case 6:
            $key = "de_education:fillTheBlanksLibrary:sixStar";
            break;
    }
    if ($key) {
        return $redis->sRandMember($key, $count);
    } else {
        return [];
    }
}

//将单选题移出题库缓存
function removeFillTheBlanks($fillTheBlanksUuid, $difficultyLevel, Redis $redis) {
    $key = "";
    switch ($difficultyLevel) {
        case 1:
            $key = "de_education:fillTheBlanksLibrary:oneStar";
            break;
        case 2:
            $key = "de_education:fillTheBlanksLibrary:twoStar";
            break;
        case 3:
            $key = "de_education:fillTheBlanksLibrary:threeStar";
            break;
        case 4:
            $key = "de_education:fillTheBlanksLibrary:fourStar";
            break;
        case 5:
            $key = "de_education:fillTheBlanksLibrary:fiveStar";
            break;
        case 6:
            $key = "de_education:fillTheBlanksLibrary:sixStar";
            break;
    }
    if ($key) {
        $redis->sRem($key, $fillTheBlanksUuid);
    }
}

//将单选题放入题库缓存
function addSingleChoiceArray(array $singleChoiceList, Redis $redis) {
    $list = [
        "one" => [],
        "two" => [],
        "three" => [],
        "four" => [],
        "five" => [],
        "six" => [],
    ];

    foreach ($singleChoiceList as $singleChoice) {
        if ($singleChoice["is_use"] == 1) {
            switch ($singleChoice["difficulty_level"]) {
                case 1:
                    $list["one"][] = $singleChoice["uuid"];
                    break;
                case 2:
                    $list["two"][] = $singleChoice["uuid"];
                    break;
                case 3:
                    $list["three"][] = $singleChoice["uuid"];
                    break;
                case 4:
                    $list["four"][] = $singleChoice["uuid"];
                    break;
                case 5:
                    $list["five"][] = $singleChoice["uuid"];
                    break;
                case 6:
                    $list["six"][] = $singleChoice["uuid"];
                    break;
            }
        }
    }
    if (count($list["one"]) != 0) {
        $key = "de_education:singleChoiceLibrary:oneStar";
        $redis->sAddArray($key, $list["one"]);
    }
    if (count($list["two"]) != 0) {
        $key = "de_education:singleChoiceLibrary:twoStar";
        $redis->sAddArray($key, $list["two"]);
    }
    if (count($list["three"]) != 0) {
        $key = "de_education:singleChoiceLibrary:threeStar";
        $redis->sAddArray($key, $list["three"]);
    }
    if (count($list["four"]) != 0) {
        $key = "de_education:singleChoiceLibrary:fourStar";
        $redis->sAddArray($key, $list["four"]);
    }
    if (count($list["five"]) != 0) {
        $key = "de_education:singleChoiceLibrary:fiveStar";
        $redis->sAddArray($key, $list["five"]);
    }if (count($list["six"]) != 0) {
        $key = "de_education:singleChoiceLibrary:sixStar";
        $redis->sAddArray($key, $list["six"]);
    }
}

//将单选题放入题库缓存
function addAllSingleChoice(array $singleChoiceUuids, $difficultyLevel, Redis $redis) {
    $key = "";
    switch ($difficultyLevel) {
        case 1:
            $key = "de_education:singleChoiceLibrary:oneStar";
            break;
        case 2:
            $key = "de_education:singleChoiceLibrary:twoStar";
            break;
        case 3:
            $key = "de_education:singleChoiceLibrary:threeStar";
            break;
        case 4:
            $key = "de_education:singleChoiceLibrary:fourStar";
            break;
        case 5:
            $key = "de_education:singleChoiceLibrary:fiveStar";
            break;
        case 6:
            $key = "de_education:singleChoiceLibrary:sixStar";
            break;
    }
    if ($key) {
        $redis->sAddArray($key, $singleChoiceUuids);
    }
}

//将单选题放入题库缓存
function addSingleChoice($singleChoiceUuid, $difficultyLevel, Redis $redis) {
    $key = "";
    switch ($difficultyLevel) {
        case 1:
            $key = "de_education:singleChoiceLibrary:oneStar";
            break;
        case 2:
            $key = "de_education:singleChoiceLibrary:twoStar";
            break;
        case 3:
            $key = "de_education:singleChoiceLibrary:threeStar";
            break;
        case 4:
            $key = "de_education:singleChoiceLibrary:fourStar";
            break;
        case 5:
            $key = "de_education:singleChoiceLibrary:fiveStar";
            break;
        case 6:
            $key = "de_education:singleChoiceLibrary:sixStar";
            break;
    }
    if ($key) {
        $redis->sadd($key, $singleChoiceUuid);
    }
}

//随机获取选择题
function getRandomSingleChoice($difficultyLevel, $count, Redis $redis) {
    $key = "";
    switch ($difficultyLevel) {
        case 1:
            $key = "de_education:singleChoiceLibrary:oneStar";
            break;
        case 2:
            $key = "de_education:singleChoiceLibrary:twoStar";
            break;
        case 3:
            $key = "de_education:singleChoiceLibrary:threeStar";
            break;
        case 4:
            $key = "de_education:singleChoiceLibrary:fourStar";
            break;
        case 5:
            $key = "de_education:singleChoiceLibrary:fiveStar";
            break;
        case 6:
            $key = "de_education:singleChoiceLibrary:sixStar";
            break;
    }
    if ($key) {
        return $redis->sRandMember($key, $count);
    } else {
        return [];
    }
}

//将单选题移出题库缓存
function removeSingleChoice($singleChoiceUuid, $difficultyLevel, Redis $redis) {
    $key = "";
    switch ($difficultyLevel) {
        case 1:
            $key = "de_education:singleChoiceLibrary:oneStar";
            break;
        case 2:
            $key = "de_education:singleChoiceLibrary:twoStar";
            break;
        case 3:
            $key = "de_education:singleChoiceLibrary:threeStar";
            break;
        case 4:
            $key = "de_education:singleChoiceLibrary:fourStar";
            break;
        case 5:
            $key = "de_education:singleChoiceLibrary:fiveStar";
            break;
        case 6:
            $key = "de_education:singleChoiceLibrary:sixStar";
            break;
    }
    if ($key) {
        $redis->sRem($key, $singleChoiceUuid);
    }
}

//将作文题放入题库缓存
function addWriting($writingUuid, $difficultyLevel, Redis $redis) {
    $key = "";
    switch ($difficultyLevel) {
        case 1:
            $key = "de_education:writingLibrary:oneStar";
            break;
        case 2:
            $key = "de_education:writingLibrary:twoStar";
            break;
        case 3:
            $key = "de_education:writingLibrary:threeStar";
            break;
        case 4:
            $key = "de_education:writingLibrary:fourStar";
            break;
        case 5:
            $key = "de_education:writingLibrary:fiveStar";
            break;
        case 6:
            $key = "de_education:writingLibrary:sixStar";
            break;
    }
    if ($key) {
        $redis->sadd($key, $writingUuid);
    }
}


//将作文题放入题库缓存
function addAllWriting(array $writingUuids, $difficultyLevel, Redis $redis) {
    $key = "";
    switch ($difficultyLevel) {
        case 1:
            $key = "de_education:writingLibrary:oneStar";
            break;
        case 2:
            $key = "de_education:writingLibrary:twoStar";
            break;
        case 3:
            $key = "de_education:writingLibrary:threeStar";
            break;
        case 4:
            $key = "de_education:writingLibrary:fourStar";
            break;
        case 5:
            $key = "de_education:writingLibrary:fiveStar";
            break;
        case 6:
            $key = "de_education:writingLibrary:sixStar";
            break;
    }
    if ($key) {
        $redis->sAddArray($key, $writingUuids);
    }
}

//随机获取作文题
function getRandomWriting($difficultyLevel, $count, Redis $redis) {
    $key = "";
    switch ($difficultyLevel) {
        case 1:
            $key = "de_education:writingLibrary:oneStar";
            break;
        case 2:
            $key = "de_education:writingLibrary:twoStar";
            break;
        case 3:
            $key = "de_education:writingLibrary:threeStar";
            break;
        case 4:
            $key = "de_education:writingLibrary:fourStar";
            break;
        case 5:
            $key = "de_education:writingLibrary:fiveStar";
            break;
        case 6:
            $key = "de_education:writingLibrary:sixStar";
            break;
    }
    if ($key) {
        return $redis->sRandMember($key, $count);
    } else {
        return [];
    }
}

//将作文题移出题库缓存
function removeWriting($writingUuid, $difficultyLevel, Redis $redis) {
    $key = "";
    switch ($difficultyLevel) {
        case 1:
            $key = "de_education:writingLibrary:oneStar";
            break;
        case 2:
            $key = "de_education:writingLibrary:twoStar";
            break;
        case 3:
            $key = "de_education:writingLibrary:threeStar";
            break;
        case 4:
            $key = "de_education:writingLibrary:fourStar";
            break;
        case 5:
            $key = "de_education:writingLibrary:fiveStar";
            break;
        case 6:
            $key = "de_education:writingLibrary:sixStar";
            break;
    }
    if ($key) {
        $redis->sRem($key, $writingUuid);
    }
}

//将判断题放入题库缓存
function addTrueFalseQuestionArray(array $trueFalseQuestionList, Redis $redis) {
    $list = [
        "one" => [],
        "two" => [],
        "three" => [],
        "four" => [],
        "five" => [],
        "six" => [],
    ];

    foreach ($trueFalseQuestionList as $trueFalseQuestion) {
        if ($trueFalseQuestion["is_use"] == 1) {
            switch ($trueFalseQuestion["difficulty_level"]) {
                case 1:
                    $list["one"][] = $trueFalseQuestion["uuid"];
                    break;
                case 2:
                    $list["two"][] = $trueFalseQuestion["uuid"];
                    break;
                case 3:
                    $list["three"][] = $trueFalseQuestion["uuid"];
                    break;
                case 4:
                    $list["four"][] = $trueFalseQuestion["uuid"];
                    break;
                case 5:
                    $list["five"][] = $trueFalseQuestion["uuid"];
                    break;
                case 6:
                    $list["six"][] = $trueFalseQuestion["uuid"];
                    break;
            }
        }
    }
    if (count($list["one"]) != 0) {
        $key = "de_education:trueFalseQuestionLibrary:oneStar";
        $redis->sAddArray($key, $list["one"]);
    }
    if (count($list["two"]) != 0) {
        $key = "de_education:trueFalseQuestionLibrary:twoStar";
        $redis->sAddArray($key, $list["two"]);
    }
    if (count($list["three"]) != 0) {
        $key = "de_education:trueFalseQuestionLibrary:threeStar";
        $redis->sAddArray($key, $list["three"]);
    }
    if (count($list["four"]) != 0) {
        $key = "de_education:trueFalseQuestionLibrary:fourStar";
        $redis->sAddArray($key, $list["four"]);
    }
    if (count($list["five"]) != 0) {
        $key = "de_education:trueFalseQuestionLibrary:fiveStar";
        $redis->sAddArray($key, $list["five"]);
    }if (count($list["six"]) != 0) {
        $key = "de_education:trueFalseQuestionLibrary:sixStar";
        $redis->sAddArray($key, $list["six"]);
    }
}

//将判断题放入题库缓存
function addAllTrueFalseQuestion(array $trueFalseQuestionUuids, $difficultyLevel, Redis $redis) {
    $key = "";
    switch ($difficultyLevel) {
        case 1:
            $key = "de_education:trueFalseQuestionLibrary:oneStar";
            break;
        case 2:
            $key = "de_education:trueFalseQuestionLibrary:twoStar";
            break;
        case 3:
            $key = "de_education:trueFalseQuestionLibrary:threeStar";
            break;
        case 4:
            $key = "de_education:trueFalseQuestionLibrary:fourStar";
            break;
        case 5:
            $key = "de_education:trueFalseQuestionLibrary:fiveStar";
            break;
        case 6:
            $key = "de_education:trueFalseQuestionLibrary:sixStar";
            break;
    }
    if ($key) {
        $redis->sAddArray($key, $trueFalseQuestionUuids);
    }
}

//将判断题放入题库缓存
function addTrueFalseQuestion($trueFalseQuestionUuid, $difficultyLevel, Redis $redis) {
    $key = "";
    switch ($difficultyLevel) {
        case 1:
            $key = "de_education:trueFalseQuestionLibrary:oneStar";
            break;
        case 2:
            $key = "de_education:trueFalseQuestionLibrary:twoStar";
            break;
        case 3:
            $key = "de_education:trueFalseQuestionLibrary:threeStar";
            break;
        case 4:
            $key = "de_education:trueFalseQuestionLibrary:fourStar";
            break;
        case 5:
            $key = "de_education:trueFalseQuestionLibrary:fiveStar";
            break;
        case 6:
            $key = "de_education:trueFalseQuestionLibrary:sixStar";
            break;
    }
    if ($key) {
        $redis->sadd($key, $trueFalseQuestionUuid);
    }
}

//随机获取判断题
function getTrueFalseQuestion($difficultyLevel, $count, Redis $redis) {
    $key = "";
    switch ($difficultyLevel) {
        case 1:
            $key = "de_education:trueFalseQuestionLibrary:oneStar";
            break;
        case 2:
            $key = "de_education:trueFalseQuestionLibrary:twoStar";
            break;
        case 3:
            $key = "de_education:trueFalseQuestionLibrary:threeStar";
            break;
        case 4:
            $key = "de_education:trueFalseQuestionLibrary:fourStar";
            break;
        case 5:
            $key = "de_education:trueFalseQuestionLibrary:fiveStar";
            break;
        case 6:
            $key = "de_education:trueFalseQuestionLibrary:sixStar";
            break;
    }
    if ($key) {
        return $redis->sRandMember($key, $count);
    } else {
        return [];
    }
}

//将判断题移出题库缓存
function removeTrueFalseQuestion($trueFalseQuestionUuid, $difficultyLevel, Redis $redis) {
    $key = "";
    switch ($difficultyLevel) {
        case 1:
            $key = "de_education:trueFalseQuestionLibrary:oneStar";
            break;
        case 2:
            $key = "de_education:trueFalseQuestionLibrary:twoStar";
            break;
        case 3:
            $key = "de_education:trueFalseQuestionLibrary:threeStar";
            break;
        case 4:
            $key = "de_education:trueFalseQuestionLibrary:fourStar";
            break;
        case 5:
            $key = "de_education:trueFalseQuestionLibrary:fiveStar";
            break;
        case 6:
            $key = "de_education:trueFalseQuestionLibrary:sixStar";
            break;
    }
    if ($key) {
        $redis->sRem($key, $trueFalseQuestionUuid);
    }
}

//缓存题库任务放到redis队列
function pushCacheQuestionLibraryList($questionType, $difficultyLevel, Redis $redis) {
    $key = "de_education:cacheQuestionLibraryList";

    $value = [
        "question_type" => $questionType,
        "difficulty_level" => $difficultyLevel
    ];

    $redis->rPush($key, json_encode($value));
}

//弹出缓存题库任务
function getCacheQuestionLibraryList(\Redis $redis) {
    $key = "de_education:cacheQuestionLibraryList";

    $data = $redis->blPop([$key], 10);

    return $data;
}

//用户今日通过分享获取书币次数+1
function addUserGetCoinByShareTimes($userUuid, Redis $redis) {
    $todayDate = date("Y-m-d");
    $key = "de_education:getCoinByShareTimes:$userUuid:$todayDate";

    $times = $redis->incr($key);
    if ($times == 1) {
        $redis->expire($key, 86400);
    }
}

//用户今日通过分享获取书币次数
function userGetCoinByShareTimes($userUuid, Redis $redis) {
    $todayDate = date("Y-m-d");
    $key = "de_education:getCoinByShareTimes:$userUuid:$todayDate";
    return (int) $redis->get($key);
}

//将用户领取书币的操作放到redis队列
function pushAddTaskList($userUuid, $addType, Redis $redis) {
    $key = "de_education:addCoinListByFinishTask";

    $value = [
        "uuid" => $userUuid,
        "add_type" => $addType,
    ];

    $redis->rPush($key, json_encode($value));
}

//弹出待领取的奖励
function getAddCoinList(\Redis $redis) {
    $key = "de_education:addCoinListByFinishTask";

    $data = $redis->blPop([$key], 10);

    return $data;
}

//用户当月领取的连续签到奖励
function cacheMonthContinuousSignReward($userUuid, array $rewardList, \Redis $redis) {
    $month = date("Y-m");
    $key = "de_education:monthContinuousSignReward:$userUuid:$month";

    $redis->setex($key, 86400*31, json_encode($rewardList));
}

//用户当月领取的连续签到奖励
function currentMonthContinuousSignReward($userUuid, \Redis $redis) {
    $month = date("Y-m");
    $key = "de_education:monthContinuousSignReward:$userUuid:$month";

    $data = $redis->get($key);
    if (empty($data)) {
        return [];
    } else {
        return json_decode($data, true);
    }
}

//将用户领取书币的操作放到redis队列
function pushReceiveContinuousSignRewardList($user, $condition, Redis $redis) {
    $key = "de_education:receiveContinuousSignRewardList";

    $value = [
        "user" => $user,
        "condition" => $condition
    ];

    $redis->rPush($key, json_encode($value));
}

//弹出待领取的奖励
function getReceiveContinuousSignRewardList(\Redis $redis) {
    $key = "de_education:receiveContinuousSignRewardList";

    $data = $redis->blPop([$key], 10);

    return $data;
}

//用户学习模块填空题缓存
function getStudyFillTheBlanksCache($userUuid, $difficultyLevel, \Redis $redis) {
    $key = "de_education:studyFillTheBlanks:$difficultyLevel:$userUuid";
    $data = $redis->get($key);

    if ($data) {
        return json_decode($data, true);
    } else {
        return [];
    }
}

//缓存用户学习模块填空题
function cacheStudyFillTheBlanks($userUuid, $difficultyLevel, array $questionUuids, \Redis $redis) {
    $key = "de_education:studyFillTheBlanks:$difficultyLevel:$userUuid";
    $redis->set($key, json_encode($questionUuids));
}

//用户学习模块单选题缓存
function getStudySingleChoiceCache($userUuid, $difficultyLevel, \Redis $redis) {
    $key = "de_education:studySingleChoice:$difficultyLevel:$userUuid";
    $data = $redis->get($key);

    if ($data) {
        return json_decode($data, true);
    } else {
        return [];
    }
}

//缓存用户学习模块单选题
function cacheStudySingleChoice($userUuid, $difficultyLevel, array $questionUuids, \Redis $redis) {
    $key = "de_education:studySingleChoice:$difficultyLevel:$userUuid";
    $redis->set($key, json_encode($questionUuids));
}