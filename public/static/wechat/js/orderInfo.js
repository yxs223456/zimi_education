var webArr = null;
var app = new Vue({
	el: '#orderInfo',
	data: {
		info:[],
		list:[]
	},
	methods: {
		index: function() {
			window.location.href = "/wechat";
		},
		myCenter: function() {
			window.location.href = "/wechat/center/userCenter";
		},
		top: function() {
			$('body,html').animate({scrollTop:0},300);
		},
		courseDetail: function(course_id){
			window.location.href = '/wechat/course/courseIntroduction?course_id='+course_id;
		},
		cancelOrder: function(id) {
			$("#loadingToast").show();
			$.ajax({
				url:urlCancelOrder,
				type:"POST",
				async:false,
				data:{
					order_id: id
				},
				dataType:"JSON",
				success:function(data){
					$("#loadingToast").hide();
					if(data.code==200){
						window.location.href = "/wechat/center/myOrder";
					} else {
						alert(data.msg);

					}
				}

			})
		},
		wechatPay: function (courseId) {
			if(isNullOrEmpty(app.mobile)) {
				alert("请您先绑定手机号码！");
				location.href = "/wechat/center/bindPhone"
			} else {
				$.ajax({
					type: 'post',
					url: '/api/pay/orderPay',
					data:{
						course_id: courseId
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
})


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

$(function() {

	var id = $.getUrlParam("id");

	$.ajax({
		url:urlOrderInfo,
		type:"POST",
		async:false,
		data:{
			id: id,
		},
		dataType:"JSON",
		success:function(data){
			if(data.code==200){
				if (!isNullOrEmpty(data.data.info)) {
					app.mobile = data.data.info.mobile;
					app.info = data.data.info;
					app.list = data.data.list;
				}else{
					window.location.href = "/wechat/center/userCenter";
				}
			} else {
				window.location.href = "/wechat/center/userCenter";
			}
		}
	})

})