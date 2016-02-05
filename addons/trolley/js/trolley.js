function delCookie(name, path) //cookie 삭제 
{
	var expireDate = new Date();
  
	//어제 날짜를 쿠키 소멸 날짜로 설정한다.
	expireDate.setDate( expireDate.getDate() - 1 );
	document.cookie = name + "= " + "; expires=" + expireDate.toGMTString() + "; path=/";
	
	document.cookie = name + "="
		+ ((path == null) ? "" : "; path=" + path)
		+ ""
		+ "; expires=Thu, 01-Jan-70 00:00:01 GMT";
}


jQuery(document).ready(function ($){
	/*
	var omitformtags=["input", "select", "span"]
	omitformtags=omitformtags.join("|")
	function disableselect(e){
	if (omitformtags.indexOf(e.target.tagName.toLowerCase())==-1)
	return false
	}
	function reEnable(){
	return true
	}
	if (typeof document.onselectstart!="undefined")
	document.onselectstart=new Function ("return false")
	else{
	document.onmousedown=disableselect
	document.onmouseup=reEnable
	}
	*/

	var r_prefix = jQuery("#r_prefix").val();
	var rc_name = r_prefix + 'Recent_item';
	var Chk_Itemsrl = getCookie(rc_name);

	if(Chk_Itemsrl)
	{
		var Chk_Itemsrl_1 = Chk_Itemsrl.split(",");
		for (var i in Chk_Itemsrl_1) 
		{
			if(Chk_Itemsrl_1[i] == '')
			{
				Chk_Itemsrl_1.pop();
				delCookie(rc_name);
				setCookie(rc_name, Chk_Itemsrl_1);
			}
		}
	}

	name_set = r_name_set();
	t_module_name = name_set['module_name'];
	t_action_name = name_set['action_name'];

	// 장바구니, 위시리스트 불러오기.
	if($("#logged_info").val())
	{
		r_load_trolley();
	}
	else
	{
		r_load_recent();
		r_load_cart();
	}


	// 탭스메뉴 숨기기.
	$('#t_menu_wrap').tabs();
	$('#t_menu_wrap').tabs({
		select: function(event, ui)
		{
			var t = $(event.target);
			if(ui.index == 0)
			{
				items_show("#cart_total");

			}
			else if(ui.index == 1)
			{
				items_show("#recent_total");
			}
			else if(ui.index == 2)
			{
				items_show("#wish_total");
			}
		}
	});


	// 최근본 상품 컨텐츠 갯수에 따른 변화.
	url_count = getCookie('url_count');	
	
	jQuery("#wish_total").css("display","none");
	jQuery("#recent_total").css("display","none");


	// pagenum set
	
	if(!getCookie('r_pagenum'))
	{
		setCookie('r_pagenum',1);
	}
	if(!getCookie('c_pagenum'))
	{
		setCookie('c_pagenum',1);
	}
	if(!getCookie('w_pagenum'))
	{
		setCookie('w_pagenum',1);
	}

	var add_num = parseInt(getCookie('r_pagenum'));
	var url_count = getCookie('url_count');	
	var r_pagenum = getCookie('r_pagenum');

	jQuery("#box_open").click(box_view);
	jQuery("#box_fold").click(box_view);

	$('.updateQuantity_').live('click', function() {
		var target = $(this).attr('data-for');
		var ival = parseInt($('#'+target).val());
		var params = new Array();
		params['cart_srl'] = target.replace(/[^0-9]/g,'');;
		params['quantity'] = ival;

		var responses = ['error','message'];
		exec_xml(t_module_name, 'proc' + t_action_name + 'UpdateQuantity', params, function (ret_obj){ alert(ret_obj['message']); }, responses);
		r_load_cart();
	});

	//item_reset();
	r_focus_item();
	recent_hide();

	box_view('setting');

});

// 레이어 팝업 위치
function fnc_cart_scroll_set()
{
	var popID = jQuery('.trolley_pop');
	move_Y = (jQuery(window).height() / 2) - (popID.height() / 2);
	move_X = (jQuery(window).width() / 2) - (popID.width() / 2);
	popID.css({'top' : move_Y});
	popID.css({'left' : move_X});
}



