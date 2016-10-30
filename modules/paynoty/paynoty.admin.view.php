<?php

/**
 * @class  paynotyAdminView
 * @author NURIGO(contact@nurigo.net)
 * @brief  paynotyAdminView
 */
class paynotyAdminView extends paynoty
{
	var $group_list;

	function init()
	{
		$oMemberModel = getModel('member');

		// group 목록 가져오기
		$this->group_list = $oMemberModel->getGroups();
		Context::set('group_list', $this->group_list);

		// 템플릿 설정
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile(lcfirst(str_replace('dispPaynotyAdmin', '', $this->act)));
	}



	/**
	 * @brief insert paynoty configuration info.
	 **/
	function dispPaynotyAdminConfig()
	{
		$oEditorModel = getModel('editor');
		$editor_config = $oEditorModel->getEditorConfig(0);
		// set editor options.
		$option = new stdClass();
		$option->skin = $editor_config->editor_skin;
		$option->content_style = $editor_config->content_style;
		$option->content_font = $editor_config->content_font;
		$option->content_font_size = $editor_config->content_font_size;
		$option->colorset = $editor_config->sel_editor_colorset;
		$option->allow_fileupload = true;
		$option->enable_default_component = true;
		$option->enable_component = true;
		$option->disable_html = false;
		$option->height = 200;
		$option->enable_autosave = false;
		$option->primary_key_name = 'noti_srl';
		$option->content_key_name = 'mail_content';

		$editor = $oEditorModel->getEditor(0, $option);
		Context::set('editor', $editor);

		$member_config = getModel('member')->getMemberConfig();
		$variable_name = array();
		foreach($member_config->signupForm as $item)
		{
			if($item->type == 'tel')
			{
				$variable_name[] = $item->name;
			}
		}
		debugPrint($variable_name);

		$config = getModel('paynoty')->getConfig();
		if(!$config)
		{
			$config->content = Context::getLang('default_content');
			$config->mail_content = Context::getLang('default_mail_content');
		}

		Context::set('config', $config);
		Context::set('variable_name', $variable_name);
	}

}
