

function delete_display_item(category_srl, item_srl) {
	if (!confirm('정말 삭제하시겠습니까?')) return;
	var params = new Array();
	params['category_srl'] = category_srl;
	params['item_srl'] = item_srl;
	exec_xml('nstore', 'procNstoreAdminDeleteDisplayItem', params, completeInsertDisplayItem );
}


(function($) {
	jQuery(function($) {
		load_categories(module_srl);
		$('.category').change(function() {
			var node_id = $('option:selected', this).val();
			var depth = $(this).attr('depth');
			depth = parseInt(depth);
			depth++;
			jQuery('input[name=category_id]').val(node_id);
			load_categories(module_srl, node_id, '#category_depth'+depth);
		});

		$("#itemlistorder").sortable({ handle:'.iconMoveTo', opacity: 0.6, cursor: 'move',
			update: function(event,tbody) {
				var order = jQuery(this).sortable("serialize");
				var params = new Array();
				params['order'] = order;
				var response_tags = new Array('error','message');
				exec_xml('nstore', 'procNstoreAdminUpdateItemListOrder', params, function(ret_obj) { }, response_tags);
			}
		});
	});
})(jQuery);