function items_show(items)
{
	jQuery("#cart_total").css("display","none");
	jQuery("#wish_total").css("display","none");
	jQuery("#recent_total").css("display","none");

	jQuery(items).css("display","");
}

/* diplay hide */
function recent_hide(menu_name, open)
{
	if(!menu_name && !open)
	{
		jQuery("#f_recent_items").hide();
		jQuery("#f_wish_items").hide();
		jQuery("#f_cart_items").hide();
		jQuery(getCookie('recent_hide')).show();
	}
	else
	{
		jQuery("#f_recent_items").hide();
		jQuery("#f_wish_items").hide();
		jQuery("#f_cart_items").hide();
		jQuery(menu_name).show();

		delCookie('recent_hide');
		setCookie('recent_hide', menu_name);
	}
}

function box_view(set, pop_call)
{
	if(!getCookie('t_open_set'))
	{
		delCookie('t_open_set');
		setCookie('t_open_set', 'o');
	}

	if(!getCookie('t_itShow'))
	{
		setCookie('t_itShow', 'false');
	}
	itShow = getCookie('t_itShow');

	if(itShow == 'true')
	{
		if(getCookie('t_open_set') == 'o')
		{
			jQuery("#fold_box").hide();
			jQuery("#open_box").show();
		}

		if(set != 'setting')
		{
			itShow = false;
			delCookie('t_itShow');
			setCookie('t_itShow', itShow);
			box_view('setting');
		}
	}
	else 
	{
		if(getCookie('t_open_set') == 'o')
		{
			jQuery("#fold_box").show();
			jQuery("#open_box").hide();
		}
		
		if(set != 'setting')
		{
			itShow = true;
			delCookie('t_itShow');
			setCookie('t_itShow', itShow);
			box_view('setting');
		}
	}

	if(getCookie('t_open_set') == 'c')
	{
		jQuery("#fold_box").hide();
		jQuery("#open_box").hide();
		if(pop_call)
		{
			delCookie('t_open_set');
			setCookie('t_open_set', 'o');
			delCookie('t_itShow');
			setCookie('t_itShow', 'false');
			jQuery("#fold_box").show();
		}
	}
}

/* 최근본 상품 아이템 삭제 */
var item_number;
function delitem(item_number, type)
{
	var r_prefix = jQuery("#r_prefix").val();
	var rc_name = r_prefix + 'Recent_item';
	var getRecent_item = getCookie(rc_name);
	var replace_1 = getRecent_item.replace(item_number+",",'');
	var replace_2 = replace_1.replace(item_number,'');
	var replace_3 = replace_2.split(',');
	delCookie(rc_name);
	setCookie(rc_name, replace_3);
	setCookie('r_pagenum', 1);

	r_load_recent();
	/*
	if(!type)
	{
		location.reload(); 
	}
	*/
}

function delitems(item_numbers)
{

	for(var i = 0; i < item_numbers.length; i++)
	{
		delitem(item_numbers[i], 'N');
	}

	r_load_recent();
	//location.reload();

}


/* arrow */

function r_arrow(type)
{
	items_count = jQuery("#fold_box .recent_count").text();
	items_count = parseInt(items_count);
	pagenum = getCookie('r_pagenum');
	total_page = Math.ceil(items_count / 4);

	if(type =='left')
	{
		pagenum = parseInt(pagenum) - 1;
		if(pagenum < 1)
		{
			pagenum = total_page;
		}

	}
	else if(type =="right")
	{
		pagenum = parseInt(pagenum) + 1;
		if(pagenum > total_page)
		{
			pagenum = 1;
		}
	}

	delCookie('r_pagenum');
	setCookie('r_pagenum', pagenum);

	r_load_recent();
}

