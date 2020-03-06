var app = new Vue({
    el: '#section-details',
    data: {
        sectionDetails: [],
        sectionComment: [],
        html: '',
        isFree: false,
        isAvailable: true,
        isCommentPoint: true,
        isMatchTime: true,
        remainingDays: 0
    },
    methods: {
        purchaseVip: function() {
            window.location.href = '/wechat/center/purchaseVip?is_vip=' + false;
        },
        goToComment: function(item_id) {
            window.location.href = '/wechat/course/courseComment?item_id=' + item_id;
        },
        goToReward: function(item_id) {
            window.location.href = '/wechat/course/giveReward?item_id=' + item_id;
        },
        index: function() {
            window.location.href = "/wechat";
        },
        myCenter: function() {
            window.location.href = "/wechat/center/userCenter";
        },
        top: function() {
            $('body,html').animate({scrollTop:0},300);
        },
        purchaseCourse: function(course_id) {
            $("#loadingToast").show();
            $.ajax({
                url:urlOrderCourse,
                type:"POST",
                async:false,
                data:{
                    course_id: course_id
                },
                dataType:"JSON",
                success:function(data){
                    $('#loadingToast').hide();
                    if(data.code==200){
                        window.location.href = "/wechat/center/orderInfo?id="+data.data.id;
                    } else {
                        alert(data.msg);
                        window.location.href = "/wechat";
                    }
                }
            })

        }
    }
})

var isNeedRefresh = true;
var page_num = 1;
var weixinAudioObj = [];
var weixinAudio = [];
var weixinAudioGif = [];
var i = 0;
var click = 1;
var flag = true;
var remainNumberAudio = 0;
var NumberAudio = 0;
var nowAudio = 0;

var switchHtml = "<div class=\"play-key\" style=\"display:none;position:absolute;top:19px;margin-left:1rem\"><img src=\"/static/wechat/image/play-key.png\" alt=\"play-key\"></div>";

