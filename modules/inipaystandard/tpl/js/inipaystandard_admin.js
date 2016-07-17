/**
 * @file   tpl/js/inipaystandard_admin.js
 * @author CONORY (https://www.conory.com)
 * @brief  inipaystandard 모듈의 관리자용 javascript
 **/

jQuery(function($){
	$('a._moduleDelete').click(function(event){
		event.preventDefault();
		if (!confirm(xe.lang.confirm_delete)) return;
		
		var del_module_srl = $(event.target).data('module-srl');
		exec_xml(
			'inipaystandard',
			'procInipaystandardAdminDeleteModule',
			{'module_srl':del_module_srl},
			function(ret){location.reload();},
			['error','message']
		);
	});
});