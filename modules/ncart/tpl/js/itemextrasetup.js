/*
function delete_extra(extra_srl) {
	if (!confirm('정말 삭제하시겠습니까?')) return;
	var params = new Array();
	params['extra_srl'] = extra_srl;
	exec_xml('nstore', 'procNstoreAdminDeleteItemExtra', params, function(ret_obj) { alert(ret_obj['message']); location.href = current_url; });
}*/

jQuery(function($) {
	$("#extraList").sortable({ handle:'.iconMoveTo', opacity: 0.6, cursor: 'move',
		update: function(event,ui) {
			var order = jQuery(this).sortable("serialize");
			var params = new Array();
			params['order'] = order;
			var response_tags = new Array('error','message');
			exec_xml('nstore', 'procNstoreAdminUpdateItemExtraOrder', params, function(ret_obj) { }, response_tags);
		}
	});

	$('a.modalAnchor.extendFormEdit').bind('before-open.mw', function(event){
		exec_xml(
			'nstore',
			'getNstoreAdminInsertItemExtra',
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
			'nstore',
			'getNstoreAdminInsertItemExtra',
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
		$("#extra_srl").val(extra_srl);

	});

	

});