$(function() {

    getDetails();

    $(window).scroll(function(){
        var scrollTop = $(this).scrollTop();
        var scrollHeight = $(document).height();
        var windowHeight = $(this).height();
        if(!isNeedRefresh &&
            (scrollHeight - scrollTop - windowHeight <= 25)){
            isNeedRefresh = false;
            page_num = page_num + 1;
            getDetails();
        }
    });

    $(".play-key").on("click", function() {

        var targetDom = $(this).prev().find(".weixinAudio");

        targetDom.trigger("click");

    });

    $('.weixinAudio').on('click', function(event) {

        if(app.isAvailable == false && app.isFree == false) {
            $("button.buy-course").effect("shake", {
                distance: 3
            });
            return false;
        }

        event.preventDefault();
        /* Act on the event */
        var weixin = $(this);

        var playing = $(".weixinAudio.playing");

        var _thisAudio = weixin.find('audio')[0];

        $(".play-key").css("display", "none");

        //判断是否为第一次点击
        if(isNullOrEmpty(playing.attr("class"))) {
            weixin.addClass("playing");
            //去除红点
            weixin.siblings('.red-point').hide();
            //动态小喇叭
            weixin.siblings('.audiowx').children('img').attr("src","/static/admin/images/player.gif").css({"width":"13px","height":"17px","z-index":"1000"});
            //播放音频
            _thisAudio.play();

        } else {

            //判断点击的是否为同一个音频
            if(weixin.hasClass("playing")) {

                //判断当前播放状态，是播放中还是暂停中
                if(_thisAudio.paused) {

                    //如果是停止状态，开启动态小喇叭，并继续播放
                    _thisAudio.play();
                    weixin.siblings('.audiowx').children('img').attr("src","/static/admin/images/player.gif").css({"width":"13px","height":"16px","z-index":"1000"});

                } else {

                    //如果是播放状态，关闭动态小喇叭，并暂停播放
                    _thisAudio.pause();
                    weixin.siblings('.audiowx').children('img').attr("src","/static/admin/images/icon-voice.png").css({"width":"13px","height":"16px"});

                    weixin.parent().next().show();

                }

            } else {

                //解绑上一个播放源的end事件
                playing.find("audio")[0].removeEventListener('ended', function(e) {

                    if(remainNumberAudio > 0) {

                        //获取下一个要播的组元素
                        var nextAudio = weixinAudioObj[nowAudio].parent();

                        var _nextAudio = nextAudio.find("audio")[0];

                        //停止当前音频、关闭动态小喇叭
                        current.siblings('.audiowx').children('img').attr("src","/static/admin/images/icon-voice.png").css({"width":"13px","height":"16px"});
                        current.find("audio")[0].pause();
                        current.find("audio")[0].currentTime = 0;

                        //开启下一音频、打开动态小喇叭、去掉红色小点
                        nextAudio.siblings('.red-point').hide();
                        //动态小喇叭
                        nextAudio.siblings('.audiowx').children('img').attr("src","/static/admin/images/player.gif").css({"width":"13px","height":"17px","z-index":"1000"});

                        //暂时hack解决动态图不出现问题
                        nextAudio.prev().css("z-index", 100);
                        setTimeout(function() {
                            nextAudio.prev().css("z-index", 1200);
                        },100);

                        // wx.getNetworkType({
                        //     success: function (e) {
                        //         _nextAudio.play();
                        //     }
                        // });

                        _nextAudio.play();
                        //去除所有标记为playing的元素，并把下一个audio元素赋值为playing
                        $(".weixinAudio").each(function() {
                            if($(this).hasClass("playing")) {
                                $(this).removeClass("playing");
                            }
                        });
                        nextAudio.addClass("playing");

                        loopSound(_nextAudio);

                    } else {

                        //停止动态小喇叭
                        current.siblings('.audiowx').children('img').attr("src","/static/admin/images/icon-voice.png").css({"width":"13px","height":"16px"});

                    }

                });

                //停止上一个播放源，并停止上一个动态小喇叭
                playing.siblings('.audiowx').children('img').attr("src","/static/admin/images/icon-voice.png").css({"width":"13px","height":"16px"});
                playing.find("audio")[0].pause();
                playing.find("audio")[0].currentTime = 0;

                //开启当前音频、开启当前音频的动态小喇叭、关闭小红点
                weixin.siblings('.red-point').hide();
                weixin.siblings('.audiowx').children('img').attr("src","/static/admin/images/player.gif").css({"width":"13px","height":"16px","z-index":"1000"});
                //暂时hack解决动态图不出现问题
                weixin.prev().css("z-index", 100);
                setTimeout(function() {
                    weixin.prev().css("z-index", 1200);
                },100);
                _thisAudio.play();

                //去除所有标记为playing的元素，并把当前元素赋值为playing
                $(".weixinAudio").each(function() {
                    if($(this).hasClass("playing")) {
                        $(this).removeClass("playing");
                    }
                });

                weixin.addClass("playing");

            }

        }

        //去循环播放
        loopSound(_thisAudio);

    });


})

var bindNext = function() {

    var current = $(".weixinAudio.playing");

    if(remainNumberAudio > 0) {

        //获取下一个要播的组元素
        var nextAudio = weixinAudioObj[nowAudio].parent();

        var _nextAudio = nextAudio.find("audio")[0];

        //停止当前音频、关闭动态小喇叭
        current.siblings('.audiowx').children('img').attr("src","/static/admin/images/icon-voice.png").css({"width":"13px","height":"16px"});
        current.find("audio")[0].pause();
        current.find("audio")[0].currentTime = 0;

        //开启下一音频、打开动态小喇叭、去掉红色小点
        nextAudio.siblings('.red-point').hide();
        //动态小喇叭
        nextAudio.siblings('.audiowx').children('img').attr("src","/static/admin/images/player.gif").css({"width":"13px","height":"17px","z-index":"1000"});

        //暂时hack解决动态图不出现问题
        nextAudio.prev().css("z-index", 100);
        setTimeout(function() {
            nextAudio.prev().css("z-index", 1200);
        },100);

        wx.getNetworkType({
            success: function (e) {
                _nextAudio.play();
            }
        });

        //去除所有标记为playing的元素，并把下一个audio元素赋值为playing
        $(".weixinAudio").each(function() {
            if($(this).hasClass("playing")) {
                $(this).removeClass("playing");
            }
        });
        nextAudio.addClass("playing");

        loopSound(_nextAudio);

    } else {
        //停止动态小喇叭
        current.siblings('.audiowx').children('img').attr("src","/static/admin/images/icon-voice.png").css({"width":"13px","height":"16px"});
    }
}


