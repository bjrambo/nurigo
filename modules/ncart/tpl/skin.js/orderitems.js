function storeInsertOrder() {
	var f = document.getElementById('fo_insert_order');
	return procFilter(f, insert_order);
}

function completeGetAddressInfo(ret_obj) {
	var data = ret_obj['data'];
	var addrinfo = data.address;
	clear_form_elements(document.getElementById('section2'));

	for (var i in fieldset)
	{
		var obj = fieldset[i];
		if (!addrinfo[obj.column_name]) continue;
		switch (obj.column_type)
		{
			case 'kr_zip':
				jQuery('input[name="'+obj.column_name+'[]"]').each(function(index) {
					jQuery(this).val(addrinfo[obj.column_name].item[index]) 
					if(jQuery(this).hasClass('krzip-hidden-postcode')) jQuery(this).parent().find('.krzip-postcode').val(jQuery(this).val());
					if(jQuery(this).hasClass('krzip-hidden-roadAddress')) jQuery(this).parent().find('.krzip-roadAddress').val(jQuery(this).val());
					if(jQuery(this).hasClass('krzip-hidden-jibunAddress')) jQuery(this).parent().find('.krzip-jibunAddress').val(jQuery(this).val());
					if(jQuery(this).hasClass('krzip-hidden-detailAddress')) jQuery(this).parent().find('.krzip-detailAddress').val(jQuery(this).val());
					if(jQuery(this).hasClass('krzip-hidden-extraAddress')) jQuery(this).parent().find('.krzip-extraAddress').val(jQuery(this).val());
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
			case 'textarea':
				jQuery('textarea[name='+obj.column_name+']').val(addrinfo[obj.column_name]);
				break;
			default:
				jQuery('input[name='+obj.column_name+']').val(addrinfo[obj.column_name]);
				break;
		}
	}
}

function apply_address_info(address_srl) {
	exec_xml('ncart'
	,'getNcartAddressInfo'
	, {address_srl : address_srl}
	, completeGetAddressInfo
	, ['error','message','data']);
}

function set_delivery_address(recipient, cellphone, telnum, address, address2, postcode) {
	jQuery('input[name=recipient_name]').val(recipient);
	jQuery('input[name=recipient_cellphone]').val(cellphone);
	jQuery('input[name=recipient_telnum]').val(telnum);
	var addr = document.getElementById('address_list_address');
	if (addr.nodeName == 'INPUT') {
		addr.value = address;
	} else {
		jQuery('select[name=address1]').html('<option value="'+address+'" selected="selected">'+address+'</option>');
	}
	jQuery('input[name=address2]').val(address2);
	jQuery('input[name=postcode]').val(postcode);
	if (address) {
		jQuery("#zone_address_search_address").hide();
		jQuery("#zone_address_list_address").show();
	} else {
		jQuery("#zone_address_search_address").show();
		jQuery("#zone_address_list_address").hide();
	}
}

function set_address_as_purchaser() {
	set_delivery_address(purchaser_name, purchaser_cellphone, purchaser_telnum, purchaser_address, purchaser_address2, '');
}

function do_order() {
	var cartnos = makeList();
	location.href = current_url.setQuery('act','dispNstoreOrderItems').setQuery('cartnos',cartnos);
}

function calculate_totalprice(deliv) {
	var amount = total_price;
	if (deliv=='N') amount -= delivery_fee;
	return amount;
}


function calculate_payamount(mileage, deliv) {
	var payment_amount = total_price - mileage;
	if (deliv=='N') payment_amount -= delivery_fee;
	return payment_amount;
}

(function($) {
	jQuery(function($) {
		$('#popAddressBook').click(function() {
			var url = current_url.setQuery('act','dispNcartAddressList');
			if(getCookie('mobile') == 'true') popup_modal(url, '배송주소록 관리', "100%", 400);
			else popup_modal(url, '배송주소록 관리', 600, 400);

			$("#modal-dialog").attr("tabindex", -1).focus();
		});
		$('#popRecentAddress').click(function() {
			var url = current_url.setQuery('act','dispNcartRecentAddress');
			if(getCookie('mobile') == 'true') popup_modal(url, '최근배송지에서 선택', "100%", 400);
			else popup_modal(url, '최근배송지에서 선택', 600, 400);

			$("#modal-dialog").attr("tabindex", -1).focus();
		});
		$('input[name=select_address]').click(function() { 
			switch ($(this).val()) {
				case 'default':
					set_delivery_address(default_recipient, default_cellphone, default_telnum, default_address, default_address2, default_postcode);
					break;
				case 'purchaser':
					if (purchaser_chk == 'N')
					{
						purchaser_name = $('#purchaser_name').val();
						purchaser_address = $("#address_list_paddress option:selected").text();
						purchaser_address2 = $('#krzip_address2_paddress').val();
						purchaser_cellphone = $('#cellphone').val();
						purchaser_telnum = $('#telnum').val();
					}
					
					set_delivery_address(purchaser_name, purchaser_cellphone, purchaser_telnum, purchaser_address, purchaser_address2, default_postcode);
					break;
				case 'new':
					set_delivery_address('', '', '', '', '', '', '');
					jQuery('input[name=recipient_name]').focus();
					break;
			}
		});

		$('input[name=input_mileage]').keyup(function() {
			var use_mileage = $(this).val();
			var reg_mileage = new RegExp("^[0-9\.]+$");
			//var reg_trim = new RegExp("^[0][0-9\.]+$");

			if (!use_mileage.match(reg_mileage))
			{
				use_mileage = 0;
				$(this).val('');
			}
			if (use_mileage < 0)
			{
				use_mileage = 0;
				$(this).val('');
			}
			raw_mileage = getRawPrice(use_mileage);

			if (total_price < raw_mileage && total_price <= my_mileage)
			{
				raw_mileage = total_price; 
				$(this).val(getPrice(raw_mileage));
			}
			if (raw_mileage > my_mileage)
			{
				raw_mileage = my_mileage;
				$(this).val(getPrice(my_mileage));
			}

			$('input[name=use_mileage]').val(raw_mileage);

			var delivfee_inadvance = $('input[name=delivfee_inadvance]:checked').val();
			var payment_amount = calculate_payamount(raw_mileage, delivfee_inadvance);
			
			$('#mileage_amount').text(getPrice(raw_mileage));
			$('#payment_amount').text(getPrice(payment_amount));
			if(payment_amount == 0)
			{	
				var answer = confirm('마일리지로 결제 하시겠습니까?');
				if(answer)
				{
					$('#fo_insert_order').submit();
				}
				else alert('마일리지 결제를 취소 하셨습니다.');
			}
		});

		$('input[name=delivfee_inadvance]').click(function() {
			var use_mileage = $('input[name=use_mileage]').val();
			if (use_mileage > my_mileage) use_mileage = my_mileage;
			var delivfee_inadvance = $(this).val();
			if (delivfee_inadvance=='Y') {
				$('#delivery_fee').text(number_format(delivery_fee));
			} else {
				$('#delivery_fee').text("0");
			}
			var orderamount = calculate_totalprice(delivfee_inadvance);
			var payamount = calculate_payamount(use_mileage,delivfee_inadvance);
			//$('#order_amount').text(number_format(orderamount));
			//$('#order_amount2').text(number_format(orderamount));
			$('#payment_amount').text(number_format(payamount));
		});
	});
}) (jQuery);