function c_arrow(type)
{
	items_count = jQuery("#fold_box .cartQuantity").text();
	items_count = parseInt(items_count);
	pagenum = getCookie('c_pagenum');
	total_page = Math.ceil(items_count / 4);

	if(type =='left')
	{
		pagenum = parseInt(pagenum) - 1;
		if(pagenum < 1)
		{
			pagenum = total_page;
		}

	}
	else if(type =="right")
	{
		pagenum = parseInt(pagenum) + 1;
		if(pagenum > total_page)
		{
			pagenum = 1;
		}
	}

	delCookie('c_pagenum');
	setCookie('c_pagenum', pagenum);

	r_load_cart();
}

function w_arrow(type)
{
	items_count = jQuery("#fold_box .wishQuantity").text();
	items_count = parseInt(items_count);
	pagenum = getCookie('w_pagenum');
	total_page = Math.ceil(items_count / 4);

	if(type == 'left')
	{
		pagenum = parseInt(pagenum) - 1;
		if(pagenum < 1)
		{
			pagenum = total_page;
		}

	}
	else if(type == "right")
	{
		pagenum = parseInt(pagenum) +1;
		if(pagenum > total_page)
		{
			pagenum = 1;
		}
	}

	delCookie('w_pagenum');
	setCookie('w_pagenum', pagenum);

	
	r_load_favorites();
}
/* arrow end*/

/*
function item_reset()
{
	var page_num = parseInt(getCookie('r_pagenum'));
	var url_count = getCookie('url_count');	

	for(i = 0; i < 14; i++)
	{
		jQuery("#url_"+i).css("display","none");
	}
	
	if(page_num == 1 || !page_num)
	{
		for(var r = url_count; r > url_count - 4; r--)
		{
			jQuery("#url_"+r).css("display","");
		}
	}
	if(page_num == 2)
	{
		for(var r = url_count - 4; r > url_count - 8; r--)
		{
			jQuery("#url_"+r).css("display","");
		}
	}
	if(page_num == 3)
	{
		for(var r = url_count - 8; r > url_count - 12; r--)
		{
			jQuery("#url_"+r).css("display","");
		}
	}

	t_page_num = jQuery('.t_page_num').empty();
	t_page_num.append(page_num);
}
*/


function del_items(checkbox_name)
{
	name_set = r_name_set();
	t_module_name = name_set['module_name'];
	t_action_name = name_set['action_name'];

	var cart = document.getElementsByName(checkbox_name);
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

	if(cart_array == '')
	{
		alert('체크를 해주세요');
	}
	else
	{
		if(checkbox_name == 't_cart')
		{
			params['cart_srls'] = cart_array;
			exec_xml(t_module_name, 'proc'+t_action_name+'DeleteCart', params, function(ret_obj) {
				r_load_cart();
			});
		}
		else if(checkbox_name == 'wish')
		{
			params['item_srls'] = cart_array;
			exec_xml(t_module_name, 'proc'+t_action_name+'DeleteFavoriteItems', params, function(ret_obj) {
				r_load_favorites();
			});
		}
		else if(checkbox_name == 'lately_url')
		{
			delitems(cart_array);
		}
	}

//location.href=current_url;
	
}

function r_del_item(type, item_srl)
{
	name_set = r_name_set();
	t_module_name = name_set['module_name'];
	t_action_name = name_set['action_name'];

	var params = new Array();
	if(type == 'cart')
	{
		params['cart_srls'] = item_srl;
		exec_xml(t_module_name, 'proc'+t_action_name+'DeleteCart', params, function(ret_obj) {
			r_load_cart();
		});
	}
	else if(type == 'wish')
	{
		params['item_srls'] = item_srl;
		exec_xml(t_module_name, 'proc'+t_action_name+'DeleteFavoriteItems', params, function(ret_obj) {
			r_load_favorites();
		});
	}
}

