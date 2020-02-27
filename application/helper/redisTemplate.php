<?php

//缓存用户信息
function cacheUserInfoByToken(array $userInfo, Redis $redis) {
    $key = "zimi_education:userInfoByToken:" . $userInfo["token"];
    $redis->hMSet($key, $userInfo);
    //缓存有效期72小时
    $redis->expire($key, 259200);

}

//通过token获取用户信息
function getUserInfoByToken($token, Redis $redis) {
    if ($token == "") {
        return [];
    }
    $key = "zimi_education:userInfoByToken:$token";
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
                    $list["one"][] = $fillTheBlanksList["uuid"];
                    break;
                case 2:
                    $list["two"][] = $fillTheBlanksList["uuid"];
                    break;
                case 3:
                    $list["three"][] = $fillTheBlanksList["uuid"];
                    break;
                case 4:
                    $list["four"][] = $fillTheBlanksList["uuid"];
                    break;
                case 5:
                    $list["five"][] = $fillTheBlanksList["uuid"];
                    break;
                case 6:
                    $list["six"][] = $fillTheBlanksList["uuid"];
                    break;
            }
        }
    }
    if (count($list["one"]) != 0) {
        $key = "zimi_education:fillTheBlanksLibrary:oneStar";
        $redis->sAddArray($key, $list["one"]);
    }
    if (count($list["two"]) != 0) {
        $key = "zimi_education:fillTheBlanksLibrary:twoStar";
        $redis->sAddArray($key, $list["two"]);
    }
    if (count($list["three"]) != 0) {
        $key = "zimi_education:fillTheBlanksLibrary:threeStar";
        $redis->sAddArray($key, $list["three"]);
    }
    if (count($list["four"]) != 0) {
        $key = "zimi_education:fillTheBlanksLibrary:fourStar";
        $redis->sAddArray($key, $list["four"]);
    }
    if (count($list["five"]) != 0) {
        $key = "zimi_education:fillTheBlanksLibrary:fiveStar";
        $redis->sAddArray($key, $list["five"]);
    }if (count($list["six"]) != 0) {
        $key = "zimi_education:fillTheBlanksLibrary:sixStar";
        $redis->sAddArray($key, $list["six"]);
    }
}

//将单选题放入题库缓存
function addFillTheBlanks($fillTheBlanksUuid, $difficultyLevel, Redis $redis) {
    $key = "";
    switch ($difficultyLevel) {
        case 1:
            $key = "zimi_education:fillTheBlanksLibrary:oneStar";
            break;
        case 2:
            $key = "zimi_education:fillTheBlanksLibrary:twoStar";
            break;
        case 3:
            $key = "zimi_education:fillTheBlanksLibrary:threeStar";
            break;
        case 4:
            $key = "zimi_education:fillTheBlanksLibrary:fourStar";
            break;
        case 5:
            $key = "zimi_education:fillTheBlanksLibrary:fiveStar";
            break;
        case 6:
            $key = "zimi_education:fillTheBlanksLibrary:sixStar";
            break;
    }
    if ($key) {
        $redis->sadd($key, $fillTheBlanksUuid);
    }
}

