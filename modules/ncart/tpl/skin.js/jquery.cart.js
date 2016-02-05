(function($) {
 	var f_closed = true;
	var active_color = '#106A75';
	var inactive_color = 'black';

 	var methods = {
		init : function(options) {
			_this = this;
			var settings = {
			};
			return this.each(function() {
				if (options) {
					$.extend(settings, options);
				}
				$('#closeCart').click(function() { 
					if (f_closed) {
						$(_this).cart('open'); 
					} else {
						$(_this).cart('close'); 
					}
					return false;
				});

				$('#tabCart').click(function() { 
					$(_this).cart('open'); 
					$(this).removeClass('inactive').addClass('active');
					$('#tabFavorite').removeClass('active').addClass('inactive');
					$('#favoriteLayer').hide();
					$('#cartLayer').show();
					$('#cmdFavorite').hide();
					$('#cmdCart').show();
				});
				$('#tabFavorite').click(function() { 
					$(_this).cart('open'); 
					$(this).removeClass('inactive').addClass('active');
					$('#tabCart').removeClass('active').addClass('inactive');
					$('#cartLayer').hide();
					$('#favoriteLayer').show();
					$('#cmdCart').hide();
					$('#cmdFavorite').show();
				});
			});
		},
		open : function(options) {
			var header_height = jQuery('.header', this).css('height');
			$(this).animate({height:169}, 500);
			$('span','#closeCart').text(xe.lang.cmd_close);					
			f_closed = false;
		},
		close : function(options) {
			var header_height = jQuery('.header', this).css('height');
			$(this).animate({height:32}, 500);
			$('span','#closeCart').text(xe.lang.cmd_open);
			f_closed = true;
		},
		show_cart : function(options) {
			$('#tabCart').click();
		},
		show_favorites : function(options) {
			$('#tabFavorite').click();
		}
	};

 	$.fn.cart = function(method) {

		if (methods[method]) {
			return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
		} else if (typeof method === 'object' || !method) {
			return methods.init.apply(this, arguments);
		} else {
			$.error('Method ' + method + ' does not exist on jQuery.cart');
		}

	};
})(jQuery);
