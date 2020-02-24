$(function() {
	$('.cancel-btn').on('click', function(event) {
		event.preventDefault();
		/* Act on the event */
		window.location.href = '../center/bindPhone';
	});

	$('.country p').on('click', function(event) {
		event.preventDefault();
		/* Act on the event */
		var text = $(this).text();
		var number = $(this).find("span").text();
		$.ajax({
	        url:urlSaveAreaString,
	        type:"POST",
	        async:true,
	        dataType:"json",
	        data:{
				area_number: number,
	        	area_string: text
	        },
	        success:function(data){
	            if(data.code==200){
	                window.location.href = '../center/bindPhone';
	            }
	        }
	    })
	});
})