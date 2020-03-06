var app = new Vue({
	el:'#teacher-info',
	data:{
		teacherInfo: []
	},
	methods:{

	}
})

$(function() {
	getTeacherInfo();
})

function getTeacherInfo(){
	$.ajax({
		url:urlTeacherInfo,
		type:"POST",
		async:false,
		data:{
			teacher_id: $.getUrlParam("teacher_id")
		},
		dataType:"JSON",
		success:function(data){
			if(data.code==200){
				app.teacherInfo = data.data.info;
			}
		}
	})
}