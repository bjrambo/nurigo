jQuery(function($){

	$.fn.xeMenu = function(){
		this
			.attr('role', 'navigation') // WAI-ARIA role
			.find('>.nav>li')
				.attr('role', 'menuitem') // WAI-ARIA role
				.find('>ul').css('height','0').end()
				.filter(':has(>ul)')
					.attr('aria-haspopup', 'true') // WAI-ARIA
				.end()
			.end()
			.find('>.nav')
			.mouseover(function(){
				$(this)
					.parent('.gnb').addClass('active').end()
					.find('>li>ul').css('height','auto').end()
			})
			.mouseleave(function(){
				$(this)
					.parent('.gnb').removeClass('active').end()
					.find('>li>ul').css('height','0').end()
			})
			.focusout(function(){
				var $this = $(this);
				setTimeout(function(){
					if(!$this.find(':focus').length) {
						$this.mouseleave();
					}
				}, 1);
			})
			.delegate('a', {
				focus : function(){
					$(this).mouseover();
				}
			});
	};
	
	$('div.gnb').xeMenu();
});

jQuery(document).ready(function(){

	if(jQuery("#sub_check").val())
	{
		jQuery(".sub_menu").addClass('sub_check');	
	}	

});


