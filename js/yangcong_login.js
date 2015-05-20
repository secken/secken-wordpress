jQuery(document).ready(function () {
    yangcong_GetResult();
});
function yangcong_GetResult() {
    jQuery.post(yangcong_login_url, {'uuid': yangcong_uuid, 'redirect_to': jQuery("input[name='redirect_to']").val()},
    function (data) {
        if (data.status === 1) {
            jQuery('#code_message').html(data.message);
            if (data.url !== '') {
                url = data.url;
                setTimeout("self.location=url", data.time * 1000);
            } else {
                location.reload();
            }
        }
        else
        if (data.status === 0) {
            setTimeout('yangcong_GetResult()', 3000);
            jQuery('#code_message').html(data.message);
        }



    }, "json").error(function (data) {
        jQuery('#code_message').html('服务器出错')
    });
}
