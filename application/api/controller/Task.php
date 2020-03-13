<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-12
 * Time: 17:48
 */

namespace app\api\controller;

use app\api\service\TaskService;
use app\common\AppException;
use app\common\enum\UserCoinAddTypeEnum;

class Task extends Base
{
    protected $beforeActionList = [
        'checkAuth' => [
            'except' => '',
        ],
    ];

    //任务中心首页
    public function index()
    {
        $user = $this->query["user"];

        $taskService = new TaskService();
        return $this->jsonResponse($taskService->list($user));
    }

    public function receiveCoin()
    {
        $type = input("type");
        $coinAddTypes = [
            UserCoinAddTypeEnum::USER_INFO,
            UserCoinAddTypeEnum::PARENT_INVITE_CODE,
            UserCoinAddTypeEnum::BIND_WE_CHAT,
            UserCoinAddTypeEnum::SHARE,
        ];
        if (!in_array($type, $coinAddTypes)) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }
        $user = $this->query["user"];

        $taskService = new TaskService();
        return $this->jsonResponse($taskService->receiveCoin($user, $type));
    }
}