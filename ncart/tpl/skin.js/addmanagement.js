function completeGetAddressInfo(ret_obj) {

	clear_form_elements(document.getElementById('newAddrForm'));
	//jQuery('#newAddrForm').get(0).reset();
	jQuery('#addressList').toggle();
	jQuery('#newAddress').toggle();

	var data = ret_obj['data'];
	var addrinfo = data.address;
	var data_default = ret_obj['data']['default']

	jQuery('input[name=address_srl]').val(data.address_srl);
	if(data_default == "Y") jQuery('input[name=default]').attr('checked', 'checked');

	for (var i in fieldset)
	{
		var obj = fieldset[i];
		if (!addrinfo[obj.column_name]) continue;
		switch (obj.column_type)
		{
			case 'kr_zip':
				jQuery('input[name="'+obj.column_name+'[]"]').each(function(index) {
					jQuery(this).val(addrinfo[obj.column_name].item[index])
				});
				var full_address = "";
				for(var i = 0; i < addrinfo[obj.column_name].item.length; i++) {
					full_address = full_address + addrinfo[obj.column_name].item[i];
				}
				jQuery('input[name="'+obj.column_name+'[]"]').next().find('.current_address').val(full_address);

				break;
							case 'tel':
									jQuery('input[name="'+obj.column_name+'[]"]').each(function(index) { jQuery(this).val(addrinfo[obj.column_name].item[index]) });
									break;
							case 'checkbox':
				for(var i = 0; i < addrinfo[obj.column_name].item.length; i++)
				{
										jQuery('input[name="'+obj.column_name+'[]"][value="'+addrinfo[obj.column_name].item[i]+'"]').each(function(index) { jQuery(this).attr('checked','checked'); });
				}
									break;
							case 'radio':
									jQuery('input[name="'+obj.column_name+'[]"][value="'+addrinfo[obj.column_name].item[0]+'"]').each(function(index) { jQuery(this).attr('checked','checked'); });
									break;
							case 'select':
									jQuery('select[name="'+obj.column_name+'"] option[value="'+addrinfo[obj.column_name]+'"]').each(function(index) { jQuery(this).attr('selected','selected'); });
									break;
							case 'date':
				var dateval = addrinfo[obj.column_name].substring(0,4) + '-' + addrinfo[obj.column_name].substring(4,6) + '-' + addrinfo[obj.column_name].substring(6,8);
									jQuery('input[name="'+obj.column_name+'"]').val(addrinfo[obj.column_name]).next('.inputDate').val(dateval);
				
									break;
			default:
				jQuery('input[name='+obj.column_name+']').val(addrinfo[obj.column_name]);
				break;
		}
	}
	/*
	jQuery('input[name=address_srl]').val(data.address_srl);
	jQuery('input[name=title]').val(data.title);
	jQuery('input[name=recipient]').val(data.recipient);
	jQuery('input[name=cellphone]').val(data.cellphone);
	jQuery('input[name=telnum]').val(data.telnum);
	jQuery('#address_list_address').append('<option value="'+data.address+'">'+data.address+'</option>');
	jQuery('#krzip_address2_address').val(data.address2);
	if (data.default=='Y') jQuery('input[name=default]').attr('checked','checked');

	jQuery('#zone_address_search_address').toggle();
	jQuery('#zone_address_list_address').toggle();
	*/
}

function apply_address(recipient, cellphone, telnum, address, address2, postcode) {
	parent.set_delivery_address(recipient, cellphone, telnum, address, address2, postcode);
	parent.close_modal();
}
function modify_address(address_srl) {
	exec_xml('nstore'
	,'getNcartAddressInfo'
	, {address_srl : address_srl}
	, completeGetAddressInfo
	, ['error','message','data']);
}
function delete_address(address_srl) {
	if (!confirm('삭제 하시겠습니까?')) return;
	exec_xml('nstore'
	,'procNcartDeleteAddress'
	, {address_srl : address_srl}
	, function() { location.href = current_url; }
	, ['error','message']);
}
function append_address() {
	jQuery('#addressList').toggle();
	jQuery('#newAddress').toggle(); 
	jQuery('#newAddrForm').get(0).reset();
}
