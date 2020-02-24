var app = new Vue({
	el:'#section-list',
	data:{
		sectionList: [],
		keyword: ""
	},
	methods:{
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

var isNeedRefresh = false;
var page_num = 1;
var data_order = 'create_time';

$(function() {
	var course_id = $.getUrlParam('course_id');
	getSectionList(course_id,data_order);

	$(window).scroll(function(){
		var scrollTop = $(this).scrollTop();
		var scrollHeight = $(document).height();
		var windowHeight = $(this).height();
		if(!isNeedRefresh &&
				(scrollHeight - scrollTop - windowHeight <= 25)){
			isNeedRefresh = true;
			page_num = page_num + 1;
			getSectionList(course_id,data_order);
			$(window).scrollTop(0);
		}
	});

	$("img.icon-search").on("click", function(event) {

		event.preventDefault();

		var keyword = $("input[name=keyword]").val();
		app.keyword = keyword;

		getSectionList(course_id,data_order);

	});

	$('.sellect-label').on('click', function(event) {
		event.preventDefault();
		/* Act on the event */
		page_num = 1;
		isNeedRefresh = false;
		data_order = $(this).attr("data-order");
		$(".sellect-label").each(function() {
			if($(this).hasClass("active")) {
				$(this).removeClass("active");
			}
		});

		$(this).addClass("active");

		var keyword = $("input[name=keyword]").val();
		app.keyword = keyword;

		getSectionList(course_id,data_order);
	});
});



function getSectionList(course_id,data_order){
	$.ajax({
		url:urlCourseItemList,
		type:"POST",
		data:{
			course_id: course_id,
			order: data_order,
			keyword: app.keyword,
			page_num: page_num
		},
		dataType:"JSON",
		success:function(data){
			if(data.code==200){
				if(!isNullOrEmpty(data.data.list)) {
					if(page_num == 1){
						app.sectionList = data.data.list;
					}else{
						app.sectionList = app.sectionList.concat(data.data.list);
					}
				}else{
					isNeedRefresh = true;
				}
			}
		}
	})
}