function makeList() {
    var list = new Array();
    jQuery('input[name=cart]:checked').each(function(idx, elem) {
        list[list.length] = jQuery(elem).val();
    });
    return list;
}

function completeInsertItem(ret_obj, response_tags, callback_func_args, fo_obj) {
	alert(ret_obj['message']);
    fo_obj.act.value = 'procElearningInsertItemFile';
	fo_obj.item_srl.value = ret_obj['item_srl'];
    fo_obj.submit();
}

function completeUpdateItem(ret_obj, response_tags, callback_func_args, fo_obj) {
	alert(ret_obj['message']);
    fo_obj.act.value = 'procElearningUpdateItemFile';
    fo_obj.submit();
}

function completeInsertStore(ret_obj) {
	alert(ret_obj['message']);
}

function appendToDisplayStand(module_srl, item_srl) {
	var params = new Array();
	params['item_srl'] = item_srl;
	params['module_srl'] = module_srl;
	params['category_srl'] = selected_category_srl;
	exec_xml('elearning', 'procElearningAdminInsertDisplayItem', params, function(ret_obj) { alert(ret_obj['message']); location.href=current_url;});
}

function append_category(module_srl) {
	var params = new Array();
	params['module_srl'] = module_srl;
	params['category_name'] = jQuery('#category_name').val();
	exec_xml('elearning', 'procElearningAdminInsertDisplayCategory', params, function(ret_obj) { alert(ret_obj['message']); location.href = current_url; });
}

function modify_category(category_srl) {
	$rec = jQuery('#record_'+category_srl);
	var category_name = jQuery('.category_name', $rec).text();
	var thumbnail_width = $rec.find('.thumbnail_width').text();
	var thumbnail_height = $rec.find('.thumbnail_height').text();
	var num_columns = $rec.find('.num_columns').text();
	var num_rows = $rec.find('.num_rows').text();

	$rec.append('<form onsubmit="return procFilter(this,update_display_category);"><input type="hidden" name="category_srl" value="'+category_srl+'" /><input type="text" name="category_name" value="'+category_name+'" /><input type="text" name="thumbnail_width" value="'+thumbnail_width+'" /><input type="text" name="thumbnail_height" value="'+thumbnail_height+'" /><input type="text" name="num_columns" value="'+num_columns+'" /><input type="text" name="num_rows" value="'+num_rows+'" /><input type="submit" value="Update" /></form>');
}

function delete_category(category_srl) {
	if (!confirm('정말 삭제하시겠습니까?')) return;
	var params = new Array();
	params['category_srl'] = category_srl;
	exec_xml('elearning', 'procElearningAdminDeleteDisplayCategory', params, function(ret_obj) { alert(ret_obj['message']); location.href = current_url; });
}


function load_categories(module_srl, node_id, target) {
	if (typeof(node_id)=='undefined') node_id = 'f.';
	if (typeof(target)=='undefined') target = '#category_depth1';

	$target = jQuery(target);
	var $first_option = jQuery(target).children().eq(0);
	$target.empty();
	$target.append($first_option);

	jQuery.ajax({
		type: 'POST',
		dataType: "json",
		contentType: "application/json; charset=utf-8",
		async : false,
		url: "./",
		data : { 
			module : "elearning"
			, act : "getElearningCategoryList"
			, node_id : node_id
			, module_srl : module_srl
		}, 
		success : function (r) {
			if (r.error == -1) {
				alert(r.message);
			} else {
				for (i = 0; i < r.data.length; i++) {
					jQuery('<option value="' + r.data[i].attr.node_id + '">' + r.data[i].attr.node_name + '</option>').appendTo(target);
				}
			}
		}
	});
}

