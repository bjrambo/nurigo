function delete_content(file_srl) {
        if (!confirm('정말 삭제하시겠습니까?')) return;
		if(file_srl) 
		{
			var params = new Array();
			params['file_srl'] = file_srl;
			exec_xml('nstore_digital_contents', 'procNstore_digital_contentsAdminDeleteContent', params, function(ret_obj) { alert(ret_obj['message']); location.href = current_url; });
		}
}

jQuery(function($) {
        var editForm = $('#editForm');

        function resetEditForm() {
                editForm.find('input[name=file_srl]').val('');
        }

        $('a._edit').click(function() {

			var file_srl = $(this).attr('id').replace(/record_/i,'');
			var file_id = '#file_' + file_srl;

			$("#abcd").html($(file_id).html());
			editForm.find('input[name=file_srl]').val(file_srl);

			/*
                var file_srl = $(this).attr('id').replace(/record_/i,'');
                exec_xml(
                        'nstore_digital_contents',
                        'getNstore_digital_contentsAdminContentInfo',
                        {file_srl:file_srl},
                        function(ret){
                                editForm.find('input[name=file_srl]').val(ret.data.file_srl);
						},
                        ['error','message','data']
                );
			*/
        });
        $('a._add').click(function() {
                resetEditForm();
        });
});


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


function popup_modal(url, title, width, height, options) {
	if(getCookie('n_find_url') == 'c')
	{
		jQuery("#nodal").hide();
		delCookie('n_find_url');
		setCookie('n_find_url','o');
		jQuery(".n_find_url").text("찾아보기");
	}	
	else
	{
		var $iframe = jQuery('<iframe src="' + url + '" frameborder="0" style="border:0 none; width:100%; height:100%; padding:0; margin:0; background:transparent;"></iframe>');
		$iframe.ready(function() {
			setTimeout(function() { 
				jQuery('#nodal').html($iframe);
				jQuery("#nodal").show();
				jQuery(".n_find_url").text("닫기");
				setCookie('n_find_url','c');
		   	}, 200);
		});
		
	}
}

function del_cookie()
{
	delCookie('n_find_url');
}

