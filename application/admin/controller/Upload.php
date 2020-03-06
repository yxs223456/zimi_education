<?php

namespace app\admin\controller;

use taobao\AliOss;

class Upload extends Common {

	//图片上传
    public function upload() {
       $file = request()->file('file');
       $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads/images');
       if($info){
            echo $info->getSaveName();
        }else{
            echo $file->getError();
        }
    }

    //图片上传至OSS
    public function uploadToOss() {
        $tempFile = $_FILES['file']['tmp_name'];
        $fileName = md5(uniqid(mt_rand(), true)).".".strtolower(pathinfo($_FILES['file']['name'])["extension"]);
        $info = AliOss::uploadContent(file_get_contents($tempFile),"image/".$fileName);
        //echo AliOss::getFileUrl("image/".$fileName);
        echo $info["info"]["url"];
    }

    //音频上传至OSS
    public function uploadAudioToOss() {

        $file = request()->file('file');

        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads' . DS .'audio');

        if($info){

            $fileName =  $_SERVER['DOCUMENT_ROOT'] . DS . 'uploads' . DS .'audio'. DS . $info->getSaveName();

            AliOss::uploadFile($fileName,"audio/".explode(DS,$info->getSaveName())[1]);

            $getID3 = new \getID3();

            $fileInfo = $getID3->analyze($fileName);

            $returnData["code"] = 200;
            $returnData["msg"] = "";
            $returnData["time"] = $_SERVER['REQUEST_TIME'];

            $returnData["data"]["url"] = AliOss::getFileUrl("audio/".explode(DS,$info->getSaveName())[1]);
            $returnData["data"]["playtime_seconds"] = round($fileInfo["playtime_seconds"],2);

            unset($fileName);

        }else{
            $returnData["code"] = -1;
            $returnData["msg"] = $file->getError();
            $returnData["time"] = $_SERVER['REQUEST_TIME'];
            $returnData["data"] = "";
        }

        echo json_encode($returnData);

    }

    // 图片上传至oss by xcj
    public function uploadImgToOss() {
        $tempFile = $_FILES['file']['tmp_name'];

        $fileName = md5(uniqid(mt_rand(), true)).".".strtolower(pathinfo($_FILES['file']['name'])["extension"]);

        $info = AliOss::uploadFile($tempFile,$fileName);

        $url = $info['info']['url'];
        $start = strrpos($url,"/");
        $imgName = substr($url, $start);
        return config('oss.Cname').$imgName;
//        return $this->getOssImgUrl($imgName);
    }

    //会员头像上传
    public function uploadface() {
       $file = request()->file('file');
       $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads/face');
       if($info){
            echo $info->getSaveName();
        }else{
            echo $file->getError();
        }
    }

    /**
     * 上传oss
     * @return string|\think\response\Json
     */
    public function uploadOssImage() {

        $param = $this->request->param();

        /**
         * validateType:  0不验证，2：验证等比，3：验证自定义宽高
         */

        // 验证必需字段是否存在
        if (!isset($param['fileName']) || !isset($param['validateType'])) {
            $returnData['code'] = -1;
            $returnData['msg'] = '参数错误';
            return json_encode($returnData);
        }

        // 判断如果验证类型为 自定义，判断是否传了宽高
        if ($param['validateType'] == 2 &&
            (!isset($param['width']) || !isset($param['height']))) {
            $returnData['code'] = -1;
            $returnData['msg'] = '参数错误';
            return json_encode($returnData);
        }

        if (!in_array($_FILES[$param['fileName']]['type'], ["image/jpeg", "image/png"])) {
            $returnData['code'] = -1;
            $returnData['msg'] = '图片格式错误';
            return json($returnData);
        }

        $tempFile = $_FILES[$param['fileName']]['tmp_name'];

        if ($param['validateType'] != 0) {

            $image = \think\Image::open($tempFile);

            // 返回图片的宽度
            $width = $image->width();
            // 返回图片的高度
            $height = $image->height();

            if ($param['validateType'] == 1) {

                if ($width != $height) {
                    $returnData['code'] = -1;
                    $returnData['msg'] = '图片宽高错误，必须1:1';
                    return json($returnData);
                }
            } else if ($param['validateType'] == 2) {

                if ($width != $param['width'] || $height != $param['height']) {
                    $returnData['code'] = -1;
                    $returnData['msg'] = "图片宽高错误，必须 ".$param['width']."*".$param['height'];
                    return json($returnData);
                }
            }

        }

        $fileName = md5(uniqid(mt_rand(), true)).".png";
        $info = AliOss::uploadFile($tempFile,$fileName);

        $url = $info['info']['url'];
        $start = strrpos($url,"/");
        $imgName = substr($url, $start);

        $returnData['code'] = 200;
        $returnData['msg'] = '上传成功';
        $returnData['data']['url'] = config('oss.Cname').$imgName;
        return json_encode($returnData);

    }

    // 百度富文本编辑器上传oss
    public function uploadEditorToOss() {

        $tempFile = $_FILES['upfile']['tmp_name'];

        $fileName = md5(uniqid(mt_rand(), true)).".".strtolower(pathinfo($_FILES['upfile']['name'])["extension"]);

        $info = AliOss::uploadFile($tempFile,$fileName);

        $url = $info['info']['url'];
        $start = strrpos($url,"/");
        $imgName = substr($url, $start);

        $returnData = array(
            "state" => "SUCCESS",
            "url" => config('oss.Cname').$imgName,
            "title" => "",
            "original" => "",
            "type" => ".png",
            "size" => ''
        );

        return json($returnData);

    }

}