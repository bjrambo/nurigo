function completeInsertPlugin(ret_obj) {
	alert(ret_obj['message']);
	location.replace( current_url.setQuery('act','dispEpayAdminUpdatePlugin').setQuery('plugin_srl',ret_obj['plugin_srl']) );
}

function completeInsertEpay(ret_obj) {
	alert(ret_obj['message']);
	location.replace( current_url.setQuery('act','dispEpayAdminInsertEpay').setQuery('module_srl',ret_obj['module_srl']) );
}
