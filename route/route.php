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

//发送注册验证码
Route::rule('user/getCodeForSignUp', 'api/user/getCodeForSignUp', 'post');
//手机号注册
Route::rule('user/signUp', 'api/user/signUp', 'post');
//发送登录验证码
Route::rule('user/getCodeForSignIn', 'api/user/getCodeForSignIn', 'post');
//手机号验证码注册
Route::rule('user/signInByCode', 'api/user/signInByCode', 'post');
//手机号密码注册
Route::rule('user/signInByPassword', 'api/user/signInByPassword', 'post');
//发送重置密码验证码
Route::rule('user/getCodeForResetPassword', 'api/user/getCodeForResetPassword', 'post');
//重置密码验
Route::rule('user/resetPassword', 'api/user/resetPassword', 'post');

return [

];
