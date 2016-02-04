function completeDeleteItems(ret_obj) {
	alert(ret_obj['message']);
	location.href = current_url;
}

/**
 * load cart items
 */
function load_cart() {
	var params = new Array();
	var response_tags = new Array('error','message','data','mileage');
	exec_xml('nstore', 'getNstoreCartItems', params, function(ret_obj) {
		$list = jQuery('#itemList').empty();
		if (ret_obj['data']) {
			var data = ret_obj['data']['item'];
			if (!jQuery.isArray(data)) {
				data = new Array(data);
			}
			var price = 0;
			for (var i = 0; i < data.length; i++) {
				var url = current_url.setQuery('item_srl',data[i].item_srl);
				$list.append('<li><div><input type="checkbox" name="cart" value="'+ data[i].cart_srl +'" /></div><div><a href="'+ url +'"><img src="' + data[i].thumbnail_url + '" /></a></div><div>'+ data[i].currency_price +'</div><div><div style="float:left;"><input type="text" id="quantity_'+ data[i].cart_srl +'" class="quantity" style="width:30px;" value="'+ data[i].quantity +'" /></div><div style="float:left;"><div class="iconUp" data-for="quantity_'+ data[i].cart_srl +'"></div><div class="iconDown" data-for="quantity_'+ data[i].cart_srl +'"></div></div><div style="float:left"><a class="button"><span class="updateQuantity" data-for="quantity_'+ data[i].cart_srl +'">' + xe.lang.cmd_change + '</span></a></div></li>');
				price += parseFloat(data[i].price) * parseInt(data[i].quantity);
			}
			jQuery('#bottom-cart .cart-header .cartQuantity').text(data.length);
			jQuery('#bottom-cart .cart-header .cartPrice').text(number_format(price));
		} else {
			$list.append('<li style="margin:40px; width:200px;">' + xe.lang.msg_cart_empty + '</li>');
		}
		jQuery('#bottom-cart .mileage .my_mileage').text(number_format(ret_obj['mileage']));
	}, response_tags);
}

/**
 * load favorite items.
 */
function load_favorites() {
	var params = new Array();
	var response_tags = new Array('error','message','data','mileage');
	exec_xml('nstore', 'getNstoreFavoriteItems', params, function(ret_obj) { 
		$list = jQuery('#favoriteList').empty();
		if (ret_obj['data']) {
			var data = ret_obj['data']['item'];
			if (!jQuery.isArray(data)) {
				data = new Array(data);
			}
			var price = 0;
			for (var i = 0; i < data.length; i++) {
				var url = current_url.setQuery('item_srl',data[i].item_srl);
				$list.append('<li><div><input type="checkbox" name="cart" value="'+ data[i].item_srl +'" /></div><div><a href="'+ url +'"><img src="' + data[i].thumbnail_url + '" /></a></div><div>'+ data[i].currency_price +'</div></li>');
				price += parseFloat(data[i].price) * parseInt(data[i].quantity);
			}
			jQuery('#bottom-cart .cart-header .favoriteQuantity').text(data.length);
		} else {
			$list.append('<li style="margin:40px; width:200px;">' + xe.lang.msg_no_favorites + '</li>');
		}
		jQuery('#bottom-cart .mileage .my_mileage').text(number_format(ret_obj['mileage']));
	}, response_tags);
}


function delete_cart() {
	var cart_srls = new Array();
	jQuery('input[name=cart]:checked', '#itemList').each(function() {
		cart_srls[cart_srls.length] = jQuery(this).val();
	});
	if (!cart_srls.length) {
		alert(xe.lang.msg_select_items_in_order_to_delete);
		return;
	}

	var params = new Array();
	params['cart_srls'] = cart_srls.join(',');
	var responses = ['error','message'];
	exec_xml('nstore', 'procNstoreDeleteCart', params, completeDeleteItems, responses);
}

function delete_favorites() {
	var item_srls = new Array();
	jQuery('input[name=cart]:checked', '#favoriteList').each(function() {
		item_srls[item_srls.length] = jQuery(this).val();
	});
	if (!item_srls.length) {
		alert(xe.lang.msg_select_items_in_order_to_delete);
		return;
	}
	var params = new Array();
	params['item_srls'] = item_srls.join(',');
	var responses = ['error','message'];
	exec_xml('nstore', 'procNstoreDeleteFavoriteItems', params, completeDeleteItems, responses);
}

function make_slider() {
	var container = jQuery('div.sliderGallery');
	var ul = jQuery('ul', container);
	
	var itemsWidth = ul.innerWidth() - container.outerWidth();
	
	jQuery('.slider', container).slider({
		min: 0,
		max: itemsWidth,
		handle: '.handle',
		stop: function (event, ui) {
			ul.animate({'left' : ui.value * -1}, 500);
		},
		slide: function (event, ui) {
			ul.css('left', ui.value * -1);
		}
	});
}

function goto_order(login_chk) {
	/*
	var cart_srls = new Array();
	jQuery('input[name=cart]', '#bottom-bar').each(function() {
		cart_srls[cart_srls.length] = jQuery(this).val();
	})
	var cartnos = cart_srls.join(',');
	*/
	//var url = current_url.setQuery('act','dispNstoreOrderItems').setQuery('cartnos',cartnos);
	
	if(login_chk == "Y")
	{
		var url = current_url.setQuery('act','dispNstoreOrderItems');
		location.href=url;
	}
	else
	{
		var url = current_url.setQuery('act','dispNstoreLogin');
		location.href=url;
	}
}

function open_cart(which) {
	if (typeof(which)=='undefined') which = 'cart';
	jQuery('#bottom-cart').cart('open');
	jQuery('#bottom-cart').cart('show_'+which);
}

/*
function close_cart() {
	var header_height = jQuery('#bottom-bar .header').css('height');
	jQuery('#bottom-bar').animate({height:header_height}, 500);
}
*/

(function($) {
 	jQuery(function($) {
		$('#bottom-cart').cart();
		$('#cartLayer').carrousel();
		$('#favoriteLayer').carrousel();
	});
})(jQuery);
