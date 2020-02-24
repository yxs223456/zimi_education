var app = new Vue({
	el: '#info-modify',
	data: {
		centerDetails: []
	},
	methods: {
		bindPhone: function() {
			window.location.href = '../center/bindPhone';
		}
	}
})

$(function() {

	getCenterDetails();

	$("#self-shopPhotoOne").change(function(){
        var id = $(this).attr("id");
        $.ajaxFileUpload({
            url:urlUploadImageToOss,
            secureuri :false,
            fileElementId :id,//file控件id
            dataType:"JSON",
            success:function(data,status){
                data = JSON.parse(data);
                if(data.code == 200){
                    $.ajax({
                        url:urlChangeHeadImage,
                        type:"POST",
                        async:true,
                        dataType:"json",
                        data:{
                            headimgurl: data.data.url
                        },
                        success:function(data){
                            if(data.code==200){
                                location.reload();
                            }else{
                                alert(data.msg);
                            }
                        }
                    })
                }

            }
        })
    })
})

function getCenterDetails() {
	$.ajax({
        url:urlCenterDetails,
        type:"POST",
        async:true,
        dataType:"json",
        data:{},
        success:function(data){
            if(data.code==200){
                
            	app.centerDetails = data.data;

            }else{

            }
        }
    })
}