function completeMakePayment(ret_obj) {
	if (ret_obj['error']==-1) {
		alert(ret_obj['message']);
	}
	if (ret_obj['return_url']) {
		url = ret_obj['return_url'];
		location.href=url;
	}
}

function completeMakeReviewOrder(ret_obj) {
	if (ret_obj['error']==0)
	{
		var tpl = ret_obj.tpl.replace(/<enter>/g, '\n');
		jQuery('#manualorderExtendForm').html(tpl);

		obj = document.getElementById('fo_manualorder');
		obj.target = 'chgchild';
		setTimeout("obj.submit();", 1000);
	}
}

function doMakePayment() {
	procFilter(opener.document.getElementById('fo_manualorder'), opener.submit_manualorder_payment);
	window.close();
}

function manualorder_payment(join_form) {
	window.open('', 'chgchild', 'width=560,height=563,status=yes,scrollbars=no,resizable=no,menubar=no');
	//window.open('', 'chgchild', 'width=400,height=280,status=yes,scrollbars=yes,resizable=yes,menubar=yes');

	if (join_form) {
		copy_form(join_form, 'fo_manualorder');
	}
	procFilter(document.getElementById('fo_manualorder'), submit_manualorder_review);

	return false;
}

