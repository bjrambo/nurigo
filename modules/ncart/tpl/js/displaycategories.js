
jQuery(function($) {
	var editForm = $('#editForm');

	function resetEditForm() {
		editForm.find('input[name=category_srl]').val('');
		editForm.find('input[name=category_name]').val('');
		editForm.find('input[name=thumbnail_width]').val('150');
		editForm.find('input[name=thumbnail_height]').val('150');
		editForm.find('input[name=num_columns]').val('6');
		editForm.find('input[name=num_rows]').val('2');
	}

	$('a._edit').click(function() {
		var category_srl = $(this).parent().attr('id').replace(/record_/i,'');
		exec_xml(
			'nstore',
			'getNstoreAdminDisplayCategory',
			{category_srl:category_srl},
			function(ret){
				editForm.find('input[name=category_srl]').val(ret.data.category_srl);
				editForm.find('input[name=category_name]').val(ret.data.category_name);
				editForm.find('input[name=thumbnail_width]').val(ret.data.thumbnail_width);
				editForm.find('input[name=thumbnail_height]').val(ret.data.thumbnail_height);
				editForm.find('input[name=num_columns]').val(ret.data.num_columns);
				editForm.find('input[name=num_rows]').val(ret.data.num_rows);
			},
			['error','message','data']
		);
	});
	$('a._add').click(function() {
		resetEditForm();
	});
});
