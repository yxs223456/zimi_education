var webArr = null;

var app = new Vue({
	el: '#vip',
	data:{
		end_time: null,
		is_vip: false,
        vipList: [],
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
                    url: '/api/pay/vippay',
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
            window.location.href = "/wechat/center/usercenter";
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

function getVip() {
    $.ajax({
        url:urlVip,
        type:"POST",
        async:false,
        data:{
        },
        dataType:"JSON",
        success:function(data){
            if(data.code==200){
                app.vipList = data.data;
                app.mobile = data.data.mobile;
            }

            setTimeout(function() {
                $('table').css({
                    width: "100%"
                });
                $('img').css({
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

        }
    })
}

$(function() {

    getVip();

})

