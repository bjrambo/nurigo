/*
 * Analytics Carousel
 * Author: Lucas Forchino
 * WebSite: http://www.jqueryload.com
 */
(function ($) {
	var methods = {
		init : function(options) {
			_obj = this;
			var settings = {width:5};
			return this.each(function() {
				if (options) {
					$.extend(settings, options);
				}
				$('.carrousel_left',_obj).click(function(){
					var $p = $(this).parent();
					var elementsCount=$('.carrousel_inner ul li',$p).size();
					var position = $p.attr('data-pos');
					position=parseInt(position, 10);
					if (position >= (elementsCount-settings.width)) return;
					elementsCount=elementsCount+3;
					if (position<elementsCount)
					{
						position=position+1;
						$('.carrousel_right',$p).removeClass('right_inactive');
						if (position==elementsCount){
							$(this).addClass('left_inactive');
						}
						var pos=position*120;
						$p.attr('data-pos',position);
						$('.carrousel_inner',$p).animate({'scrollLeft' : pos },'fast');
					}
					return false;
				});

				$('.carrousel_right',_obj).click(function(){
					var $p = $(this).parent();
					var position = $p.attr('data-pos');
					var elementsCount=$('.carrousel_inner ul li',$p).size();
					position=parseInt(position, 10);
					if (position>0)
					{
						$('.carrousel_left',$p).removeClass('left_inactive');
						position=position-1;
						if(position==0)
							{
								$(this).addClass('right_inactive');
							}
						var pos=position*120;
						$p.attr('data-pos',position);
						$('.carrousel_inner',$p).animate({'scrollLeft' : pos },'fast');
					}
					return false;
				});
			});
		}
	};

	$.fn.carrousel = function(method) {
		if (methods[method]) {
			return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
		} else if (typeof method === 'object' || !method) {
			return methods.init.apply(this, arguments);
		} else {
			$.error('Method ' + method + ' does not exist on jQuery.carrousel');
		}
	};
}) (jQuery);