// 장바구니 아이템 셋업
function r_load_cart(call) 
{
	fnc_cart_scroll_set();

	if(call)
	{
		Popon(call);
		recent_hide("#f_cart_items", 'open');
		if(getCookie('t_open_set') == 'c')
		{
			box_view(null,'call');
		}
	}

	name_set = r_name_set();
	t_module_name = name_set['module_name'];
	t_action_name = name_set['action_name'];

	cp_num = parseInt(getCookie('c_pagenum'));
	c_pagenum = getCookie('c_pagenum');
	c_pagenum = c_pagenum * 5;
	c_divisionc = Math.ceil(c_pagenum / 5);
	c_pagenum = c_pagenum - c_divisionc; 

	jQuery('.f_cart_items').empty();

	var params = new Array();
	params['image_width'] = 80;
	params['image_height'] = 80;
	var response_tags = new Array('error','message','data','mileage');
	exec_xml(t_module_name, 'getNcartCartItems', params, function(ret_obj) {
		
		if (ret_obj['data']) {
			jQuery.list = jQuery('#cart_items').empty();
			jQuery.fold_list = jQuery('.f_cart_items').empty();
			var data = ret_obj['data']['item'];
			if (!jQuery.isArray(data)) {
				data = new Array(data);
			}
			var price = 0;
			var t_cart_length = Math.ceil(data.length / 4);

			if(cp_num > t_cart_length)
			{
				delCookie('c_pagenum');
				setCookie('c_pagenum', 1);

				c_pagenum = getCookie('c_pagenum');
				c_pagenum = c_pagenum * 5;
				c_divisionc = Math.ceil(c_pagenum / 5);
				c_pagenum = c_pagenum - c_divisionc; 
			}

			for (var i = 0; i < data.length; i++) {

				t_thumbnail_url = data[i].thumbnail_url;
				if(!t_thumbnail_url) t_thumbnail_url = t_no_image;

				item_name = data[i].item_name;
				if(item_name.length > 35) item_name = item_name.substr(0, 35)+"...";

				var url = current_url.setQuery('document_srl',data[i].document_srl);
				jQuery.list.append('<ul><li class="n_item_set"><div class="item_image"><input type="checkbox" name="t_cart" value="'+ data[i].cart_srl +'" /><a href="'+ url +'"><img src="' + t_thumbnail_url + '" /></a></div><div class="item_text"><div class="r_item_name">'+ item_name +'</div><span>' + xe.lang.purchase + ':</span> '+ data[i].currency_discounted_price +'<br /><div class="r_button"><a class="button"><span class="updateQuantity_" data-for="quantity__'+ data[i].cart_srl +'">' + xe.lang.change + '</span></a></div><div class="r_icon"><span>' + xe.lang.change + ' : </span> <input type="text" id="quantity__'+ data[i].cart_srl +'" class="quantity" style="width:30px;" value="'+ data[i].quantity +'" /><div class="icon_wrap"><div class="iconUp" data-for="quantity__'+ data[i].cart_srl +'"></div><div class="iconDown" data-for="quantity__'+ data[i].cart_srl +'"></div></div></div></div></li></ul>');
				price += parseFloat(data[i].discounted_price);
			}

			jQuery('#open_box .menu_cart .cartQuantity').text(data.length);
			jQuery('#fold_box .cartQuantity').text(data.length);
			jQuery('#t_menu_wrap .c_total').text(getPrintablePrice(price));

			for (var p = c_pagenum - 4; p < c_pagenum; p++)
			{
				if(!data[p])
				{
					break;
				}

				t_thumbnail_url = data[p].thumbnail_url;
				if(!t_thumbnail_url) t_thumbnail_url = t_no_image;

				item_name = data[p].item_name;
				if(item_name.length > 8) item_name = item_name.substr(0, 8)+"...";

				var url = current_url.setQuery('document_srl',data[p].document_srl);
				jQuery.fold_list.append('<div class="focus_item"><div class="over_info"><p>'+ item_name +'<br> <span>'+ data[p].currency_discounted_price +'</span></p></div><div class="recent_item_close" onClick="r_del_item(\'cart\',\''+ data[p].cart_srl +'\')"></div><a href="'+ url +'"><img src="'+ t_thumbnail_url +'"></a></div>');
			}

			jQuery.fold_list.append('<span class="left_arrow" onClick="c_arrow(\'left\')"></span> <span>'+ getCookie('c_pagenum') +'</span> <span class="right_arrow" onClick="c_arrow(\'right\')"></span>');
		} 
		else {
			jQuery.list = jQuery('#cart_items').empty();
			jQuery('#open_box .menu_cart .cartQuantity').text(0);
			jQuery('#fold_box .cartQuantity').text(0);
			jQuery('#t_menu_wrap .c_total').text(0);
			jQuery.list.append('<li class="emty_items" >'+xe.lang.empty_cart+'</li>');
		}
		r_focus_item();
	}, response_tags);
}


