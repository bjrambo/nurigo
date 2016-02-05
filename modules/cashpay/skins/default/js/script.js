function completeCashPayment(ret_obj) {
	if (ret_obj['error']==-1) {
		alert(ret_obj['message']);
	}
	if (ret_obj['return_url']) {
		url = ret_obj['return_url'];
		location.href=url;
	}
}

function completeCashReviewOrder(ret_obj) {
	if (ret_obj['error']==0)
	{
		var tpl = ret_obj.tpl.replace(/<enter>/g, '\n');
		jQuery('#cashExtendForm').html(tpl);

		obj = document.getElementById('fo_cash');
		obj.target = 'chgchild';
		setTimeout("obj.submit();", 1000);
	}
}

function doCashPayment() {
	var input_name = document.getElementById('depositor_name');
	var depositor_name = opener.document.getElementById('depositor_name');
	if (!input_name.value.length)
	{
		alert('입금자명을 입력해 주세요');
		input_name.focus();
		return false;
	}
	depositor_name.value = input_name.value;
	procFilter(opener.document.getElementById('fo_cash'), opener.submit_cash_payment);
	window.close();
}

function cash_payment(join_form) {
	var child = window.open('', 'chgchild', 'width=560,height=563,status=yes,scrollbars=no,resizable=no,menubar=no');
	//window.open('', 'chgchild', 'width=400,height=280,status=yes,scrollbars=yes,resizable=yes,menubar=yes');

	if (join_form) {
		copy_form(join_form, 'fo_cash');
	}
	procFilter(document.getElementById('fo_cash'), submit_cash_review);
	child.focus();

	return false;
}
