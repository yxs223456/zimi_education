<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-04-16
 * Time: 17:04
 */

namespace app\api\service\v1;

use app\api\service\Base;
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
            if (isset($contents["images"])) {
                foreach ($contents["images"] as $key=>$image) {
                    $contents["images"][$key] = getImageUrl($image);
                }
            }

            $returnData[] = [
                "source_type" => $item["source_type"],
                "difficulty_level" => $item["difficulty_level"],
                "topic" => $item["topic"],
                "requirements" => json_decode($item["requirements"], true),
                "contents" => $contents,
                "is_comment" => $item["is_comment"],
                "total_score" => (int) $item["total_score"],
                "score" => (int) $item["score"],
                "comment" => $item["comment"],
                "comment_level" => $item["is_comment"]?$this->getCommentLevel($item["total_score"], $item["score"]):"",
            ];
        }

        return $returnData;
    }

    public function getCommentLevel($totalScore, $score)
    {
        //满分100分等级：优秀 90-100分，比较优秀80-90，合格70-80,一般60-70,不及格以下
        //满分30分等级：27-30,24-27,21-24,18-21,18分以下不合格
        $commentLevel = "";
        if ($totalScore == 100) {
            if ($score >= 90) {
                $commentLevel = "优秀";
            } else if ($score >= 80) {
                $commentLevel = "比较优秀";
            } else if ($score >= 70) {
                $commentLevel = "合格";
            } else if ($score >= 60) {
                $commentLevel = "一般";
            } else {
                $commentLevel = "不及格";
            }
        } else if ($totalScore == 30) {
            if ($score >= 27) {
                $commentLevel = "优秀";
            } else if ($score >= 24) {
                $commentLevel = "比较优秀";
            } else if ($score >= 21) {
                $commentLevel = "合格";
            } else if ($score >= 18) {
                $commentLevel = "一般";
            } else {
                $commentLevel = "不及格";
            }
        }

        return $commentLevel;
    }
}