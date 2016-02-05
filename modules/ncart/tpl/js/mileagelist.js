
jQuery(function($) {
	$('a.modalAnchor.plusMileage').bind('before-open.mw', function(event){
		exec_xml(
			'nstore',
			'getNstoreAdminPlusMileage',
			{},
			function(ret){
				var tpl = ret.tpl.replace(/<enter>/g, '\n');
				$('#plusForm').html(tpl);
			},
			['error','message','tpl']
		);
	});
	$('a.modalAnchor.minusMileage').bind('before-open.mw', function(event){
		exec_xml(
			'nstore',
			'getNstoreAdminMinusMileage',
			{},
			function(ret){
				var tpl = ret.tpl.replace(/<enter>/g, '\n');
				$('#minusForm').html(tpl);
			},
			['error','message','tpl']
		);
	});

});
