function completeXpaySubmitPayment(ret_obj) {
	if (ret_obj['error']==-1) {
		alert(ret_obj['message']);
	}
	var url = current_url.setQuery('act','dispStoreOrderComplete').setQuery('order_srl', ret_obj['order_srl']);
	if (ret_obj['return_url']) {
		url = ret_obj['return_url'];
	}
	location.href=url;
}

function completeXpayReviewOrder(ret_obj) {
	frm = document.getElementById('LGD_PAYINFO');

	var tpl = ret_obj.tpl.replace(/<enter>/g, '\n');
	jQuery('#xpayExtends').html(tpl);

	jQuery(frm).remove("input[name='module']").remove("input[name='act']").remove("input[name='mid']");

	ret = xpay_check(frm, cst_platform);

	if (ret=="00"){     //ActiveX 로딩 성공
		var LGD_RESPCODE        = dpop.getData('LGD_RESPCODE');       //결과코드
		var LGD_RESPMSG         = dpop.getData('LGD_RESPMSG');        //결과메세지

		if( "0000" == LGD_RESPCODE ) { //인증성공
			var LGD_PAYKEY      = dpop.getData('LGD_PAYKEY');         //LG유플러스 인증KEY
			var msg = "인증결과 : " + LGD_RESPMSG + "\n";
			msg += "LGD_PAYKEY : " + LGD_PAYKEY +"\n\n";
			document.getElementById('LGD_PAYKEY').value = LGD_PAYKEY;
			//alert(msg);
			return procFilter(frm, submit_xpay_payment);
		} else { //인증실패
			alert("인증이 실패하였습니다. " + LGD_RESPMSG);
		    /*
		     * 인증실패 화면 처리
		     */
		}
	} else {
		alert("LG U+ 전자결제를 위한 ActiveX Control이  설치되지 않았습니다.");
        /*
         * 인증실패 화면 처리
         */
	}
}

function doPay_ActiveX(){
	copy_form(join_form, 'LGD_PAYINFO');
	procFilter(document.getElementById('LGD_PAYINFO'), submit_xpay_review);
}

(function($) {
	jQuery(function($) {
		$('input[name=payment_method]','#LGD_PAYINFO').click(function() {
			var paymethod = $(this).val();
			switch(paymethod) {
				case 'CC':
					$('input[name=LGD_CUSTOM_FIRSTPAY]').val('SC0010');
					break;
				case 'IB':
					$('input[name=LGD_CUSTOM_FIRSTPAY]').val('SC0030');
					break;
				case 'VA':
					$('input[name=LGD_CUSTOM_FIRSTPAY]').val('SC0040');
					break;
				case 'MP':
					$('input[name=LGD_CUSTOM_FIRSTPAY]').val('SC0060');
					break;
			}

			var method = $(this).val();
			$('.payment_info','#LGD_PAYINFO').hide();
			$('#pm_'+method).show();
		});
	});
}) (jQuery);
