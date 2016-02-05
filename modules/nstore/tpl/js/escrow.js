(function($) {
	var methods = {
		init : function(options) {
			var settings = {
			};
			return this.each(function() {
				if (options) {
					$.extend(settings, options);
				}
				var order_srl = $(this).attr('data-order-srl');
				exec_xml(
					'nstore',
					'getNstoreAdminEscrowInfo',
					{order_srl:order_srl},
					function(ret){
						if (ret.data) {
							if (ret.data['deny_order']=='N') {
								$('#escrow_'+ret.data['order_srl']).attr('class','nuribtn').html('<span>수정</span>');
								if (ret.data['result_code'] != '00') $('#escrow_'+ret.data['order_srl']).addClass('red');
							} else {
								$('#escrow_'+ret.data['order_srl']).attr('class','nuribtn').html('<span>거절</span>').click(function() { window.open(current_url.setQuery('act','dispNstoreAdminEscrowDenyConfirm').setQuery('order_srl',order_srl), 'popup', 'left=50, top=20, width=600, scrollbars=yes, height=400, toolbars=no'); });
								if (ret.data['denyconfirm_code'] != '00') $('#escrow_'+ret.data['order_srl'])
							}
						} else {
							$('#escrow_'+order_srl).attr('class','nuribtn magenta').html('<span>등록</span>');
						}
					},
					['error','message','data']
				);
							
			});
		}
	};

        $.fn.escrow = function(method) {

                if (methods[method]) {
                        return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
                } else if (typeof method === 'object' || !method) {
                        return methods.init.apply(this, arguments);
                } else {
                        $.error('Method ' + method + ' does not exist on jQuery.cart');
                }

        };
})(jQuery);
