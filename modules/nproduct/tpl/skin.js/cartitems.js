/**
 * callback function of procNstore_digitalDeleteCart.
 */
function completeDeleteCart(ret_obj) {
	alert(ret_obj['message']);
	location.href = current_url;
}

function deleteCartItem(cart_srl) {
	var cart_srls = new Array();
	cart_srls[cart_srls.length] = cart_srl;

	var params = new Array();
	params['cart_srls'] = cart_srls.join(',');
	var responses = ['error','message'];
	exec_xml('nproduct', 'procNproductDeleteCart', params, completeDeleteCart, responses);
}

(function($) {
	jQuery(function($) {
		// declared in cartitems.html
		$('#deleteCart').click(function() {
			var cart_srls = new Array();
			$('input[name=cart]:checked').each(function() {
				cart_srls[cart_srls.length] = $(this).val();
			});
			var params = new Array();
			params['cart_srls'] = cart_srls.join(',');
			var responses = ['error','message'];
			exec_xml('nproduct', 'procNproductDeleteCart', params, completeDeleteCart, responses);
		});
	});
})(jQuery);