//将单选题移出题库缓存
function removeFillTheBlanks($fillTheBlanksUuid, $difficultyLevel, Redis $redis) {
    $key = "";
    switch ($difficultyLevel) {
        case 1:
            $key = "zimi_education:fillTheBlanksLibrary:oneStar";
            break;
        case 2:
            $key = "zimi_education:fillTheBlanksLibrary:twoStar";
            break;
        case 3:
            $key = "zimi_education:fillTheBlanksLibrary:threeStar";
            break;
        case 4:
            $key = "zimi_education:fillTheBlanksLibrary:fourStar";
            break;
        case 5:
            $key = "zimi_education:fillTheBlanksLibrary:fiveStar";
            break;
        case 6:
            $key = "zimi_education:fillTheBlanksLibrary:sixStar";
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
                    $list["one"][] = $singleChoiceList["uuid"];
                    break;
                case 2:
                    $list["two"][] = $singleChoiceList["uuid"];
                    break;
                case 3:
                    $list["three"][] = $singleChoiceList["uuid"];
                    break;
                case 4:
                    $list["four"][] = $singleChoiceList["uuid"];
                    break;
                case 5:
                    $list["five"][] = $singleChoiceList["uuid"];
                    break;
                case 6:
                    $list["six"][] = $singleChoiceList["uuid"];
                    break;
            }
        }
    }
    if (count($list["one"]) != 0) {
        $key = "zimi_education:singleChoiceLibrary:oneStar";
        $redis->sAddArray($key, $list["one"]);
    }
    if (count($list["two"]) != 0) {
        $key = "zimi_education:singleChoiceLibrary:twoStar";
        $redis->sAddArray($key, $list["two"]);
    }
    if (count($list["three"]) != 0) {
        $key = "zimi_education:singleChoiceLibrary:threeStar";
        $redis->sAddArray($key, $list["three"]);
    }
    if (count($list["four"]) != 0) {
        $key = "zimi_education:singleChoiceLibrary:fourStar";
        $redis->sAddArray($key, $list["four"]);
    }
    if (count($list["five"]) != 0) {
        $key = "zimi_education:singleChoiceLibrary:fiveStar";
        $redis->sAddArray($key, $list["five"]);
    }if (count($list["six"]) != 0) {
        $key = "zimi_education:singleChoiceLibrary:sixStar";
        $redis->sAddArray($key, $list["six"]);
    }
}

//将单选题放入题库缓存
function addSingleChoice($singleChoiceUuid, $difficultyLevel, Redis $redis) {
    $key = "";
    switch ($difficultyLevel) {
        case 1:
            $key = "zimi_education:singleChoiceLibrary:oneStar";
            break;
        case 2:
            $key = "zimi_education:singleChoiceLibrary:twoStar";
            break;
        case 3:
            $key = "zimi_education:singleChoiceLibrary:threeStar";
            break;
        case 4:
            $key = "zimi_education:singleChoiceLibrary:fourStar";
            break;
        case 5:
            $key = "zimi_education:singleChoiceLibrary:fiveStar";
            break;
        case 6:
            $key = "zimi_education:singleChoiceLibrary:sixStar";
            break;
    }
    if ($key) {
        $redis->sadd($key, $singleChoiceUuid);
    }
}

//将单选题移出题库缓存
function removeSingleChoice($singleChoiceUuid, $difficultyLevel, Redis $redis) {
    $key = "";
    switch ($difficultyLevel) {
        case 1:
            $key = "zimi_education:singleChoiceLibrary:oneStar";
            break;
        case 2:
            $key = "zimi_education:singleChoiceLibrary:twoStar";
            break;
        case 3:
            $key = "zimi_education:singleChoiceLibrary:threeStar";
            break;
        case 4:
            $key = "zimi_education:singleChoiceLibrary:fourStar";
            break;
        case 5:
            $key = "zimi_education:singleChoiceLibrary:fiveStar";
            break;
        case 6:
            $key = "zimi_education:singleChoiceLibrary:sixStar";
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
            $key = "zimi_education:writingLibrary:oneStar";
            break;
        case 2:
            $key = "zimi_education:writingLibrary:twoStar";
            break;
        case 3:
            $key = "zimi_education:writingLibrary:threeStar";
            break;
        case 4:
            $key = "zimi_education:writingLibrary:fourStar";
            break;
        case 5:
            $key = "zimi_education:writingLibrary:fiveStar";
            break;
        case 6:
            $key = "zimi_education:writingLibrary:sixStar";
            break;
    }
    if ($key) {
        $redis->sadd($key, $writingUuid);
    }
}

