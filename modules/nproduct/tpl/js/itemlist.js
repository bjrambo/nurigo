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
	
		/*
		 // 순번으로 교체
		$("#itemlistorder").sortable({ handle:'.iconMoveTo', opacity: 0.6, cursor: 'move',
			update: function(event,tbody) {
				var order = jQuery(this).sortable("serialize");
				var params = new Array();
				params['order'] = order;
				var response_tags = new Array('error','message');
				exec_xml('nproduct', 'procNproductAdminUpdateItemListOrder', params, function(ret_obj) { }, response_tags);
			}
		});
		*/
	});
})(jQuery);

