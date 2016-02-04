jQuery(function($) {
	$(".x_modal .user_id").keyup(function () {
		var params = new Array();
		var responses = ['message', 'error', 'data', 'alert_message'];

		params['user_id'] = $(this).val();
		exec_xml('nmileage', 'getNmileageAdminCheckUserId', params, function(ret_obj){
			if(ret_obj['data']) {
				$('.user_id_help').html('Email : ' + ret_obj['data']['email_address'] + ', NickName :  ' + ret_obj['data']['nick_name']);
			} else {
				$('.user_id_help').html(ret_obj['alert_message']);
			}
		}, responses);
	});
});
