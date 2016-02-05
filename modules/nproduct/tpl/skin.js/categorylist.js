jQuery(function($) {
	$('#current-category').click(function() {
		$('#siblings').toggle();
		$(this).toggleClass('rollbtn');
	});
});
