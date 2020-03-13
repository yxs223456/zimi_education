<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

Route::get('think', function () {
    return 'hello,ThinkPHP5!';
});

Route::get('hello/:name', 'index/hello');

//上传文件
Route::rule('upload/index', 'api/upload/index', 'post');


//发送注册验证码
//Route::rule('user/getCodeForSignUp', 'api/user/getCodeForSignUp', 'post');
//手机号注册
Route::rule('user/signUp', 'api/user/signUp', 'post');
//发送登录验证码
//Route::rule('user/getCodeForSignIn', 'api/user/getCodeForSignIn', 'post');
//手机号验证码登录
Route::rule('user/signInByCode', 'api/user/signInByCode', 'post');
//手机号密码登录
Route::rule('user/signInByPassword', 'api/user/signInByPassword', 'post');
//发送重置密码验证码
//Route::rule('user/getCodeForResetPassword', 'api/user/getCodeForResetPassword', 'post');
//重置密码验
Route::rule('user/resetPassword', 'api/user/resetPassword', 'post');
//绑定微信
Route::rule('user/bindWeChat', 'api/user/bindWeChat', 'post');
//微信登录
Route::rule('user/weChatSignIn', 'api/user/weChatSignIn', 'post');
//微信登录
Route::rule('user/userInfo', 'api/user/userInfo', 'post');
//修改用户信息
Route::rule('user/modifyUserInfo', 'api/user/modifyUserInfo', 'post');


//任务中心首页
Route::rule('task/index', 'api/task/index', 'post');
//任务完成领取书币奖励
Route::rule('task/receiveCoin', 'api/task/receiveCoin', 'post');


//app检查更新
Route::rule('app/checkUpdate', 'api/app/checkUpdate', 'post');
//意见反馈
Route::rule('app/feedback', 'api/app/feedback', 'post');

return [

];
