var app = new Vue({
	el: '#index',
	data: {
		courseIndex:[],
		is_data: false
	},
	methods: {
		courseDetail: function(course_id){
			window.location.href = '/wechat/course/courseIntroduction?course_id='+course_id;
		},
		sectionList: function(id) {
			window.location.href = '/wechat/course/sectionLists?course_id='+id;
		}
	}
});

var data_type = 0;
var isNeedRefresh = false;
var page_num = 1;

$(function() {
	getCourseIndex(data_type);

	$('li').on('click', function(event) {
		event.preventDefault();
		/* Act on the event */
		$(this).addClass('active').siblings('li').removeClass('active');
		data_type = $(this).attr("data-type");
		page_num = 1;
		isNeedRefresh = false;
		app.courseIndex = [];
		getCourseIndex(data_type);
		$(window).scrollTop(0);
	});

	$(window).scroll(function(){
		var scrollTop = $(this).scrollTop();
		var scrollHeight = $(document).height();
		var windowHeight = $(this).height();
		if(!isNeedRefresh &&
				(scrollHeight - scrollTop - windowHeight <= 25)){
			isNeedRefresh = true;
			page_num = page_num + 1;
			getCourseIndex(data_type);
		}
	});

	function getCourseIndex(data_type){
		$("#loadingToast").show();
		$.ajax({
			url:urlCourseIndex,
			type:"POST",
			async:false,
			data:{
				type: data_type,
				page_num: page_num
			},
			dataType:"JSON",
			success:function(data){
				if(data.code==200){
					if(!isNullOrEmpty(data.data.list)) {
						if(page_num == 1){
							app.courseIndex = data.data.list;
							app.is_data = false;
						}else{
							app.courseIndex = app.courseIndex.concat(data.data.list);
							app.is_data = false;
						}
					}else{
						if (page_num == 1) {
							isNeedRefresh = false;
							app.is_data = true;
						}else{
							isNeedRefresh = false;
							app.is_data = false;
						}
					}
				}
			}
		})

	}

})
