/* NHN (developers@xpressengine.com) */
jQuery(function($){

// get add/edit menu title
var $lang = $('#addField h2:first span');
xe.lang.add_menu  = $lang.eq(0).text();
xe.lang.edit_menu = $lang.eq(1).text();

var $grant_lang = $('#groupList select[name=menu_grant_default] option');
xe.lang.grant_to_all = $grant_lang.eq(0).text();
xe.lang.grant_to_login_user = $grant_lang.eq(1).text();
xe.lang.grant_to_group = $grant_lang.eq(2).text();
$lang.empty();

$('form.siteMap')
	.delegate('li:not(.placeholder)', 'dropped.st', function() {
		var $this = $(this), $pkey, $mkey, is_child;

		$pkey = $this.find('>input._parent_key');
		is_child = !!$this.parent('ul').parent('li').length;

		if(is_child) {
			$pkey.val($this.parent('ul').parent('li').find('>input._item_key').val());
		} else {
			$pkey.val('0');
		}
	});

	var editForm = $('#addField');
	var menuSrl = null;
	var menuForm = null;
	var menuUrl = null;

	$('a._edit').click(function(){
		resetEditForm();
		var field_srl = $(this).parent().prevAll('._item_key').val();

		var params = new Array();
		var response_tags = new Array('data');
		params['field_srl'] = field_srl;

		exec_xml("ncart","getNcartAdminFieldInfo", params, completeGetActList, response_tags);
	});

	function completeGetActList(obj)
	{
		var data = obj.data;
		var successReturnUrl = editForm.find('input[name=success_return_url]').val() + data.fieldset_srl;
		var columnTitle = $('<div />').html(data.column_title).text();

		editForm.find('.h2').text(xe.lang.edit_menu);
		editForm.find('input[name=fieldset_srl]').val(data.fieldset_srl);
		editForm.find('input[name=field_srl]').val(data.field_srl);
		editForm.find('input[name=column_name]').val(data.column_name);
		editForm.find('input[name=column_title]').val(data.column_title);
		editForm.find('select[name=column_type] option[value='+data.column_type+']').attr('selected','selected');
		editForm.find('textarea[name=default_value]').val(data.default_value);
		editForm.find('textarea[name=description]').val(data.description);
		if (data.required == 'Y') editForm.find('#radio_required').attr('checked','checked');
		else editForm.find('#radio_option').attr('checked','checked');
		if (data.is_head== 'Y') editForm.find('#radio_is_head').attr('checked','checked');
		else editForm.find('#radio_is_head_option').attr('checked','checked');
		editForm.find('input[name=success_return_url]').val(successReturnUrl);
		if(multiOption.is(':selected')){
			multiExample.slideDown(200);
		} else {
			multiExample.slideUp(200);
		}
	}

	$('a._delete').click(function() {
		if(confirmDelete())
		{
			fieldsetSrl = $(this).parents().prevAll('input[name=fieldset_srl]').val();
			fieldsetForm = $('#fieldset_'+fieldsetSrl);

			var field_srl = $(this).parent().prevAll('._item_key').val();
			fieldsetForm.find('input[name=field_srl]').val(field_srl);
			fieldsetForm.find('input[name=act]').val('procNcartAdminDeleteField');
			fieldsetForm.submit();
		}
	});

	var kindModuleLayer = $('#kindModule');
	var createModuleLayer = $('#createModule');
	var selectModuleLayer = $('#sModule_id');
	var insertUrlLayer = $('#insertUrl');
	var selectLayoutLayer = $('#selectLayout');

	function resetEditForm()
	{
		kindModuleLayer.hide();
		createModuleLayer.hide()
		selectModuleLayer.hide()
		insertUrlLayer.hide()
		selectLayoutLayer.hide()

		editForm.find('input[name=field_srl]').val('');
		editForm.find('input[name=column_name]').val('');
		editForm.find('input[name=column_title]').val('');
		editForm.find('input[name=column_type]').attr('checked', false);
		editForm.find('input[name=default_value]').val('');
		editForm.find('textarea[name=description]').val('');
		editForm.find('#radio_option').attr('checked','checked');
	}

	$('a._add').click(function()
	{
		var $this = $(this);

		resetEditForm();

		editForm.find('.h2').text(xe.lang.add_menu);
		editForm.find('input[name=fieldset_srl]').val($this.closest('form').find('input[name=fieldset_srl]:first').val());
		editForm.find('input[name=parent_srl]').val($this.parent().prevAll('input._item_key').val());
	});

	$('input._typeCheck').click(typeCheck);
	var checkedValue = null;

	function typeCheck()
	{
		var inputTypeCheck = $('input._typeCheck');
		for(var i=0; i<3; i++)
		{
			if(inputTypeCheck[i].checked)
			{
				checkedValue = inputTypeCheck[i].value;
				break;
			}
		}

		if(checkedValue == 'CREATE')
		{
			kindModuleLayer.show();
			createModuleLayer.show();
			selectModuleLayer.hide();
			insertUrlLayer.hide();
			selectLayoutLayer.show();
			changeLayoutList();
		}
		else if(checkedValue == 'SELECT')
		{
			kindModuleLayer.show();
			createModuleLayer.hide();
			selectModuleLayer.show();
			insertUrlLayer.hide();
			selectLayoutLayer.show();
			changeLayoutList();
		}
		// type is URL
		else
		{
			kindModuleLayer.hide();
			createModuleLayer.hide()
			selectModuleLayer.hide()
			insertUrlLayer.show()
			selectLayoutLayer.hide()
		}
	}

	$('#kModule').change(getModuleList).change();
	function getModuleList()
	{
		var params = new Array();
		var response_tags = ['error', 'message', 'module_list'];

		exec_xml('module','procModuleAdminGetList',params, completeGetModuleList, response_tags);
	}

	var layoutList = new Array();
	var moduleList = new Array();
	function completeGetModuleList(ret_obj)
	{
		var module = $('#kModule').val();
		if(module == 'WIDGET' || module == 'ARTICLE' || module == 'OUTSIDE') module = 'page';

		var htmlBuffer = "";
		if(ret_obj.module_list[module] != undefined)
		{
			var midList = ret_obj.module_list[module].list;
			var midListByCategory = new Object();
			for(x in midList)
			{
				if(!midList.hasOwnProperty(x)){
					continue;
				}
				var midObject = midList[x];

				if(!midListByCategory[midObject.module_category_srl])
				{
					midListByCategory[midObject.module_category_srl] = new Array();
				}
				midListByCategory[midObject.module_category_srl].push(midObject);
			}

			for(x in midListByCategory)
			{
				var midGroup = midListByCategory[x];
				htmlBuffer += '<optgroup label="'+x+'">'
				for(y in midGroup)
				{
					var midObject = midGroup[y];
					htmlBuffer += '<option value="'+midObject.mid+'"';
					if(menuUrl == midObject.mid) htmlBuffer += ' selected ';
					htmlBuffer += '>'+midObject.mid+'('+midObject.browser_title+')</option>';

					layoutList[midObject.mid] = midObject.layout_srl;
					moduleList[midObject.mid] = midObject.module_srl;
				}
				htmlBuffer += '</optgroup>'
			}
		}
		else htmlBuffer = '';

		selectModuleLayer.html(htmlBuffer);
		changeLayoutList();
	}

	$('#sModule_id').change(changeLayoutList).change();
	function changeLayoutList()
	{
		if(checkedValue == 'SELECT')
		{
			var mid = $('#sModule_id').val();
			$('#layoutSrl').val(layoutList[mid]);
			editForm.find('input[name=module_srl]').val(moduleList[mid]);
		}
		else if(checkedValue == 'CREATE')
		{
			$('#layoutSrl').val('0');
		}
	}

	function tgMapBtn(){
		$('.x .siteMap>ul:visible').next('.btnArea').slideDown(50);
		$('.x .siteMap>ul:hidden').next('.btnArea').slideUp(50);
	}
	tgMapBtn();
	$('a.tgMap').click(function() {
		var $this = $(this);
		var curToggleStatus = getCookie('sitemap_toggle_'+$this.attr('href'));
		var toggleStatus = curToggleStatus == 1 ? '0' : 1;

		$($this.attr('href')).slideToggle('fast');
		$this.closest('.siteMap').toggleClass('fold');
		setCookie('sitemap_toggle_'+$this.attr('href'), toggleStatus);
		setTimeout(function(){ tgMapBtn(); }, 250);
		
		return false;
	});
});

function confirmDelete()
{
	if(confirm(xe.lang.confirm_delete)) return true;
	return false;
}

/* 메뉴 권한 선택용 */
function doShowMenuGrantZone() {
	jQuery(".grant_default").each( function() {
	var id = "#zone_menu_grant";
	if(!jQuery(this).val()) jQuery(id).css("display","block");
	else jQuery(id).css("display","none");
	} );
}

