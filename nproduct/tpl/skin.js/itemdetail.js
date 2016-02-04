function getTotalPrice() {
	var total_amount = 0;
	jQuery('#selected_options tr').each(function() {
		if(jQuery('.quantity', this).val() < 1)
		{
			alert(xe.lang.msg_input_more_than_one); 
			jQuery('.quantity').val('1');
			var quantity = 1;
		}
		else var quantity = jQuery('.quantity', this).val();
		var price = parseFloat(jQuery(this).attr('data-price'));
		total_amount += ((price) * quantity);
	});
	return total_amount;
}

function printTotalPrice() {
	var total_price = getTotalPrice();
	jQuery('#total_amount')
		.html('<span>' + xe.lang.total_amount + ': <span class="red">'+ number_format(total_price) +'</span></span>')
		.attr('data-amount', total_price);
	calculate_sum();
}

function calculate_sum() {
	var related_sum = g_discounted_price;
	var total_amount = parseInt(jQuery('#total_amount').attr('data-amount'));
	if(total_amount > 0) related_sum = total_amount;
	jQuery('input[name=related_item]:checked').each(function(idx, elm) {
		var price = parseInt(jQuery(elm).attr('data-price'));
		related_sum += price;
	});
	jQuery('#related_sum').html(number_format(related_sum));
}


jQuery(function($) {
	$('#select_options').change(function() {
		var option_srl = $(this).val();
		if (!option_srl) return;
		var $opt = $('option:selected',this);
		var title = $opt.attr('data-title');
		var price = parseFloat($opt.attr('data-price'));
		var str_price='';
		if (price > 0) str_price = '(' + '+' + number_format(price) + ')';
		if (price < 0) str_price = '(' + number_format(price) + ')';

		if (!$('#option_'+option_srl).length) {
			$('#selected_options').append('<tr id="option_'+option_srl+'" data-price="'+ (g_discounted_price + (price)) +'"><td>'+ title + str_price + '</td><td><input type="hidden" name="option_srls" value="' + option_srl + '" /><input type="text" name="quantities" class="quantity" value="1" />' + xe.lang.each + '</td><td><span onclick="jQuery(this).parent().parent().remove(); printTotalPrice();" class="deleteItem">X</span></td><td><span>' + number_format(g_discounted_price + (price)) + '</span></td></tr>');
		}

		printTotalPrice();
	});
	$('#selected_options input').live('change', function() {
		printTotalPrice();
	});

	calculate_sum();
	jQuery('input[name=related_item]').change(function() {
		calculate_sum();
	});
});


/**
 * return cart parameter formatted string.
 * format [ {item_srl:x, option_srl:x, quantity:x} , {item_srl:x, option_srl:x, quantity:x},...  ]
 */
function getCartParamsInDetailPage(item_srl) {
	var param = new Array();

	var options_count = 0;
	jQuery('input[name=option_srls]').each(function(idx, elem) {
		var option_srl = jQuery(elem).val();
		var item = new Object();
		item.item_srl = item_srl;
		item.option_srl = option_srl;
		item.quantity = jQuery(elem).next('.quantity').val();
		param[param.length] = item;
		options_count++;
	});

	if (options_count == 0) {
		var item = new Object();
		item.item_srl = item_srl;
		item.quantity = jQuery('#quantity_'+item_srl).val();
		param[param.length] = item;
	}

	jQuery('input[name=related_item]:checked').each(function(idx, elem) {
		var item = new Object();
		item.item_srl = jQuery(elem).val();
		param[param.length] = item;
	});

	return param;
}
/**
 * add items into cart
 */
function addItemsToCartInDetailPage(item_srl) {
	var param = getCartParamsInDetailPage(item_srl);
	addItemsToCartObj(param);
}

/**
 * direct order
 */
function orderItemsInDetailPage(item_srl, ncart_mid) {
	var param = getCartParamsInDetailPage(item_srl);
	orderItemsDirectly(param, ncart_mid);
}