function loopSound(currentAudio) {

    var current = $(".weixinAudio.playing");

    //计算当前语音播放次序
    remainNumberAudio = current.parent().parent()
        .parent().parent().parent()
        .nextAll(".app-field.clearfix[type='audio']").length;
    NumberAudio = $(".app-field.clearfix[type='audio']").length;
    nowAudio = NumberAudio - remainNumberAudio;

    //监听锁屏播放时，外部停止、开启后，内部网页的展示效果
    currentAudio.addEventListener('pause', function(e) {

        if(currentAudio.paused) {
            //关闭小喇叭动态效果
            current.siblings('.audiowx').children('img')
                .attr("src","/static/admin/images/icon-voice.png")
                .css({"width":"13px","height":"16px"});
        } else {
            //关闭小喇叭动态效果
            current.siblings('.audiowx').children('img')
                .attr("src","/static/admin/images/player.gif")
                .css({"width":"13px","height":"16px","z-index":"1000"});
            current.parent().next().show();
        }

    });

    currentAudio.addEventListener('ended', bindNext, false);

}

function getDetails() {
    $.ajax({
        url:urlSectionContent,
        type:"POST",
        async:false,
        data:{
            item_id: $.getUrlParam('item_id'),
            page_num: page_num
        },
        dataType:"JSON",
        success:function(data){
            if(data.code==200){

                app.sectionDetails = data.data.info;
                app.html = data.data.info.content;

                app.isFree = data.data.is_free;
                app.isAvailable = data.data.is_available;
                app.isMatchTime = data.data.is_match_time;
                app.remainingDays = data.data.remaining_days;
                app.isCommentPoint = data.data.is_comment_point;

                document.title = data.data.info.title;

                var re = /(http:\/\/[\w.\/]+)(?![^<]+>)/gi;
                $('.section-content').html(app.html.replace(re,"<a href='$1'>$1</a>"));

                $(".custom-audio-weixin").each(function() {
                    if($(this).find(".weixinAudio").attr("class") != undefined) {
                        var switchLeft = $(this).find(".red-point").css('left').replace("px","")*1 + 25;

                        $(this).html($(this).parent().html() + switchHtml);

                        $(this).find(".play-key").css("left", switchLeft+"px");

                        $(this).find("img.js-animation").css("-webkit-tap-highlight-color", "rgba(255,255,255,0)");
                    }

                });

                if(app.isAvailable == false && app.isFree == false) {

                    $(".app-field.clearfix[type=rich_text] img").each(function() {
                        $(this).addClass("no-access");
                    });

                    // $(".audioCss.weixinAudio").each(function() {
                    //     $(this).addClass("no-access-audio");
                    // });
                    //
                    // $(".red-point").each(function() {
                    //     $(this).addClass("no-access-audio");
                    // });
                    //
                    // $(".play-time").each(function() {
                    //     $(this).addClass("no-access-audio");
                    // });
                    //
                    // $(".audioIcon.audiowx").each(function() {
                    //     $(this).addClass("no-access-audio");
                    // });

                    // $(".word").each(function () {
                    //     $(this).addClass("no-access-audio");
                    // });

                    // $("video").each(function () {
                    //     $(this).attr("disabled", "disabled");
                    // });

                }

                $('.app-field.clearfix').each(function(){
                    $(this).children('.actions').remove();
                    $(this).children('.sort').remove();
                    $(this).removeAttr("onclick");
                });
                if(!isNullOrEmpty(data.data.comment_list)) {
                    if(page_num == 1){
                        app.sectionComment = data.data.comment_list;
                    }else{
                        app.sectionComment = app.sectionComment.concat(data.data.comment_list);
                        isNeedRefresh = false;
                    }
                }else{
                    isNeedRefresh = true;
                }

                setTimeout(function() {
                    $("audio").attr("preload",true);
                    $('.audio_src').each(function(index, el) {
                        weixinAudioObj.push($(el));
                    });

                    $('table').css({
                        width: "100%"
                    });
                    var fullWidth = $(".section-content").width();

                    $("iframe").each(function() {
                        var originSrc = $(this).attr("src").split("&",1)[0];
                        var newSrc = originSrc + "&width=" + fullWidth + "&height=280&auto=0";
                        $(this).attr("src", newSrc);
                        $(this).css("height", "280px");
                    });

                }, 500);


            } else if(data.code == 100010) {
                alert("对不起，该课程已经下架");
                window.location.href = "/wechat";
            }
        }
    })
}