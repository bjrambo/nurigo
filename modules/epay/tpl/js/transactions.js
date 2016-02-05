(function($) {
	jQuery(function($) {
		// view order info.
		$('a.modalAnchor.viewOrderInfo').bind('before-open.mw', function(event){
			var target_module = $(this).attr('data-target-module');
			var target_act = $(this).attr('data-target-act');
			var order_srl = $(this).attr('data-order-srl');

			// get order info. html codes.
			exec_xml(
				target_module
				, target_act
				, {order_srl : order_srl}
				, function(ret) {
					var tpl = ret.tpl.replace(/<enter>/g, '\n');
					$('#orderInfo').html(tpl); }
				, ['error','message','tpl']
			);
		});
	});
}) (jQuery);
