var app = new Vue({
	el: '#section-list-body',
	data: {
		attendRecord: []
	},
	methods: {
		sectionDetails: function(item_id){
			window.location.href = '/wechat/course/sectionIntroduction?item_id=' + item_id;
		},
		index: function() {
			window.location.href = "/wechat";
		},
		myCenter: function() {
			window.location.href = "/wechat/center/userCenter";
		},
		top: function() {
			$('body,html').animate({scrollTop:0},300);
		}
	}
})

$(function() {
	getAttendRecord();
})

function getAttendRecord() {
	$.ajax({
		url:urlPurchasedCourse,
		type:"POST",
		async:false,
		data:{},
		dataType:"JSON",
		success:function(data){
			if(data.code==200){
				app.attendRecord = data.data.list;
			}
		}
	})
}