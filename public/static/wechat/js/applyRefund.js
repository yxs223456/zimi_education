var app = new Vue({
	el: '#apply-refund',
	data: {
		course_title: ''
	},
	methods: {
		commitRefund: function() {
			$.ajax({
				url:urlRefundOrder,
				type:"POST",
				async:false,
				data:{
					order_id: $.getUrlParam("order_id")
				},
				dataType:"JSON",
				success:function(data){
					if(data.code==200){
						$('.bg-black').show();
						$('.success-alert').show();
					}
				}
			})
		}
	}
})

$(function() {

	app.course_title = localStorage.getItem("title");

	$(".icon-check").on('click', function(event) {
		event.preventDefault();
		/* Act on the event */
		$(".icon-check").removeClass('active');
		$(this).addClass('active');
		if ($(this).attr("data-reason") == 3) {
			$(".reason-area").show();
		}else{
			$(".reason-area").hide();
		}
	});

	$('.bg-black,.icon-close').on('click', function(event) {
		event.preventDefault();
		/* Act on the event */
		$('.bg-black').hide();
		$('.success-alert').hide();
		window.location.href = '../center/myOrder';
	});

})