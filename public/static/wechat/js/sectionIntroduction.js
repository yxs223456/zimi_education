var app = new Vue({
	el:'#section-details',
	data:{
		sectionDetails: [],
		sectionComment: [],
		collectStatus: false,
        availableStatus: false,
        is_match_time: false,
        remaining_days: 0
	},
	methods:{
		teacherDetails: function(teacher_id){
			window.location.href = '../course/speechIntroduction?teacher_id=' + teacher_id;
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

		},
        purchaseVip: function() {
            window.location.href = '../center/purchaseVip?is_vip=' + false;
        },
		sectionDetailsPage: function(item_id) {
			window.location.href = '../course/sectionDetails?item_id=' + item_id;
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
		isCollect: function(){
			//取消收藏
			$.ajax({
				url:urlCancelCollect,
				type:"POST",
				async:false,
				data:{
					item_id: $.getUrlParam('item_id')
				},
				dataType:"JSON",
				success:function(data){
					if(data.code==200){
						//console.log("isCollect");
						
						app.collectStatus = false;
						//console.log(app.collectStatus);
					}
				}
			})
		},
		Collect: function() {
			//收藏
			$.ajax({
				url:urlCollect,
				type:"POST",
				async:false,
				data:{
					item_id: $.getUrlParam('item_id')
				},
				dataType:"JSON",
				success:function(data){
					console.log(data);
					if(data.code==200){
						//console.log("collect");
						
						app.collectStatus = true;
						//console.log(app.collectStatus);
					}
				}
			})
		}
	}
})

var isNeedRefresh = true;
var page_num = 1;

$(function() {
	var item_id = $.getUrlParam('item_id');
	getSectionDetails(item_id);

	$(window).scroll(function(){
		var scrollTop = $(this).scrollTop();
		var scrollHeight = $(document).height();
		var windowHeight = $(this).height();
		if(!isNeedRefresh &&
				(scrollHeight - scrollTop - windowHeight <= 25)){
			isNeedRefresh = false;
			page_num = page_num + 1;
			getSectionDetails(item_id);
		}
	});
})

function getSectionDetails(item_id){
	$.ajax({
		url:urlCourseItemDetails,
		type:"POST",
		async:false,
		data:{
			item_id: item_id,
			page_num: page_num
		},
		dataType:"JSON",
		success:function(data){
			if(data.code==200){
				app.sectionDetails = data.data.info;
				app.collectStatus = data.data.is_collect;
				app.availableStatus = data.data.is_available;
				app.is_match_time = data.data.is_match_time;
				app.remaining_days = data.data.remaining_days;

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
			} else if(data.code == 100010) {
				alert("对不起，该课程已经下架");
				window.location.href = "/wechat";
			}
		}
	})
}