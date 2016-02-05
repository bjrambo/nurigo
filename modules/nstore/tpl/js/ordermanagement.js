(function($) {
	jQuery(function($) {
		// view order info.
		$('a.modalAnchor.viewOrderInfo').bind('before-open.mw', function(event){
			// get cart_srl
			var order_srl = $(this).attr('data-order-srl');
			var mid = $(this).attr('data-mid');

			// get enrollment form
			exec_xml(
				'ncart'
				, 'getNcartAdminOrderDetails'
				, {order_srl : order_srl, 'mid': mid}
				, function(ret) {
					var tpl = ret.tpl.replace(/<enter>/g, '\n');
					$('#orderInfo').html(tpl); }
				, ['error','message','tpl']
			);
		});

		$('a.modalAnchor.deleteOrders').bind('before-open.mw', function(event){
			// get checked items.
			var a = [];
			var $checked_list = jQuery('input[name=cart\\[\\]]:checked');
			$checked_list.each(function() { a.push(jQuery(this).val()); });
			var order_srl = a.join(',');

			// get delete form.
			exec_xml(
					'nstore',
					'getNstoreAdminDeleteOrders',
					{order_srl:order_srl},
					function(ret){
							var tpl = ret.tpl.replace(/<enter>/g, '\n');
							$('#deleteForm').html(tpl);
					},
					['error','message','tpl']
			);
		});
	});
}) (jQuery);
