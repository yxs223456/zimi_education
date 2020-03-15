<?php
/**
 * Created by PhpStorm.
 * User: yangxiushan
 * Date: 2020-03-12
 * Time: 13:49
 */

namespace app\api\controller;

use app\common\AppException;
use think\facade\Env;

class Upload extends Base
{
    protected $beforeActionList = [
        'checkAuth' => [
            'except' => 'index',
        ],
    ];

    public function index()
    {
        if (empty($_FILES['file'])) {
            throw AppException::factory(AppException::COM_PARAMS_ERR);
        }

        $tempFile = $_FILES['file']['tmp_name'];
        $fileName = md5(uniqid(mt_rand(), true)).".".strtolower(pathinfo($_FILES['file']['name'])["extension"]);
        $fileUrl = "static/api/" . $fileName;
        $filePath = "public/" . $fileUrl;

        move_uploaded_file($tempFile, Env::get("root_path") . $filePath);

        return $this->jsonResponse([
            "url" => $fileUrl,
        ]);
    }

}