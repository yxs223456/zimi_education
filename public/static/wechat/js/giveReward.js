$(function() {

    function onBridgeReady() {
        wx.chooseWXPay({
            timestamp: webArr.payConfig.timestamp,
            nonceStr: webArr.payConfig.nonceStr,
            package: webArr.payConfig.package,
            signType: webArr.payConfig.signType,
            paySign: webArr.payConfig.paySign,
            success: function (res) {
                alert("恭喜您支付成功！");
                window.location.href = "/wechat/course/sectionDetails?item_id="+$.getUrlParam("item_id");
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

    function payDonation(item_id, money) {
        $.ajax({
            type: 'post',
            url: '/api/pay/donationPay',
            data:{
                item_id: $.getUrlParam("item_id"),
                amount: money
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
        });
	}

	$('.other-money').on('click', function(event) {
		event.preventDefault();
		/* Act on the event */
		$('.bg-black').show();
		$('.delete-alert').show();
	});

	$('.bg-black').on('click', function(event) {
		event.preventDefault();
		/* Act on the event */
		$('.bg-black').hide();
		$('.delete-alert').hide();
		$('.expect-money input').val("");
	});

	$('.money-account').on('click', function(event) {
		event.preventDefault();
		/* Act on the event */
		var money = $(this).children('.money-number').attr("data-money");

        payDonation($.getUrlParam("item_id"),money);
	});

	$('.reward-btn').on('click', function(event) {
		event.preventDefault();
		/* Act on the event */
		var money = $('.expect-money input').val();
		if (isNullOrEmpty(money)) {
			alert("请输入金额");
			return false;
		}
		if (money > 256 || money < 1) {
			alert("请重新输入金额");
			$('.expect-money input').val("");
			return false;
		}
        payDonation($.getUrlParam("item_id"),money);
	});
});