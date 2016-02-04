
(function($) {
	jQuery(function($) {
                $('a.modalAnchor.updatePeriod').bind('before-open.mw', function(event){
					var cart_srl = $(this).attr('data-cart-srl');
					var period  = $(this).attr('data-period');

					if(period) $("#form_period").html(period);
					else $("#form_period").html('-');

					$("#form_cart_srl").val(cart_srl);
			
                });
	});


	$('.receipt').click(function() {
		var order_srl = $(this).attr('data-order-srl');
		var $_this = $(this);

		exec_xml(
			'epay',
			'getEpayReceipt',
			{order_srl:order_srl},
			function(ret){
				var tpl = ret.tpl.replace(/<enter>/g, '\n');
				$_this.html(tpl);
			},
			['error','message','tpl']
		);
	});

}) (jQuery);
