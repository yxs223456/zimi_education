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

//上传单个文件
Route::rule('upload/index', 'api/upload/index', 'post');
//上传多个文件
Route::rule('upload/multiUpload', 'api/upload/multiUpload', 'post');


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
//微信绑定手机号
Route::rule('user/bindPhone', 'api/user/bindPhone', 'post');
//微信登录
Route::rule('user/userInfo', 'api/user/userInfo', 'post');
//修改用户信息
Route::rule('user/modifyUserInfo', 'api/user/modifyUserInfo', 'post');
//签到页面
Route::rule('user/signInfo', 'api/user/signInfo', 'post');
//用户签到
Route::rule('user/sign', 'api/user/sign', 'post');
//用户领取连续签到奖励
Route::rule('user/receiveContinuousSignReward', 'api/user/receiveContinuousSignReward', 'post');
//用户作文本
Route::rule('user/writingList', 'api/user/writingList', 'post');
//用户DE币流水
Route::rule('user/coinFlowList', 'api/user/coinFlowList', 'post');
//勋章墙
Route::rule('user/medals', 'api/user/medals', 'post');
//跟换个人勋章
Route::rule('user/updateSelfMedal', 'api/user/updateSelfMedal', 'post');


//任务中心首页
Route::rule('task/index', 'api/task/index', 'post');
//任务完成领取书币奖励
Route::rule('task/receiveCoin', 'api/task/receiveCoin', 'post');


//app检查更新
Route::rule('app/checkUpdate', 'api/app/checkUpdate', 'post');
//意见反馈
Route::rule('app/feedback', 'api/app/feedback', 'post');
//分享信息
Route::rule('app/share', 'api/app/share', 'post');


//获取新手测试题
Route::rule('novice/getQuestions', 'api/novice/getQuestions', 'post');
//提交新手测试成绩
Route::rule('novice/submitResult', 'api/novice/submitResult', 'post');


//学习模块，获取填空练习题
Route::rule('study/getFillTheBlanks', 'api/study/getFillTheBlanks', 'post');
//学习模块，获取单选练习题
Route::rule('study/getSingleChoice', 'api/study/getSingleChoice', 'post');
//学习模块，获取判断练习题
Route::rule('study/getTrueFalseQuestion', 'api/study/getTrueFalseQuestion', 'post');
//学习模块，获取作文练习题
Route::rule('study/getWriting', 'api/study/getWriting', 'post');
//学习模块，提交填空练习题
Route::rule('study/submitFillTheBlanks', 'api/study/submitFillTheBlanks', 'post');
//学习模块，提交单选练习题
Route::rule('study/submitSingleChoice', 'api/study/submitSingleChoice', 'post');
//学习模块，提交判断练习题
Route::rule('study/submitTrueFalseQuestion', 'api/study/submitTrueFalseQuestion', 'post');
//学习模块，提交作文练习题
Route::rule('study/submitWriting', 'api/study/submitWriting', 'post');


//综合练习模块，获取练习题
Route::rule('athletics/getSynthesize', 'api/athletics/getSynthesize', 'post');
//综合练习模块，提交练习题答案草稿
Route::rule('athletics/submitSynthesizeDraft', 'api/athletics/submitSynthesizeDraft', 'post');
//综合练习模块，获取练习题答案
Route::rule('athletics/submitSynthesize', 'api/athletics/submitSynthesize', 'post');
//综合练习成绩单
Route::rule('athletics/synthesizeReportCardList', 'api/athletics/synthesizeReportCardList', 'post');


//发起pk规则
Route::rule('athletics/initPkRule', 'api/athletics/initPkRule', 'post');
//发起pk
Route::rule('athletics/initPk', 'api/athletics/initPk', 'post');
//参与pk
Route::rule('athletics/joinPk', 'api/athletics/joinPk', 'post');
//pk列表
Route::rule('athletics/pkList', 'api/athletics/pkList', 'post');
//pk详情信息
Route::rule('athletics/pkInfo', 'api/athletics/pkInfo', 'post');
//提交pk答案
Route::rule('athletics/submitPkAnswer', 'api/athletics/submitPkAnswer', 'post');
//我的pk成绩单
Route::rule('athletics/pkReportCard', 'api/athletics/pkReportCard', 'post');
//我发起的pk
Route::rule('athletics/myInitPk', 'api/athletics/myInitPk', 'post');
//我参与的pk
Route::rule('athletics/myJointPk', 'api/athletics/myJointPk', 'post');


//内部大赛列表
Route::rule('athletics/competitionList', 'api/athletics/competitionList', 'post');
//内部大赛详情
Route::rule('athletics/competitionInfo', 'api/athletics/competitionInfo', 'post');
//内部大赛详情
Route::rule('athletics/joinCompetition', 'api/athletics/joinCompetition', 'post');
//提交内部大赛作品草稿
Route::rule('athletics/submitCompetitionDraft', 'api/athletics/submitCompetitionDraft', 'post');
//提交内部大赛作品
Route::rule('athletics/submitCompetition', 'api/athletics/submitCompetition', 'post');
//内部大赛成绩单
Route::rule('athletics/competitionReportCardList', 'api/athletics/competitionReportCardList', 'post');
//内部大赛成绩详情
Route::rule('athletics/competitionReportCardInfo', 'api/athletics/competitionReportCardInfo', 'post');
//内部大赛成绩详情用户列表
Route::rule('athletics/competitionReportCardUserList', 'api/athletics/competitionReportCardUserList', 'post');
//DE大赛主办方列表
Route::rule('athletics/competitionSponsorList', 'api/athletics/competitionSponsorList', 'post');


//综合测试排行榜
Route::rule('rank/synthesizeRank', 'api/rank/synthesizeRank', 'post');
//综合测试排行榜点赞
Route::rule('rank/synthesizeLike', 'api/rank/synthesizeLike', 'post');
//才情排行榜
Route::rule('rank/competitionRank', 'api/rank/competitionRank', 'post');
//才情排行榜点赞
Route::rule('rank/competitionLike', 'api/rank/competitionLike', 'post');
//pk排行榜
Route::rule('rank/pkRank', 'api/rank/pkRank', 'post');
//pk排行榜点赞
Route::rule('rank/pkLike', 'api/rank/pkLike', 'post');

return [

];
