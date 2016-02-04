// jQuery Mega Menu Effects

// To apply one of those effects (replace "hover_fade" by any other effect) :
// $(function() {
//	 $("#menu").megaMenu('hover_fade');
// });

(function($) {


jQuery.fn.megaMenu = function(menu_effect)
{
	
	
	var menuItem = $('#menu li'),
	    menuItemChildren = ('.dropcontent, .fullwidth');

	function openCloseMegamenu() {
		$('#menu li').click(function() {
			$(this).toggleClass('active').siblings().removeClass('active');
			$(menuItemChildren).fadeOut(400, 0);
			$(this).children(menuItemChildren).fadeTo(400, 1);
		});
	}  

	$('.dropcontent').css('left', 'auto').hide();
	$('.fullwidth').css('left', '-1px').hide();
	
	
	switch( menu_effect )
	{

	case "hover_fade":
		$(menuItem).hover(function() {
			$(this).children(menuItemChildren).stop().delay(200).fadeTo(400, 1);
			}, function () { 
			$(this).children(menuItemChildren).stop().fadeTo(200, 0, function() {
			  $(this).hide(); 
		  });
		});
		break;

	case "hover_fadein":
		$(menuItem).hover(function() {
			$(this).children(menuItemChildren).stop().delay(200).fadeTo(400, 1);
			}, function () { 
			$(this).children(menuItemChildren).stop().fadeTo(0, 0).hide();
		});
		break;

	case "hover_slide":
		$(menuItem).hover(function() {
			$(this).children(menuItemChildren).delay(200).animate({height: 'toggle'}, 200);
			}, function () { 
			$(this).children(menuItemChildren).animate({height: 'toggle'}, 200);
		});
		break;

	case "hover_toggle":
		$(menuItem).hover(function() {
			$(this).children(menuItemChildren).delay(200).toggle(200);
			}, function () { 
			$(this).children(menuItemChildren).toggle(0);
		});
		break;

	case "click_fade":
		$(menuItem).click(function() {
			$(this).children(menuItemChildren).fadeIn(400);
			$(this).hover(function() {
			}, function(){	
				$(this).children(menuItemChildren).fadeOut(200);
			});
		});
		break;

	case "click_slide":
		$(menuItem).click(function() {
			$(this).children(menuItemChildren).slideDown(200);
			$(this).hover(function() {
			}, function(){	
				$(this).children(menuItemChildren).slideUp(200);
			});
		});
		break;

	case "click_toggle":
		$(menuItem).click(function() {
			$(this).children(menuItemChildren).show(200);
			$(this).hover(function() {
			}, function(){	
				$(this).children(menuItemChildren).hide(200);
			});
		});
		break;

	case "click_open_close":
		openCloseMegamenu();		
		break;

	case "click_open_close_slide":
		$(menuItem).click(function() {
			$(this).toggleClass('active').siblings().removeClass('active').children(menuItemChildren).slideUp(400);
			$(this).children(menuItemChildren).slideToggle(400);
		});
		break;

	case "click_open_close_toggle":
		$(menuItem).click(function() {
			$(this).toggleClass('active').siblings().removeClass('active').children(menuItemChildren).hide(400);
			$(this).children(menuItemChildren).toggle(400);
		});
		break;

	case "opened_first":
		$('#menu li:first-child > div').fadeTo(400, 1).parent().toggleClass('active');
		openCloseMegamenu();		
		break;

	case "opened_last":
		$('#menu li:last-child > div').fadeTo(400, 1).parent().toggleClass('active');
		openCloseMegamenu();		
		break;

	case "opened_second":
		$('#menu li:nth-child(2) > div').fadeTo(400, 1).parent().toggleClass('active');
		openCloseMegamenu();		
		break;

	case "opened_third":
		$('#menu li:nth-child(3) > div').fadeTo(400, 1).parent().toggleClass('active');
		openCloseMegamenu();		
		break;

	case "opened_fourth":
		$('#menu li:nth-child(4) > div').fadeTo(400, 1).parent().toggleClass('active');
		openCloseMegamenu();		
		break;

	case "opened_fifth":
		$('#menu li:nth-child(5) > div').fadeTo(400, 1).parent().toggleClass('active');
		openCloseMegamenu();		
		break;

	
	}

	
}

})(jQuery);
