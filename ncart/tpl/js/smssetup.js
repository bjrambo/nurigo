jQuery(function($) {
	$('a.modalAnchor.configSms').bind('before-open.mw', function(event){
		var sms_code = $(this).attr('data-sms-code');

		exec_xml(
			'nstore',
			'getNstoreAdminSmsSetup',
			{sms_code:sms_code},
			function(ret){
				var tpl = ret.tpl.replace(/<enter>/g, '\n');
				$('#setupForm').html(tpl);
			},
			['error','message','tpl']
		);

	});
});
