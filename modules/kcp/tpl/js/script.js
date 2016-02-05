function completeSubmitPayment(ret_obj) {
	if (ret_obj['error']==-1) {
		alert(ret_obj['message']);
	}
	if (ret_obj['return_url']) {
		url = ret_obj['return_url'];
		location.href=url;
	}
}

/* Payplus Plug-in 실행 */
function  jsf__pay( form )
{
	var RetVal = false;

/*
	if( document.Payplus.object == null )
	{
		openwin = window.open( "chk_plugin.html", "chk_plugin", "width=420, height=100, top=300, left=300" );
	}
*/

	/* Payplus Plugin 실행 */
	if ( MakePayMessage( form ) == true )
	{
		//return procFilter(form, submit_payment);
		//openwin = window.open( "proc_win.html", "proc_win", "width=449, height=209, top=300, left=300" );
		RetVal = true ;
	}
	else
	{
		/*  res_cd와 res_msg변수에 해당 오류코드와 오류메시지가 설정됩니다.
		    ex) 고객이 Payplus Plugin에서 취소 버튼 클릭시 res_cd=3001, res_msg=사용자 취소
		    값이 설정됩니다.
		*/
		res_cd  = document.fo_payplus.res_cd.value ;
		res_msg = document.fo_payplus.res_msg.value ;
		//alert ( "Payplus Plug-in 실행 결과\n" + "res_cd = " + res_cd + "|" + "res_msg=" + res_msg ) ;
	}

	return RetVal ;
}

	// Payplus Plug-in 설치 안내 
function init_pay_button()
{
    if( document.Payplus.object == null )
	document.getElementById("display_setup_message").style.display = "block" ;
    else
	document.getElementById("display_pay_button").style.display = "block" ;
}

/* 주문번호 생성 예제 */
function init_orderid()
{
    var today = new Date();
    var year  = today.getFullYear();
    var month = today.getMonth() + 1;
    var date  = today.getDate();
    var time  = today.getTime();

    if(parseInt(month) < 10) {
	month = "0" + month;
    }

    if(parseInt(date) < 10) {
	date = "0" + date;
    }

    var order_idxx = "TEST" + year + "" + month + "" + date + "" + time;

    document.fo_payplus.ordr_idxx.value = order_idxx;
}

/* onLoad 이벤트 시 Payplus Plug-in이 실행되도록 구성하시려면 다음의 구문을 onLoad 이벤트에 넣어주시기 바랍니다. */
function onload_pay()
{
     if( jsf__pay(document.fo_payplus) )
	document.fo_payplus.submit();
}

function completeReviewOrder(ret_obj) {
	var tpl = ret_obj.tpl.replace(/<enter>/g, '\n');
	jQuery('#payplusForm').html(tpl);

	jsf__pay(document.fo_payplus);
}

function payplus_pay() {
	if( jsf__pay(document.fo_payplus) ) document.fo_payplus.submit();
	return false;
}

(function($) {
	jQuery(function($) {
		$('input[name=payment_method]','#fo_payplus').click(function() {
			var paymethod = $(this).val();
			$('.payment_info','#fo_payplus').hide();
			$('#payplus_pm_'+paymethod).show();
		});

	});
}) (jQuery);
