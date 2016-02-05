function completeZeropayPayment(ret_obj) {
	if (ret_obj['error']==-1) {
		alert(ret_obj['message']);
	}
	if (ret_obj['return_url']) {
		url = ret_obj['return_url'];
		location.href=url;
	}
}

function completeZeropayReviewOrder(ret_obj) {
	if (ret_obj['error']==0)
	{
		var tpl = ret_obj.tpl.replace(/<enter>/g, '\n');
		jQuery('#cashExtendForm').html(tpl);
		procFilter(document.getElementById('fo_cash'), submit_zeropay_payment);
	}
}


function zeropay_payment(join_form) {
	if (join_form) {
		copy_form(join_form, 'fo_cash');
	}
	procFilter(document.getElementById('fo_cash'), submit_zeropay_review);
	return false;
}
