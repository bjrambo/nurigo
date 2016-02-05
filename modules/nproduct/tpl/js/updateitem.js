
function delete_item(f) {
	return procFilter(f, delete_item);
}


function completeDeleteItem(ret_obj, response_tags, callback_func_args, fo_obj) {
	alert(ret_obj['message']);
	location.href = current_url.setQuery('act','dispNstore_digitalAdminItemList');
}


(function($) {
	jQuery(function($) {
		$('.category').change(function() {
			var node_id = $('option:selected', this).val();
			var depth = $(this).attr('depth');
			depth = parseInt(depth);
			depth++;
			jQuery('input[name=category_id]').val(node_id);
			load_categories(g_module_srl, node_id, '#category_depth'+depth);
		});
		$('a.modalAnchor.modifyDeliveryInfo').bind('before-open.mw', function(event){
			var item_srl = $(event.target).parent().attr('id');
			//var checked = $(event.target).closest('tr').find('input:radio:checked').val();

			exec_xml(
				'nproduct',
				'getNproductAdminInsertDeliveryInfo',
				{item_srl:item_srl},
				function(ret){
					var tpl = ret.tpl.replace(/<enter>/g, '\n');
					$('#extendForm').html(tpl);
				},
				['error','message','tpl']
			);

		});

/*
		$('a.modalAnchor.deleteItem').bind('before-open.mw', function(event){
			var item_srl = $(event.target).parent().attr('data-item-srl');

			exec_xml(
				'nproduct',
				'getNproductAdminDeleteItem',
				{item_srl:item_srl},
				function(ret){
					var tpl = ret.tpl.replace(/<enter>/g, '\n');
					$('#deleteForm').html(tpl);
				},
				['error','message','tpl']
			);

		});
*/

/*
		$('a.modalAnchor.adminManualOrder').bind('before-open.mw', function(event){
			var item_srl = $(event.target).attr('data-item-srl');

			exec_xml(
				'nproduct',
				'getNproductAdminInsertManualOrder',
				{item_srl:item_srl},
				function(ret){
					var tpl = ret.tpl.replace(/<enter>/g, '\n');
					$('#orderForm').html(tpl);
				},
				['error','message','tpl']
			);

		});
*/

		$('a.modalAnchor.modifyOptions').bind('before-open.mw', function(event){
			var item_srl = $(event.target).parent().attr('data-item-srl');
			//var checked = $(event.target).closest('tr').find('input:radio:checked').val();

			exec_xml(
				'nproduct',
				'getNproductAdminInsertOptions',
				{item_srl:item_srl},
				function(ret){
					var tpl = ret.tpl.replace(/<enter>/g, '\n');
					$('#optionsForm').html(tpl);
				},
				['error','message','tpl']
			);

		});

		$('.product_picker').ProductPicker({
			lang: {
				dialogTitle: xe.lang.product_picker
				, deleteButton: xe.lang.cmd_delete
				, appendButton: xe.lang.cmd_append
			}
		});
	});
}) (jQuery);
