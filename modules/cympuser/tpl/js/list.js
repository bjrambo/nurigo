jQuery(function($) {
	$('a.modalAnchor.deleteModule').bind('before-open.mw', function(event){
		var module_srl = $(this).attr('data-module-srl');
		console.log(module_srl);
		if (!module_srl) return;

		exec_xml(
			'sender_id',
			'getSender_idAdminDelete',
			{module_srl:module_srl},
			function(ret){
				var tpl = ret.tpl.replace(/<enter>/g, '\n');
				$('#deleteForm').html(tpl);
			},
			['error','message','tpl']
		);

	});
});
