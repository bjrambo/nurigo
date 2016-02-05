/*
 * callback function of procNstoreDeleteCart.
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
        exec_xml('ncart', 'procNcartDeleteCart', params, completeDeleteCart, responses);
}

(function($) {
        jQuery(function($) {
                // declared in cartitems.html
                $('#deleteCart').click(function() {
                        var cart_srls = new Array();
                        $('input[name=cart]:checked').each(function() {
                                cart_srls[cart_srls.length] = $(this).val();
                        });
			if (!cart_srls.length) {
				alert(xe.lang.msg_select_items_in_order_to_delete);
				return false;
			}
                        var params = new Array();
                        params['cart_srls'] = cart_srls.join(',');
                        var responses = ['error','message'];
                        exec_xml('ncart', 'procNcartDeleteCart', params, completeDeleteCart, responses);
                });
        });
})(jQuery);
