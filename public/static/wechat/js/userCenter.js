var app = new Vue({
	el: '#user-center',
	data: {
		userCenter: [],
		attendCount: 0,
		purchaseCount: 0
	},
	methods: {
		purchaseVip: function(is_vip){
			window.location.href = '../center/purchaseVip?is_vip=' + is_vip;
		},
		bindPhone: function(is_set_mobile){
			if (is_set_mobile == true) {
				window.location.href = '../center/infoModify';
			}else{
				window.location.href = '../center/bindPhone';
			}
		},
		userDetails: function() {
			window.location.href = '../center/infoModify';
		},
		courseRecord: function() {
			window.location.href = '../center/courseRecord';
		},
		purchasedCourse: function() {
			window.location.href = '../center/purchasedCourse';
		},
		collectCourse: function() {
			window.location.href = '../center/collectCourse';
		},
		myOrder: function() {
			window.location.href = '../center/myOrder';
		},
		index: function() {
			window.location.href = '/wechat';
		},
		infoModify: function() {
			window.location.href = '../center/infoModify';
		},
		share: function() {
			$('.share-zone').show();
		}
	}
})

$(function() {
	getUserCenter();

	$('.share-zone').on('click', function(event) {
		event.preventDefault();
		/* Act on the event */
		$(this).hide();
	});
})

function getUserCenter(){
	$.ajax({
		url:urlUserCenter,
		type:"POST",
		async:false,
		data:{},
		dataType:"JSON",
		success:function(data){
			if(data.code==200){
				app.userCenter = data.data.info;
				app.attendCount = data.data.attend_count;
				app.purchaseCount = data.data.purchase_count;
				localStorage.setItem("end_time", data.data.info.end_time);
			}
		}
	})
}