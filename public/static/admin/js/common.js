var common = {

    //判空
    isNullOrEmpty: function(value) {
        return (typeof(value) == "undefined" || value == '' || value == null || value == 0);
    },

    isNullOrEmptyForDictionary: function(value) {
        return (typeof(value) == "undefined" || value == '' || value == null);
    },

    //询问弹出层
    confirm: function(message,url) {
        layer.confirm(message, {
            btn: ['确定','取消'] //按钮
        }, function(){
            layer.msg("请稍候", {time:2000}, function(index){
                layer.close(index);
                window.location.href=url;
            });
        }, function(index){
            layer.close(index);
        });
    },

	//成功弹出层
	success: function(message,url) {
		layer.msg(message, {icon: 6,time:2000}, function(index){
            layer.close(index);
            window.location.href=url;
        });
	},

	// 错误弹出层
	error: function(message) {
        layer.msg(message, {icon: 5,time:2000}, function(index){
            layer.close(index);
        });       
    },

	// 确认弹出层
    delete : function(id,url,redirect) {
        layer.confirm('确认删除此条记录吗?', {icon: 3, title:'提示'}, function(index){
	        $.getJSON(url, {'id' : id}, function(res){
	            if(res.code == 1){
	                layer.msg(res.msg,{icon:1,time:1500,shade: 0.1}, function(index) {
                        layer.close(index);
                        location.href = redirect;
                    });
	            }else{
	                layer.msg(res.msg,{icon:0,time:1500,shade: 0.1});
	            }
	        });
	        layer.close(index);
	    })
    },

    //状态
    status : function(id,url){
	    $.post(url,{id:id},function(data){	         
	        if(data.code==1){
	            var a='<span class="label label-danger">禁用</span>'
	            $('#zt'+id).html(a);
	            layer.msg(data.msg,{icon:2,time:1500,shade: 0.1,});
	            return false;
	        }else{
	            var b='<span class="label label-info">开启</span>'
	            $('#zt'+id).html(b);
	            layer.msg(data.msg,{icon:1,time:1500,shade: 0.1,});
	            return false;
	        }         	        
	    });
	    return false;
	},

    //开启
    activate : function(id,url,redirect){
        $.post(url,{id:id},function(res){
            if(res.code == 1){
                layer.msg(res.msg,{icon:1,time:1500,shade: 0.1}, function(index) {
                    layer.close(index);
                    window.location.href = redirect;
                });
            }else{
                layer.msg(res.msg,{icon:2,time:1500,shade: 0.1});
            }
        });
        return false;
    },

    //关闭
    deactivate : function(id,url,redirect){
        $.post(url,{id:id},function(res){
            if(res.code == 1){
                layer.msg(res.msg,{icon:1,time:1500,shade: 0.1}, function(index) {
                    layer.close(index);
                    window.location.href = redirect;
                });
            }else{
                layer.msg(res.msg,{icon:2,time:1500,shade: 0.1});
            }
        });
        return false;
    }


};