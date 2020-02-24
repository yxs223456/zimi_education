<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2019-01-23
 * Time: 10:00
 */
namespace app\common\service;

use app\common\model\Users as CMUsers;
use app\common\model\Balance as CMBalance;

class Balance extends Base
{

    public function updateBalance($userId, $logType, $money, $detailNote = '', $source = 0, $sourceId = 0, $reduceType = 0, $reduceId = 0)
    {

        //获取用户
        $userModel = new CMUsers();
        $user = $userModel->findById($userId);
        if (isNullOrEmpty($user)) {
            return;
        }

        //如果是消耗num为负数
        if ($logType == config('enum.balanceLogType.reduce.value')) {
            $money = -abs($money);
        }

        // 增加余额日志
        $balanceModel = new CMBalance();
        $balanceData['userId'] = $userId;
        $balanceData['type'] = $logType;
        $balanceData['source'] = $source;
        $balanceData['sourceId'] = $sourceId;
        $balanceData['reduceType'] = $reduceType;
        $balanceData['reduceId'] = $reduceId;
        $balanceData['money'] = $money;
        $balanceData['beforeBalance'] = $user['balance'];
        $balanceData['afterBalance'] = $user['balance'] + $money;
        $balanceData['detailNote'] = $detailNote;
        $balanceData['createdDate'] = getCurrentDate();

        $balanceModel->saveByData($balanceData);

        // 更新用户账户
        $update = $userModel->where("id", $userId);
        if ($logType == config('enum.balanceLogType.add.value')) {
            $update->inc("balance", $money);

            if ($source != config('enum.balanceSource.withdrawFail.value')) {
                $update->inc("totalBalance", $money);
            }

        } else {
            $update->dec('balance', abs($money));
        }

        $update->update();

    }

}