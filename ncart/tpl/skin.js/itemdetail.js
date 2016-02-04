function getTotalPrice() {
	var total_amount = 0;
	jQuery('#selected_options tr').each(function() {
		if(jQuery('.quantity', this).val() < 1)
		{
			alert('수량은 1개 이상 적어주세요.'); 
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
	jQuery('#total_amount').html('<span>총 상품금액: <span class="red">'+ number_format(total_price) +'</span></span>');
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
			$('#selected_options').append('<tr id="option_'+option_srl+'" data-price="'+ (item_price + (price)) +'"><td>'+ title + str_price + '</td><td><input type="hidden" name="option_srls" value="' + option_srl + '" /><input type="text" name="quantities" class="quantity" value="1" />개</td><td><span onclick="jQuery(this).parent().parent().remove(); printTotalPrice();" class="deleteItem">X</span></td><td><span>' + number_format(item_price + (price)) + '</span></td></tr>');
		}

		printTotalPrice();
	});
	$('#selected_options input').live('change', function() {
		printTotalPrice();
	});
});
