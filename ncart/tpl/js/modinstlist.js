
jQuery(function($) {
	$('a.modalAnchor.deleteInstance').bind('before-open.mw', function(event){
		var module_srl = $(this).attr('data-module-srl');
		if (!module_srl) return;

		exec_xml(
			'nstore',
			'getNcartAdminDeleteModInst',
			{module_srl:module_srl},
			function(ret){
				var tpl = ret.tpl.replace(/<enter>/g, '\n');
				$('#deleteForm').html(tpl);
			},
			['error','message','tpl']
		);
	});
});
