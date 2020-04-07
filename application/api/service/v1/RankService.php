<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-04-07
 * Time: 16:52
 */

namespace app\api\service\v1;

use app\api\service\Base;
use app\common\helper\Redis;
use app\common\model\UserSynthesizeRankModel;

class RankService extends Base
{
    public function synthesizeRank($user, $difficultyLevel)
    {
        $userSynthesizeRankModel = new UserSynthesizeRankModel();
        $rankList = $userSynthesizeRankModel->getRank($difficultyLevel);
        foreach ($rankList as $key=>$item) {
            $rankList[$key]["nickname"] = getNickname($item["nickname"]);
            $rankList[$key]["head_image_url"] = getHeadImageUrl($item["head_image_url"]);
            $rankList[$key]["rank"] = $key+1;
        }

        $myRank = [
            "head_image_url" => getHeadImageUrl($user["head_image_url"]),
            "nickname" => getNickname($user["nickname"]),
            "rank" => $userSynthesizeRankModel->getUserSynthesizeRank($user["uuid"], $difficultyLevel),
        ];

        $redis = Redis::factory();
        $carousel = getSynthesizeUpdateList($difficultyLevel, $redis);

        return [
            "rank_list" => $rankList,
            "my_rank" => $myRank,
            "carousel" => $carousel,
        ];
    }
}