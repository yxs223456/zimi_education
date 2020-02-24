var webArr = null;

var app = new Vue({
    el: '#purchase',
    data:{
        end_time: null,
        is_vip: false,
        courseDetails: [],
        mobile: null
    },
    methods:{
        wechatPay: function () {
            if(isNullOrEmpty(app.mobile)) {
                alert("请您先绑定手机号码！");
                location.href = "/wechat/center/bindPhone"
            } else {
                $.ajax({
                    type: 'post',
                    url: '/api/pay/orderPay',
                    data:{
                        course_id: $.getUrlParam("course_id")
                    },
                    dataType: 'json',
                    success: function (data) {
                        if(data.code != 200) {
                            alert(data.msg);
                        } else {
                            webArr = data.data;
                            callpay();
                        }
                    },
                    error:function (data) {
                        alert('服务器错误，请稍候重试！')
                    }
                })
            }
        }
    }
});

function onBridgeReady() {
    wx.chooseWXPay({
        timestamp: webArr.payConfig.timestamp,
        nonceStr: webArr.payConfig.nonceStr,
        package: webArr.payConfig.package,
        signType: webArr.payConfig.signType,
        paySign: webArr.payConfig.paySign,
        success: function (res) {
            alert("恭喜您支付成功！");
            window.location.href = "/wechat/center/myOrder";
        }
    });
}

function callpay() {
    if (typeof WeixinJSBridge == "undefined") {
        if (document.addEventListener) {
            alert("必须在微信中支付");
            document.addEventListener('WeixinJSBridgeReady', onBridgeReady, false);
        } else if (document.attachEvent) {
            alert("必须在微信中支付2");
            document.attachEvent('WeixinJSBridgeReady', onBridgeReady);
            document.attachEvent('onWeixinJSBridgeReady', onBridgeReady);
        }
    } else {
        onBridgeReady();
    }
}

function getCourse() {
    $.ajax({
        url:urlCourseDetails,
        type:"POST",
        async:false,
        data:{
            course_id: $.getUrlParam("course_id")
        },
        dataType:"JSON",
        success:function(data){
            if(data.code==200){
                //console.log(JSON.stringify(data))
                app.courseDetails = data.data.info;
                app.mobile = data.data.mobile;
            }
        }
    })
}

$(function() {
    getCourse();
});