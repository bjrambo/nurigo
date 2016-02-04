jQuery(document).ready(function (){

	var params = new Array();
	var respons = ['item_count'];
	exec_xml('ncart', 'getNcartCartItems', params, function(ret_obj) {
		jQuery("#count_cart_items").html(ret_obj['item_count']);
	},respons);

});
