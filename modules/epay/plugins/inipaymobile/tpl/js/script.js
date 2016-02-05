function completeSubmitPayment(ret_obj) {
	if (ret_obj['error']==-1) {
		alert(ret_obj['message']);
	}
	var url = current_url.setQuery('act','dispStoreOrderComplete').setQuery('order_srl', ret_obj['order_srl']);
	if (ret_obj['return_url']) {
		url = ret_obj['return_url'];
	}
	location.href=url;
}

function completeReviewOrder(ret_obj) {
	var width = 330;
	var height = 480;
	var xpos = (screen.width - width) / 2;
	var ypos = (screen.width - height) / 2;
	var position = "top=" + ypos + ",left=" + xpos;
	var features = position + ", width=320, height=440";
	var payment_method = jQuery('input[name=payment_method]:checked','#fo_inipaymobile').val();
	var paymethod = 'wcard';
	switch(payment_method) {
		case 'CC':
			paymethod = 'wcard';
			break;
		case 'IB':
			paymethod = 'bank';
			break;
		case 'VA':
			paymethod = 'vbank';
			break;
		case 'MP':
			paymethod = 'mobile';
			break;
	}
	var order_form = document.getElementById('fo_inipaymobile');

	var tpl = ret_obj.tpl.replace(/<enter>/g, '\n');
	jQuery('#inipaymobileForm').html(tpl);

	/*
	var wallet = window.open("", "BTPG_WALLET", features);
	
	if (wallet == null) 
	{
		if ((webbrowser.indexOf("Windows NT 5.1")!=-1) && (webbrowser.indexOf("SV1")!=-1)) 
		{    // Windows XP Service Pack 2
			alert("팝업이 차단되었습니다. 브라우저의 상단 노란색 [알림 표시줄]을 클릭하신 후 팝업창 허용을 선택하여 주세요.");
		} 
		else 
		{
			alert("팝업이 차단되었습니다.");
		}
		return false;
	}
	*/


/*
       	param = "";
       	param = param + "mid=" + order_form.P_MID.value + "&";
       	param = param + "oid=" + order_form.P_OID.value + "&";
       	param = param + "price=" + order_form.P_AMT.value + "&";
       	param = param + "goods=" + order_form.P_GOODS.value + "&";
       	param = param + "uname=" + order_form.P_UNAME.value + "&";
       	param = param + "mname=" + order_form.P_MNAME.value + "&";
       	param = param + "mobile=000-111-2222" + order_form.P_MOBILE.value + "&";
       	param = param + "paymethod=" + paymethod + "&";
       	param = param + "noteurl=" + order_form.P_NOTI_URL.value + "&";
       	param = param + "ctype=1" + "&";
       	param = param + "returl=" + "&";
       	param = param + "email=" + order_form.P_EMAIL.value;
	var ret = location.href="INIpayMobile://" + encodeURI(param);
	return;
*/
	
	//order_form.target = "BTPG_WALLET";
	order_form.action = "https://mobile.inicis.com/smart/" + paymethod + "/";
	order_form.submit();
}

function inipaymobile_submit(join_form) {
	var width = 330;
	var height = 480;
	var xpos = (screen.width - width) / 2;
	var ypos = (screen.width - height) / 2;
	var position = "top=" + ypos + ",left=" + xpos;
	var features = position + ", width=320, height=440";
	//var wallet = window.open("", "BTPG_WALLET", features);
	//wallet.focus();

	if (join_form) {
		copy_form(join_form, 'fo_inipaymobile');
	}
	procFilter(document.getElementById('fo_inipaymobile'), inipaymobile_submit_review);
	return false;
}

(function($) {
	jQuery(function($) {
		$('input[name=payment_method]','#fo_inipaymobile').click(function() {
			var paymethod = $(this).val();
			$('.payment_info','#fo_inipaymobile').hide();
			$('#pm_'+paymethod).show();
		});
	});
}) (jQuery);
