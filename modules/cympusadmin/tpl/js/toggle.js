function delCookie(name, path) //cookie 삭제 
{
	var expireDate = new Date();
  
	//어제 날짜를 쿠키 소멸 날짜로 설정한다.
	expireDate.setDate( expireDate.getDate() - 1 );
	document.cookie = name + "= " + "; expires=" + expireDate.toGMTString() + "; path=/";
	
	document.cookie = name + "="
		+ ((path == null) ? "" : "; path=" + path)
		+ ""
		+ "; expires=Thu, 01-Jan-70 00:00:01 GMT";
}

jQuery(document).ready(function (){
	
	toggleview();
});

function toggleview()
{
	if(getCookie("toggle_master"))
	{
		toggle_master = getCookie("toggle_master");

		toggle_master = toggle_master.split(',');
		toggle_master_string = '';

		//alert(toggle_master);

		for(var i=0; i < toggle_master.length; i++)
		{
			active_id = "." + toggle_master[i];	
			id = "toggle_" + toggle_master[i];
			id = getCookie(id);

			if(id)
			{
				if(id == "show") jQuery(active_id).hide();
				if(id == "hide") jQuery(active_id).show();

				if(toggle_master[i] != '')
				{
					if(toggle_master_string == '') toggle_master_string = toggle_master[i];
					else toggle_master_string = toggle_master_string + "," + toggle_master[i];
				}
			}
		}
		delCookie("toggle_master");
		setCookie("toggle_master", toggle_master_string);
	}
}

function toggleset(id)
{
	base_id = id;
	prefix = "toggle_";
	active_id = "." + id;
	
	if(id)
	{
		id = prefix + id;
		if(getCookie(id))
		{
			if(getCookie(id) == "show")
			{
				jQuery(active_id).hide();
				value = "hide";
			}
			if(getCookie(id) == "hide")
			{
				jQuery(id).show();
				value = "show";
			}

			delCookie(id);
			setCookie(id, value);
		}
		else
		{
			setCookie(id,"show");
			jQuery(active_id).show();
		}
	}

	if(!getCookie("toggle_master"))
	{
		toggle_master = base_id;
		setCookie("toggle_master", toggle_master);
	}
	else
	{
		toggle_master = getCookie("toggle_master");

		replace_1 = toggle_master.replace(base_id+",",'');
		replace_2 = replace_1.replace(base_id,'');
		replace_3 = replace_2.split(',');
		replace_4 = replace_3 + ',' + base_id;

		delCookie("toggle_master");
		setCookie("toggle_master", replace_4);
	}

	toggleview();
}	

