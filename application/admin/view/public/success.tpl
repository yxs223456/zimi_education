<!doctype html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>跳转提示</title>
	<script src="/static/admin/js/jquery.min.js?v=2.1.4"></script>
	<script src="/static/admin/js/layer/layer.js"></script>   
</head>
<body>
   
    <script> layer.msg('{$msg}',{icon:1,shade:0.1});</script>
	
    <script type="text/javascript">    
        	setTimeout(function(){
				location.href = '{$url}';
			},1000);
    </script>
</body>
</html>
