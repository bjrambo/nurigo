
function completeSubmitPaypal(ret_obj)
{
	if (ret_obj['error'] < 0) alert(ret_obj['message']);
	if (ret_obj['return_url']) location.href = ret_obj['return_url'];
}

function paypal_submit() 
{
        if (join_form) {
		copy_form(join_form, 'fo_paypal');
        }
        procFilter(document.getElementById('fo_paypal'), submit_paypal);
        return false;
}
