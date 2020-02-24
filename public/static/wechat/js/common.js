;(function () {

    $.ajaxSetup({
        headers: {
            'api-version': 1
        },
        beforeSend: function () {
            $("#loadingToast").show();
        },
        complete: function () {
            $('#loadingToast').hide();
        }
    });
})();

//判空
function isNullOrEmpty(value) {
    return (typeof(value) == "undefined" || value == '' || value == null || value == 0);
}

//截取url参数
$.getUrlParam = function (name) {//获取url参数
    var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)");
    var r = window.location.search.substr(1).match(reg);
    if (r != null) return unescape(r[2]); return null;
}

//校验手机号
function checkTel(idName){
    var isMob=/^((\+?86)|(\(\+86\)))?(13[0123456789][0-9]{8}|15[0123456789][0-9]{8}|18[0123456789][0-9]{8}|17[0123456789][0-9]{8}|147[0-9]{8}|1349[0-9]{7})$/;
    var value=$("#"+idName).val();
    if(isMob.test(value)){
        return true;
    }
    else{
        return false;
    }
}