// 관심상품
function r_load_favorites(call) 
{
	if(call)
	{
		Popon(call);
		recent_hide("#f_wish_items", 'open');
		if(getCookie('t_open_set') == 'c')
		{
			box_view(null,'call');
		}
	}


	name_set = r_name_set();
	t_module_name = name_set['module_name'];
	t_action_name = name_set['action_name'];

	wp_num = parseInt(getCookie('w_pagenum'));
	w_pagenum = getCookie('w_pagenum');
	w_pagenum = w_pagenum * 5;
	w_divisionc = Math.ceil(w_pagenum / 5);
	w_pagenum = w_pagenum - w_divisionc; 

	jQuery.fold_list = jQuery('.f_wish_items').empty();


	var params = new Array();
	params['image_width'] = 80;
	params['image_height'] = 80;
	var response_tags = new Array('error','message','data','mileage');
	exec_xml(t_module_name, 'get'+t_action_name+'FavoriteItems', params, function(ret_obj) { 
		if (ret_obj['data']) {
			jQuery.list = jQuery('#wish_items').empty();
			jQuery.fold_list = jQuery('.f_wish_items').empty();
			var data = ret_obj['data']['item'];
			if (!jQuery.isArray(data)) {
				data = new Array(data);
			}

			t_pagenum_reset('w_pagenum',data.length);
			var price = 0;
			var t_wish_length = Math.ceil(data.length / 4);

			if(wp_num > t_wish_length)
			{
				delCookie('w_pagenum');
				setCookie('w_pagenum', 1);

				w_pagenum = getCookie('w_pagenum');
				w_pagenum = w_pagenum * 5;
				w_divisionc = Math.ceil(w_pagenum / 5);
				w_pagenum = w_pagenum - w_divisionc; 
			}

			for (var i = 0; i < data.length; i++) {
				var url = current_url.setQuery('document_srl',data[i].document_srl);
				t_thumbnail_url = data[i].thumbnail_url;
				if(!t_thumbnail_url) t_thumbnail_url = t_no_image;

				item_name = data[i].item_name;
				if(item_name.length > 35) item_name = item_name.substr(0, 35)+"...";

				jQuery.list.append('<ul><li class="n_item_set"><div class="item_image"><input type="checkbox" name="wish" value="'+ data[i].item_srl +'" /><a href="'+ url +'"><img src="' + t_thumbnail_url + '" /></a></div><div class="item_text"><div class="r_item_name">'+ item_name +'</div>'+ data[i].currency_discounted_price +'</div></li></ul>');
			}

			for (var p = w_pagenum - 4 ; p < w_pagenum; p++)
			{
				if(!data[p])
				{
					break;
				}
				var url = current_url.setQuery('document_srl',data[p].document_srl);
				t_thumbnail_url = data[p].thumbnail_url;
				if(!t_thumbnail_url) t_thumbnail_url = t_no_image;

				item_name = data[p].item_name;
				if(item_name.length > 8) item_name = item_name.substr(0, 8)+"...";

				jQuery.fold_list.append('<div class="focus_item"><div class="over_info"><p>'+ item_name +'<br> <span>'+ data[p].currency_discounted_price +'</span></p></div><div class="recent_item_close" onClick="r_del_item(\'wish\',\''+ data[p].item_srl +'\')"></div><a href="'+ url +'"><img src="'+ t_thumbnail_url +'" /></a></div>');
			}

			jQuery.fold_list.append('<span class="left_arrow" onClick="w_arrow(\'left\')"></span> <span>'+ getCookie('w_pagenum') +'</span> <span class="right_arrow" onClick="w_arrow(\'right\')"></span>');
			jQuery('#open_box .menu_wish .wishQuantity').text(data.length);
			jQuery('#fold_box .wishQuantity').text(data.length);
		} else {
			jQuery.list = jQuery('#wish_items').empty();
			jQuery('#open_box .menu_wish .wishQuantity').text(0);
			jQuery('#fold_box .wishQuantity').text(0);
			jQuery.list.append('<li class="emty_items">'+xe.lang.empty_wish+'</li>');
		}
		r_focus_item();
	}, response_tags);
}

