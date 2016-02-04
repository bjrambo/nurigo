<?php
	if(!defined('__XE__')) exit();

	if($called_position == 'before_module_proc' && Context::getResponseMethod()=='HTML' && $addon_info->r_ncart_mid && !Context::get('hide_trolley'))
	{
		require_once('./addons/trolley/trolley.lib.php');

		$addon_info->r_trolley_id = 'trolley';
		
		$logged_info = Context::get('logged_info');

		Context::set('logged_info', $logged_info);

		if($this->module == 'nproduct')
		{
			// Cookie 체크 밑 업데이트.
			checkRecentItems($module_name, $addon_info, $this->module);
		}

		// Recent Item Set View
		setRecentView($addon_info);

		// Document Event Set
		setDocumentEvent($addon_info);

		// BUtton Event Set
		setButtonEvent($addon_info);

	}

	if($called_position == 'before_display_content' && Context::getResponseMethod()=='HTML'&& $addon_info->r_ncart_mid && !Context::get('hide_trolley'))
	{
		$addon_info->r_trolley_id = 'trolley';

		Context::set('recent_pass', '');
		// 템플릿 파일을 지정
		$tpl_file = 'trolley';

		$oTemplate = &TemplateHandler::getInstance();
		$output = $output.$oTemplate->compile('./addons/trolley/tpl', $tpl_file);
	}
?>
