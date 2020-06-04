<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-04-16
 * Time: 17:04
 */

namespace app\api\service\v1;

use app\api\service\Base;
use app\common\AppException;
use app\common\enum\UserWritingSourceTypeEnum;
use app\common\model\UserWritingModel;

class UserWritingService extends Base
{
    public function myWritingList($user, $pageNum, $pageSize)
    {
        $userWritingModel = new UserWritingModel();
        $userWritingList = $userWritingModel->myWritingList($user["uuid"], $pageNum, $pageSize);

        $returnData = [];
        foreach ($userWritingList as $item) {
            $contents = json_decode($item["content"], true);
            if (isset($contents["image"]["images"])) {
                foreach ($contents["image"]["images"] as $key=>$image) {
                    $contents["images"]["image"][$key] = getImageUrl($image);
                }
            }

            $tags = [UserWritingSourceTypeEnum::getEnumDescByValue($item["source_type"])];
            if (!empty($contents["text"]["content"])) {
                $tags[] = mb_strlen($contents["text"]["content"]) . "字";
            }

            $returnData[] = [
                "source_type" => $item["source_type"],
                "difficulty_level" => $item["difficulty_level"],
                "topic" => $item["topic"],
                "requirements" => json_decode($item["requirements"], true),
                "contents" => $contents,
                "is_comment" => $item["is_comment"],
                "total_score" => (string) (int) $item["total_score"],
                "tags" => $tags,
                "score" => (string) (int) $item["score"],
                "comment" => $item["comment"],
                "comment_level" => $item["is_comment"]?$this->getCommentLevel($item["total_score"], $item["score"]):0,
            ];
        }

        return $returnData;
    }

    public function writingListBySourceType($user, $pageNum, $pageSize, $sourceType)
    {
        $userWritingModel = new UserWritingModel();
        if ($sourceType == UserWritingSourceTypeEnum::STUDY) {
            $userWritingList = $userWritingModel->studyWritingList($user["uuid"], $pageNum, $pageSize);
        } else if ($sourceType == UserWritingSourceTypeEnum::SYNTHESIZE) {
            $userWritingList = $userWritingModel->synthesizeWritingList($user["uuid"], $pageNum, $pageSize);
        } else {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $returnData = [];
        foreach ($userWritingList as $item) {
            $contents = json_decode($item["content"], true);
            if (isset($contents["image"]["images"])) {
                foreach ($contents["image"]["images"] as $key=>$image) {
                    $contents["images"]["image"][$key] = getImageUrl($image);
                }
            }

            $tags = [];
            if (!empty($contents["text"]["content"])) {
                $tags[] = mb_strlen($contents["text"]["content"]) . "字";
            }

            $returnData[] = [
                "difficulty_level" => $item["difficulty_level"],
                "topic" => $item["topic"],
                "requirements" => json_decode($item["requirements"], true),
                "contents" => $contents,
                "is_comment" => $item["is_comment"],
                "total_score" => (string) (int) $item["total_score"],
                "tags" => $tags,
                "score" => (string) (int) $item["score"],
                "comment" => $item["comment"],
                "comment_level" => $item["is_comment"]?$this->getCommentLevel($item["total_score"], $item["score"]):0,
                "submit_time" => $item["create_time"],
                "comment_time" => $item["is_comment"]?date("Y-m-d H:i:s", $item["comment_time"]):"",
            ];
        }

        return $returnData;
    }

    public function getCommentLevel($totalScore, $score)
    {
        //满分100分等级：优秀 90-100分，良好80-90，及格60-80,不及格以下
        //满分30分等级：27-30,24-27,18-24,18分以下不合格
        $commentLevel = "";
        if ($totalScore == 100) {
            if ($score >= 90) {
                $commentLevel = 4;
            } else if ($score >= 80) {
                $commentLevel = 3;
            } else if ($score >= 60) {
                $commentLevel = 2;
            } else {
                $commentLevel = 1;
            }
        } else if ($totalScore == 30) {
            if ($score >= 27) {
                $commentLevel = 4;
            } else if ($score >= 24) {
                $commentLevel = 3;
            } else if ($score >= 18) {
                $commentLevel = 2;
            } else {
                $commentLevel = 1;
            }
        }

        return $commentLevel;
    }
}