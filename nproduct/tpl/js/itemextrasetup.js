jQuery(function($) {
	$("#extraList").sortable({ handle:'.iconMoveTo', opacity: 0.6, cursor: 'move',
		update: function(event,ui) {
			var order = jQuery(this).sortable("serialize");
			var params = new Array();
			params['order'] = order;
			var response_tags = new Array('error','message');
			exec_xml('nproduct', 'procNproductAdminUpdateItemExtraOrder', params, function(ret_obj) { }, response_tags);
		}
	});

	$('a.modalAnchor.extendFormEdit').bind('before-open.mw', function(event){
		exec_xml(
			'nproduct',
			'getNproductAdminInsertItemExtra',
			{},
			function(ret){
				var tpl = ret.tpl.replace(/<enter>/g, '\n');
				$('#extendForm').html(tpl);
			},
			['error','message','tpl']
		);

	});
	$('a.modalAnchor._edit').bind('before-open.mw', function(event){
		var extra_srl = $(this).attr('data-extra-srl');
		exec_xml(
			'nproduct',
			'getNproductAdminInsertItemExtra',
			{extra_srl:extra_srl},
			function(ret){
				var tpl = ret.tpl.replace(/<enter>/g, '\n');
				$('#extendForm').html(tpl);
			},
			['error','message','tpl']
		);

	});
	$('a.modalAnchor.extendFormDelete').bind('before-open.mw', function(event){
		
		var extra_srl = $(this).attr('data-extra-srl');
		$('#item_to_delete').text($(this).prev('span').text());
		$("#extra_srl").val(extra_srl);

	});

});
