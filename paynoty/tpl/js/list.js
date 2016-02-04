jQuery(function($) {
	$('a.modalAnchor.deleteConfig').bind('before-open.mw', function(event){
		var config_srl = $(this).attr('data-config-srl');
		if (!config_srl) return;

		exec_xml(
			'paynoty',
			'getPaynotyAdminDelete',
			{config_srl:config_srl},
			function(ret){
				var tpl = ret.tpl.replace(/<enter>/g, '\n');
				$('#deleteForm').html(tpl);
			},
			['error','message','tpl']
		);

	});
});
