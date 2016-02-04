function makeList() {
    var list = new Array();
    jQuery('input[name=cart]:checked').each(function(idx, elem) {
        list[list.length] = jQuery(elem).val();
    });
    return list;
}

function completeInsertItem(ret_obj, response_tags, callback_func_args, fo_obj) {
	alert(ret_obj['message']);
    fo_obj.act.value = 'procNstoreInsertItemFile';
	fo_obj.item_srl.value = ret_obj['item_srl'];
    fo_obj.submit();
}

function completeUpdateItem(ret_obj, response_tags, callback_func_args, fo_obj) {
	alert(ret_obj['message']);
    fo_obj.act.value = 'procNstoreUpdateItemFile';
    fo_obj.submit();
}

function completeInsertNstore(ret_obj) {
	alert(ret_obj['message']);
}

function completeGetFrontDisplayItems(ret_obj) {
	$list_tabs = jQuery('#tabs-'+selected_category_srl);
	
	if($list_tabs.length == 0)
	{
		$list_tabs = jQuery('#tabs-0');
	}
	
	$list = jQuery('> ul',$list_tabs).empty();

	if (ret_obj['data']) 
	{
		var data = ret_obj['data']['item'];

		if (!jQuery.isArray(data)) {
			data = new Array(data);
		}
		for (var i = 0; i < data.length; i++) {
			$list.append('<li id="record_'+data[i].item_srl+'"><span class="iconMoveTo"></span><span>'+data[i].item_name+'</span><a href="#" class="delete" onclick="delete_display_item('+selected_category_srl+','+data[i].item_srl+'); return false;">삭제</a></li>');
		}
	}
}

function completeInsertDisplayItem(ret_obj) {	
	var params = new Array();	
	var responses = ['error','message','data','category_srl'];

	if(ret_obj['error'] == -1) 
	{
		alert(ret_obj['message']); 
		return;
	}
	
	params['category_srl'] = selected_category_srl;

	exec_xml('nstore', 'getNstoreDisplayItems', params, completeGetFrontDisplayItems, responses);
}

function appendToDisplayStand(module_srl, item_srl) {
	
	var cart = document.getElementsByName("cart");
	var cart_array = new Array();
	var c = 0;

	for(var i = 0; i < cart.length; i++)
	{
		if(cart[i].checked)
		{
			if(cart_array[c])
			{
				c+=1;
				cart_array[c] = cart[i].value;
			}
			else
			{
				cart_array[c] = cart[i].value;
			}
		}
	} 
	
	var params = new Array();

	if(item_srl)
	{
		params['item_srl'] = item_srl;
	}
	else
	{
		params['item_srl'] = cart_array;
	}

	params['module_srl'] = module_srl;
	params['category_srl'] = selected_category_srl;
//location.href=current_url;
	exec_xml('nstore', 'procNstoreAdminInsertDisplayItem', params, completeInsertDisplayItem);
}

function append_category(module_srl) {
	var params = new Array();
	params['module_srl'] = module_srl;
	params['category_name'] = jQuery('#category_name').val();
	exec_xml('nstore', 'procNstoreAdminInsertDisplayCategory', params, function(ret_obj) { alert(ret_obj['message']); location.href = current_url; });
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
	exec_xml('nstore', 'procNstoreAdminDeleteDisplayCategory', params, function(ret_obj) { alert(ret_obj['message']); location.href = current_url; });
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
			module : "nstore"
			, act : "getNstoreCategoryList"
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