//将作文题移出题库缓存
function removeWriting($writingUuid, $difficultyLevel, Redis $redis) {
    $key = "";
    switch ($difficultyLevel) {
        case 1:
            $key = "zimi_education:writingLibrary:oneStar";
            break;
        case 2:
            $key = "zimi_education:writingLibrary:twoStar";
            break;
        case 3:
            $key = "zimi_education:writingLibrary:threeStar";
            break;
        case 4:
            $key = "zimi_education:writingLibrary:fourStar";
            break;
        case 5:
            $key = "zimi_education:writingLibrary:fiveStar";
            break;
        case 6:
            $key = "zimi_education:writingLibrary:sixStar";
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
                    $list["one"][] = $trueFalseQuestionList["uuid"];
                    break;
                case 2:
                    $list["two"][] = $trueFalseQuestionList["uuid"];
                    break;
                case 3:
                    $list["three"][] = $trueFalseQuestionList["uuid"];
                    break;
                case 4:
                    $list["four"][] = $trueFalseQuestionList["uuid"];
                    break;
                case 5:
                    $list["five"][] = $trueFalseQuestionList["uuid"];
                    break;
                case 6:
                    $list["six"][] = $trueFalseQuestionList["uuid"];
                    break;
            }
        }
    }
    if (count($list["one"]) != 0) {
        $key = "zimi_education:trueFalseQuestionLibrary:oneStar";
        $redis->sAddArray($key, $list["one"]);
    }
    if (count($list["two"]) != 0) {
        $key = "zimi_education:trueFalseQuestionLibrary:twoStar";
        $redis->sAddArray($key, $list["two"]);
    }
    if (count($list["three"]) != 0) {
        $key = "zimi_education:trueFalseQuestionLibrary:threeStar";
        $redis->sAddArray($key, $list["three"]);
    }
    if (count($list["four"]) != 0) {
        $key = "zimi_education:trueFalseQuestionLibrary:fourStar";
        $redis->sAddArray($key, $list["four"]);
    }
    if (count($list["five"]) != 0) {
        $key = "zimi_education:trueFalseQuestionLibrary:fiveStar";
        $redis->sAddArray($key, $list["five"]);
    }if (count($list["six"]) != 0) {
        $key = "zimi_education:trueFalseQuestionLibrary:sixStar";
        $redis->sAddArray($key, $list["six"]);
    }
}

//将判断题放入题库缓存
function addTrueFalseQuestion($trueFalseQuestionUuid, $difficultyLevel, Redis $redis) {
    $key = "";
    switch ($difficultyLevel) {
        case 1:
            $key = "zimi_education:trueFalseQuestionLibrary:oneStar";
            break;
        case 2:
            $key = "zimi_education:trueFalseQuestionLibrary:twoStar";
            break;
        case 3:
            $key = "zimi_education:trueFalseQuestionLibrary:threeStar";
            break;
        case 4:
            $key = "zimi_education:trueFalseQuestionLibrary:fourStar";
            break;
        case 5:
            $key = "zimi_education:trueFalseQuestionLibrary:fiveStar";
            break;
        case 6:
            $key = "zimi_education:trueFalseQuestionLibrary:sixStar";
            break;
    }
    if ($key) {
        $redis->sadd($key, $trueFalseQuestionUuid);
    }
}

//将判断题移出题库缓存
function removeTrueFalseQuestion($trueFalseQuestionUuid, $difficultyLevel, Redis $redis) {
    $key = "";
    switch ($difficultyLevel) {
        case 1:
            $key = "zimi_education:trueFalseQuestionLibrary:oneStar";
            break;
        case 2:
            $key = "zimi_education:trueFalseQuestionLibrary:twoStar";
            break;
        case 3:
            $key = "zimi_education:trueFalseQuestionLibrary:threeStar";
            break;
        case 4:
            $key = "zimi_education:trueFalseQuestionLibrary:fourStar";
            break;
        case 5:
            $key = "zimi_education:trueFalseQuestionLibrary:fiveStar";
            break;
        case 6:
            $key = "zimi_education:trueFalseQuestionLibrary:sixStar";
            break;
    }
    if ($key) {
        $redis->sRem($key, $trueFalseQuestionUuid);
    }
}