var app = new Vue({
	el: '#course-comment',
	data: {
		sectionDetails: [],
		is_comment: false
	},
	methods: {
		comment: function() {
			var content = $('.comment-content').val();
			var point = $(".star .active").length;

			if (isNullOrEmpty(content)) {
				alert("请填写评价内容！");
				return false;
			}

			if(point == 0 && app.is_comment == false){
		    	alert("请您选择星级评价!");
		        return false;
		    }

		    $.ajax({
				url:urlComment,
				type:"POST",
				// async:false,
				data:{
					item_id: $.getUrlParam('item_id'),
					content: content,
					point: point
				},
				dataType:"JSON",
				success:function(data){
					if(data.code==200){				
						alert("评价成功!");
						window.location.href = '../course/sectionDetails?item_id=' + $.getUrlParam('item_id');
					}
				},
                error:function (data) {
                    alert('对不起，评论中暂不支持表情，请重新填写');
                }
			})
		},
		cancelComment: function() {
			window.location.href = '../course/sectionDetails?item_id=' + $.getUrlParam('item_id');
		}
	}
})

$(function() {
	getSectionDetails();

	$(".star i").each(function(index){
        $(this).on("click",function(){
            $(".star i").removeClass("active");
            for(var i = 0;i <= index;i++){
                $(".star i:eq("+i+")").addClass("active");
            }
        })
    })
})

function getSectionDetails(){
	$.ajax({
		url:urlSectionContent,
		type:"POST",
		async:false,
		data:{
			item_id: $.getUrlParam('item_id')
		},
		dataType:"JSON",
		success:function(data){
			console.log(data);
			if(data.code==200){
				
				app.sectionDetails = data.data.info;

				app.is_comment = data.data.is_comment_point;

			}
		}
	})
}
