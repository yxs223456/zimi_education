var app = new Vue({
	el: '#bind-phone',
	data: {

	},
	methods: {
		countryArea: function() {
			window.location.href = '../center/countryList';
		}
	}
})

var wait = 60;

$(function() {

	getInfo();

	$('#mobile').blur(function(event) {
		/* Act on the event */
		if (!checkTel('mobile')) {
			alert("请输入正确的手机号");
			return false;
		}
	});

	$('.code-btn').on('click', function(event) {
		event.preventDefault();
		/* Act on the event */
		var mobile = $('#mobile').val();
		if (isNullOrEmpty(mobile)) {
			alert("请输入手机号");
			return false;
		}
		sendCode();
	});

	$('.confirm-btn').on('click', function(event) {
		event.preventDefault();
		var mobile = $("#mobile").val();
		var areaNumber = $("input[name=areaNumber]").val();
		/* Act on the event */
		$.ajax({
	        url:urlChangeMobile,
	        type:"POST",
	        async:true,
	        dataType:"json",
	        data:{
	        	mobile: areaNumber+mobile,
	        	code: $("#code").val()
	        },
	        success:function(data){
	            if(data.code==200){
	                //time(thisBtn);
	                alert("操作成功");
	                window.location.href = '../center/userCenter';
	            }else{

	            }
	        },
	        error:function(data){
	            //alert(JSON.stringify(data))
	            thisBtn.removeAttr("disabled").html("获取验证码");
	            wait = 0;
	            alert("网络错误，请稍后再试");
	        }
	    })
	});

	$('.cancel-btn').on('click', function(event) {
		event.preventDefault();
		/* Act on the event */
		window.location.href = '../center/userCenter';
	});

})

function sendCode(){
    if($(".code-btn").attr("disabled")=="disabled"){
        return false;
    }
    var thisBtn = $(".code-btn");
    var mobile = $("#mobile").val();
	var areaNumber = $("input[name=areaNumber]").val();

    time(thisBtn);
    $.ajax({
        url:urlSendMobileCode,
        type:"POST",
        async:true,
        dataType:"json",
        data:{mobile:areaNumber+mobile},
        success:function(data){
            if(data.code==200){
                //time(thisBtn);
            }else{
            }
        },
        error:function(data){
            //alert(JSON.stringify(data))
            thisBtn.removeAttr("disabled").html("获取验证码");
            wait = 0;
            alert("网络错误，请稍后再试");
        }
    })
}

function time(o) {
    if (wait == 0) {
        o.removeAttr("disabled").html("获取验证码");
        wait = 60;
    } else {
        o.attr('disabled',"true").html("(" + wait + ")s");
        wait--;
        setTimeout(function(){
            time(o)
        },1000)
    }
}

function getInfo() {
	$.ajax({
        url:urlChangeMobileIndex,
        type:"POST",
        async:true,
        dataType:"json",
        data:{},
        success:function(data){
            if(data.code==200){
                if (isNullOrEmpty(data.data.area_string)) {
                	$('.choose-country').text("中国大陆86");
                }else{
					$("input[name=areaNumber]").val(data.data.area_number);
                	$('.choose-country').text(data.data.area_string);
                }
            }
        }
    })
}