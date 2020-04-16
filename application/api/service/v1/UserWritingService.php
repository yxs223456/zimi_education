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
            ];
        }

        return $returnData;
    }
}