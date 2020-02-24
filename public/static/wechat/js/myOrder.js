var app = new Vue({
	el: '#my-order',
	data: {
		myOrderList: [],
		is_data: false
	},
	methods: {
		applyRefund: function(order_id,title){
			localStorage.setItem("title", title);
			window.location.href = '/wechat/center/applyRefund?order_id=' + order_id;
		},
		courseIntroduction: function(detail_url) {
			window.location.href = detail_url;
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
		orderInfo: function(id) {
			window.location.href = "/wechat/center/orderInfo?id=" + id;
		},
		cancelOrder: function(id) {
			$.ajax({
				url:urlCancelOrder,
				type:"POST",
				async:false,
				data:{
					order_id: id
				},
				beforeSend: function () {
					$("#loadingToast").show();
				},
				complete: function () {
					$('#loadingToast').hide();
				},
				dataType:"JSON",
				success:function(data){
					if(data.code==200){
						alert("取消成功");
						getMyOrder(data_status);
					}
				}
			})
		},
		deleteOrder: function(id) {
			$.ajax({
				url:urlDeleteOrder,
				type:"POST",
				async:false,
				data:{
					order_id: id
				},
				dataType:"JSON",
				success:function(data){
					if(data.code==200){
						$('.bg-black').hide();
						$('.delete-alert').hide();
						$('#toastSuccess').show();
						setTimeout(function(){
							getMyOrder(data_status);
							$('#toastSuccess').hide();
						},1000);
					}
				}
			})
		}
	}
})

var data_status = 0;
var page_num = 1;

$(function() {

	getMyOrder(data_status);

	$("li").on('click', function(event) {
		event.preventDefault();
		/* Act on the event */
		$(this).addClass('active').siblings('li').removeClass('active');
		data_status = $(this).attr("data-status");
		getMyOrder(data_status);
	});

	$('.bg-black,.cancel-btn,.icon-close').on('click', function(event) {
		event.preventDefault();
		/* Act on the event */
		$('.bg-black').hide();
		$('.delete-alert').hide();
	});


})

function getMyOrder(data_status) {
	$.ajax({
		url:urlMyOrder,
		type:"POST",
		async:false,
		data:{
			status: data_status,
			page_num: page_num
		},
		dataType:"JSON",
		success:function(data){
			if(data.code==200){
				if (!isNullOrEmpty(data.data.list)) {
					app.myOrderList = data.data.list;
					app.is_data = false;
				}else{
					app.is_data = true;
				}
			}
		}
	})
}

function deleteOrder(tag){
	$('.bg-black').show();
	$('.delete-alert').show();
	var order_id = $(tag).attr("data-orderid");
	$('.comment-btn').on('click', function(event) {
		event.preventDefault();
		/* Act on the event */
		$.ajax({
			url:urlDeleteOrder,
			type:"POST",
			async:false,
			data:{
				order_id: order_id
			},
			dataType:"JSON",
			success:function(data){
				if(data.code==200){
					$('.bg-black').hide();
					$('.delete-alert').hide();
					$('#toastSuccess').show();
					setTimeout(function(){
						getMyOrder(data_status);
						$('#toastSuccess').hide();
					},1000);
				}
			}
		})
	});
}