function r_load_recent()
{
	rp_num = parseInt(getCookie('r_pagenum'));
	r_pagenum = getCookie('r_pagenum');
	r_pagenum = r_pagenum * 5;
	r_divisionc = Math.ceil(r_pagenum / 5);
	r_pagenum = r_pagenum - r_divisionc; 

	var r_prefix = jQuery("#r_prefix").val();
	var rc_name = r_prefix + 'Recent_item';
	var params = new Array();

	if(getCookie(rc_name))
	{
		params['document_srls'] = getCookie(rc_name);
		params['image_width'] = 80;
		params['image_height'] = 80;
		var responses = ['error','message','data'];
		exec_xml('nproduct', 'getNproductItemInfos', params, function(ret_obj) {
			if(ret_obj['data'])
			{
				var data = ret_obj['data']['item_list']['item'];
				var r_recent_length = Math.ceil(data.length / 4);

				if(rp_num > r_recent_length)
				{
					delCookie('r_pagenum');
					setCookie('r_pagenum', 1);

					r_pagenum = getCookie('r_pagenum');
					r_pagenum = r_pagenum * 5;
					r_divisionc = Math.ceil(r_pagenum / 5);
					r_pagenum = r_pagenum - r_divisionc; 
				}

				if (!jQuery.isArray(data)) {
					data = new Array(data);
				}

				jQuery.list = jQuery("#recent_arrow > ul").empty();
				
				for (var i = 0; i < data.length; i++) 
				{
					if(!data[i].thumbnail_url) item_src = t_no_image;
					else item_src = data[i].thumbnail_url;

					url = current_url.setQuery('document_srl',data[i].document_srl);

					item_name = data[i].item_name;
					if(item_name.length > 35) item_name = item_name.substr(0, 35)+"...";

					jQuery.list.append('<li class="n_item_set"><div class="item_image"><input type="checkbox" name="lately_url" value="' + data[i].document_srl + '" /><a href="' + url + '"><img src="' + item_src + '"></a></div><div class="item_text"><a href="' + url + '"><p>' + item_name + '<br />' + data[i].currency_discounted_price + '</p></a></div></li>');
				}


				jQuery.list = jQuery("#f_recent_items").empty();
				jQuery.list.append('<div class="recent_arrow f_recent_items r_height"></div>');
				jQuery.fold = jQuery(".f_recent_items").empty();

				for (var p = r_pagenum - 4 ; p < r_pagenum; p++)
				{
					if(!data[p]) break;
					if(!data[p].thumbnail_url) item_src = t_no_image;
					else item_src = data[p].thumbnail_url;

					url = current_url.setQuery('document_srl',data[p].document_srl);

					item_name = data[p].item_name;
					if(item_name.length > 8) item_name = item_name.substr(0, 8)+"...";

					jQuery.fold.append('<div class="focus_item"><div class="over_info"><p>' + item_name + '<br> <span class="price">' + data[p].currency_discounted_price + '</span></p></div><div class="recent_item_close" onClick="delitem(\'' + data[p].document_srl + '\')"></div><a href="' + url + '"><img src="' + item_src + '"/></a></div>');

				}
				
				jQuery.fold.append('<span class="left_arrow" onClick="r_arrow(\'left\')" ></span> <span class="numguride t_page_num">' + getCookie('r_pagenum') + '</span> <span class="right_arrow" onClick="r_arrow(\'right\')" > </span></div>');
				
				jQuery.quantity = jQuery(".recent_count").empty();
				jQuery.quantity.append(data.length);
			}
			
			r_focus_item();
		}, responses);	
	}
	else
	{
		jQuery.list = jQuery("#recent_items").empty();
		jQuery.list.append('<div id="recent_arrow"><ul><li class="emty_items" style="margin-left:0">' + xe.lang.view_product + '</li></ul></div></ul></div>');
		jQuery.list = jQuery("#f_recent_items").empty();
		jQuery.list.append('<div class="recent_arrow f_recent_items r_height"><span class="nothing"></span></div>');

		jQuery.quantity = jQuery(".recent_count").empty();
		jQuery.quantity.append("0");
	}
}

