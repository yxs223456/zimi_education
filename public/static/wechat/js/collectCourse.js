var app = new Vue({
	el: '#collect-course',
	data: {
		collectCourse: []
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
	getCollectCourse();
})

function getCollectCourse() {
	$.ajax({
		url:urlCollectCourse,
		type:"POST",
		async:false,
		data:{},
		dataType:"JSON",
		success:function(data){
			if(data.code==200){
				app.collectCourse = data.data.list;
			}
		}
	})
}