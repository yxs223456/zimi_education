var app = new Vue({
	el: '#course-details',
	data: {
		courseDetails:[]
	},
	methods: {
		sectionList: function(course_id){
			window.location.href = '../course/sectionLists?course_id=' + course_id;
		}
	}
})

$(function() {
	var course_id = $.getUrlParam('course_id');
	getCourseDetails(course_id);
});

function getCourseDetails(course_id){
	$.ajax({
		url:urlCourseDetails,
		type:"POST",
		async:false,
		data:{
			course_id: course_id
		},
		dataType:"JSON",
		success:function(data){
			if(data.code==200){
				//console.log(JSON.stringify(data))
				app.courseDetails = data.data.info;
				setTimeout(function() {
					$('table').css({
						width: "100%"
					});

					var fullWidth = $(".course-description").width();

					$("iframe").each(function() {
						var originSrc = $(this).attr("src").split("&",1)[0];
						var newSrc = originSrc + "&width=" + fullWidth + "&height=280&auto=0";
						$(this).attr("src", newSrc);
						$(this).css("height", "280px");
					});

				}, 500);
			}
		}
	})
}