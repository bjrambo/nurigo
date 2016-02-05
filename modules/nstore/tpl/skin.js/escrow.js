(function($) {
	var methods = {
		init : function(options) {
			var settings = {
			};
			return this.each(function() {
				if (options) {
					$.extend(settings, options);
				}
				var _this = this;
				var order_srl = $(this).attr('data-order-srl');
				exec_xml(
					'nstore',
					'getNstoreEscrowInfo',
					{order_srl:order_srl},
					function(ret){
						if (ret.data) {
							if (ret.data['confirm_code'] != '00') {
								$('#escrow_'+ret.data['order_srl']).attr('class','nuribtn yellow').html('<span>구매결정</span>');
							}
							if (ret.data['confirm_code'] == '00') {
								$('#escrow_'+ret.data['order_srl']).attr('class','nuribtn').html('<span>구매완료</span>');
							}

/*
							$('#escrow_'+ret.data['order_srl']).attr('class','nuribtn').html('<span>구매완료</span>');
							if (ret.data['result_code'] != '00') $('#escrow_'+ret.data['order_srl']).addClass('red');
*/
						} else {
							// no data
							$(_this).remove();
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