function r_load_trolley()
{
	r_load_cart();
	r_load_favorites();
	r_load_recent();
}

function r_name_set()
{
	recent_module = new Array();
	r_module_name = jQuery("#module_name").val();
	if(r_module_name == "nstore_digital") r_action_name = "Nstore_digital";
	else if(r_module_name == "nstore") r_action_name = "Nstore";
	else if(r_module_name == "ncart") r_action_name = "Ncart";

	recent_module['module_name'] = r_module_name;
	recent_module['action_name'] = r_action_name;

	return recent_module;
}

function r_focus_item()
{
	jQuery(".focus_item").mouseover(function() {
		jQuery(this).addClass('on');
	});
	jQuery(".focus_item").mouseout(function() {
		jQuery(this).removeClass('on');
	});
}

function r_view(kind)
{
	if(jQuery('#t_ncart_mid').val())
	{
		// ncart일땐 mid값으로 구분한다.
		var t_ncart_mid = jQuery('#t_ncart_mid').val();

		if(kind == 'Cart')
		{
			location.href = current_url.setQuery('mid', t_ncart_mid).setQuery('document_srl', '');
		}
		else if(kind == 'Wish')
		{
			location.href = current_url.setQuery('mid', t_ncart_mid).setQuery('act','dispNcartFavoriteItems').setQuery('document_srl', '');
		}
		else if(kind == 'Order')
		{
			login_chk = jQuery('#logged_info').val();
			if(login_chk == "Y")
			{
				var url = current_url.setQuery('mid', t_ncart_mid).setQuery('act', 'dispNcartOrderItems').setQuery('document_srl', '');
				location.href = url;
			}
			else
			{
				alert('로그인후 사용가능합니다.');
				return;
			}
		}
	}
	else
	{	
		alert("ncart mid값을 설정해주세요.");
		return;
	}
}




function getPrice(price) 
{
	var division = Math.pow(10, 0);
	return price / division;
}

function getPrintablePrice(price) 
{
	var num = getPrice(price);
	return number_format(num.toFixed(0));
}

function number_format(nStr)
{
    nStr += '';
    x = nStr.split('.');
    x1 = x[0];
    x2 = x.length > 1 ? '.' + x[1] : '';
    var rgx = /(\d+)(\d{3})/;
    while (rgx.test(x1)) {
        x1 = x1.replace(rgx, '$1' + ',' + '$2');
    }
    return x1 + x2;
}


function t_pagenum_reset(page_name, count)
{
	c_pagenum = getCookie('c_pagenum');
	c_pagenum = c_pagenum * 5;
	c_divisionc = Math.ceil(c_pagenum / 5);
	c_pagenum = c_pagenum - c_divisionc; 
}

function t_box_close(type)
{
	if(!type)
	{
		type = getCookie('t_box_id');
		if(!type)
		{
			type = "#fold_box";
		}
		jQuery(type).show();

		delCookie('t_open_set');
		setCookie('t_open_set', 'o');
	}
	else
	{
		jQuery(type).hide();
		delCookie('t_box_id');
		setCookie('t_box_id', type);

		delCookie('t_open_set');
		setCookie('t_open_set', 'c');
	}
}

function Popon(call)
{
	if(call == 'cart') pop_text = jQuery('.pop_t_cart');
	else pop_text = jQuery('.pop_t_wish');
	jQuery('.t_pop_hide').show();
	jQuery('.trolley_pop').show();
	pop_text.show();

	setTimeout(function (){ 
		jQuery('.t_pop_hide').hide();
		jQuery('.trolley_pop').hide();
		pop_text.hide();
   	}, 1000); 
}

