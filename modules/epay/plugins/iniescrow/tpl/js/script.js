function completeIniescrowSubmitPayment(ret_obj) {
	if (ret_obj['error']==-1) {
		alert(ret_obj['message']);
	}
	var url = current_url.setQuery('act','dispStoreOrderComplete').setQuery('order_srl', ret_obj['order_srl']);
	if (ret_obj['return_url']) {
		url = ret_obj['return_url'];
	}
	location.href=url;
}

function iniescrow_enable_click()
{
	document.fo_iniescrow.clickcontrol.value = "enable"
}

function iniescrow_disable_click()
{
	document.fo_iniescrow.clickcontrol.value = "disable"
}

function iniescrow_focus_control()
{
	if(document.fo_iniescrow.clickcontrol.value == "disable")
		openwin.focus();
}

function completeIniescrowReviewOrder(ret_obj) {
	// MakePayMessage()를 호출함으로써 플러그인이 화면에 나타나며, Hidden Field
	// 에 값들이 채워지게 됩니다. 일반적인 경우, 플러그인은 결제처리를 직접하는 것이
	// 아니라, 중요한 정보를 암호화 하여 Hidden Field의 값들을 채우고 종료하며,
	// 다음 페이지인 INIsecureresult.php로 데이터가 포스트 되어 결제 처리됨을 유의하시기 바랍니다.
	frm = document.getElementById('fo_iniescrow');

	if(frm.clickcontrol.value == "enable") {
		
		if(frm.goodname.value == "")  // 필수항목 체크 (상품명, 상품가격, 구매자명, 구매자 이메일주소, 구매자 전화번호)
		{
			alert("상품명이 빠졌습니다. 필수항목입니다.");
			return false;
		}
		else if(frm.buyername.value == "")
		{
			alert("구매자명이 빠졌습니다. 필수항목입니다.");
			return false;
		} 
		else if(frm.buyeremail.value == "")
		{
			alert("구매자 이메일주소가 빠졌습니다. 필수항목입니다.");
			return false;
		}
		else if(frm.buyertel.value == "")
		{
			alert("구매자 전화번호가 빠졌습니다. 필수항목입니다.");
			return false;
		}
		else if( (navigator.userAgent.indexOf("MSIE") >= 0 || navigator.appName == 'Microsoft Internet Explorer') && (document.fo_iniescrow == null) )  // 플러그인 설치유무 체크
		{
			alert("\n이니페이 플러그인 128이 설치되지 않았습니다. \n\n안전한 결제를 위하여 이니페이 플러그인 128의 설치가 필요합니다. \n\n다시 설치하시려면 Ctrl + F5키를 누르시거나 메뉴의 [보기/새로고침]을 선택하여 주십시오.");
			return false;
		}
		else
		{
			var tpl = ret_obj.tpl.replace(/<enter>/g, '\n');
			jQuery('#iniescrowForm').html(tpl);

			/******
			 * 플러그인이 참조하는 각종 결제옵션을 이곳에서 수행할 수 있습니다.
			 * (자바스크립트를 이용한 동적 옵션처리)
			 */
			
						 
			if (MakePayMessage(frm))
			{
				iniescrow_disable_click();
				//openwin = window.open("childwin.html","childwin","width=299,height=149");		
				return procFilter(frm, submit_iniescrow_payment);
			}
			else
			{
				if (IsPluginModule()) // plugin 타입 체크
				{
					alert("결제를 취소하셨습니다.");
				}
				return false;
			}
		}
	}
	else
	{
		alert('결제진행 상태입니다.');
		return false;
	}
}

function pay(frm) {
	procFilter(frm, submit_iniescrow_review);
	return false;
}

function iniescrow_submit(join_form) {
	var paymethod = jQuery('input[name=payment_method]:checked','#fo_iniescrow').val();
	switch(paymethod) {
		case 'CC':
			jQuery('input[name=gopaymethod]','#fo_iniescrow').val('Card');
			break;
		case 'IB':
			jQuery('input[name=gopaymethod]','#fo_iniescrow').val('DirectBank');
			break;
		case 'VA':
			jQuery('input[name=gopaymethod]','#fo_iniescrow').val('VBank');
			break;
		case 'MP':
			jQuery('input[name=gopaymethod]','#fo_iniescrow').val('HPP');
			break;
	}

	if (join_form) {
		copy_form(join_form, 'fo_iniescrow');
	}
	procFilter(document.getElementById('fo_iniescrow'), submit_iniescrow_review);
	return false;
}

(function($) {
	jQuery(function($) {
		iniescrow_enable_click();
		$('input[name=payment_method]','#fo_iniescrow').click(function() {
			var paymethod = $(this).val();
			$('.payment_info','#fo_iniescrow').hide();
			$('#iniescrow_pm_'+paymethod).show();
		});
	});
}) (jQuery);
