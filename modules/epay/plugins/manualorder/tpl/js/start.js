jQuery(document).ready(function ($)
{
		var manorder_email = $("#manorder_email", opener.document).text();
		var manorder_nick = $("#manorder_nick", opener.document).text();

		$("#manorder_email").html('<span>' + manorder_email + '</span>');
		$("#manorder_nick").html('<span>' + manorder_nick + '</span>');
});

