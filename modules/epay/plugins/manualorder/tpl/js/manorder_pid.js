jQuery(document).ready(function ($){
	var params = new Array();
	var responses = ['manorder_pid_message', 'data'];

	$('#manorder_pid_message').html('<span> 아이디를 입력해주세요. </span>');
	$("#manorder_pid").keyup(function ()
	{
		params['manorder_pid'] = $("#manorder_pid").val();
		exec_xml('epay', 'getEpayCheckUserId', params, function(obj){
			if(obj['manorder_pid_message']) $('#manorder_pid_message').html('<span>' + obj['manorder_pid_message'] + '</span>');
			else $('#manorder_pid_message').html('<span> 아이디를 입력해주세요. </span>');

			if(obj['data'])
			{
				$('#manorder_pid_info').html(' 이메일 : <span id="manorder_email"> ' + obj['data']['email_address'] + '</span> <br /> 닉네임 :  <span id="manorder_nick"> ' + obj['data']['nick_name'] + '</span>');
			}
			else 
			{ 
				$('#manorder_pid_info').html('');
			}
		}, responses);
	});
});
