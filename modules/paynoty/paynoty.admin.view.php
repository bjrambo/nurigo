<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
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
	}

	/**
	 * @brief paynoty configuration list.
	 **/
	function dispPaynotyAdminList() 
	{
		$config_list = array();
		$args->page = Context::get('page');
		$output = executeQueryArray('paynoty.getConfigList', $args);
		if ($output->toBool() && $output->data) 
		{
			foreach ($output->data as $no => $val) 
			{
				$val->no = $no;
				$val->module_info = array();
				$config_list[$val->config_srl] = $val;
			}
		}
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);


		// module infos
		if (count($config_list) > 0) 
		{
			$config_srls = array_keys($config_list);
			$config_srls = join(',', $config_srls);

			$query_id = "paynoty.getModuleInfoByConfigSrl";
			$args->config_srls = $config_srls;
			$output = executeQueryArray($query_id, $args);
			if ($output->data) 
			{
				foreach ($output->data as $no => $val) 
				{
					$config_list[$val->config_srl]->module_info[] = $val;
				}
			}
		}
		Context::set('list', $config_list);


		$oPaynotyModel = getModel('paynoty');
		$config = $oPaynotyModel->getModuleConfig();
		Context::set('config',$config);

		$this->setTemplateFile('list');
	}

	/**
	 * @brief insert paynoty configuration info.
	 **/
	function dispPaynotyAdminInsert() 
	{
		$oEditorModel = getModel('editor');
		$config = $oEditorModel->getEditorConfig(0);
		// set editor options.
		$option->skin = $config->editor_skin;
		$option->content_style = $config->content_style;
		$option->content_font = $config->content_font;
		$option->content_font_size = $config->content_font_size;
		$option->colorset = $config->sel_editor_colorset;
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

		$config->content = Context::getLang('default_content');
		$config->mail_content = Context::getLang('default_mail_content');
		Context::set('config', $config);

		$this->setTemplateFile('insert');
	}

	/**
	 * @brief modify paynoty configuration.
	 **/
	function dispPaynotyAdminModify() 
	{
		$config_srl = Context::get('config_srl');
		// load paynoty info
		$args->config_srl = $config_srl;
		$output = executeQuery("paynoty.getConfig", $args);
		$config = $output->data;
		$extra_vars = unserialize($config->extra_vars);
		if ($extra_vars) 
		{
			foreach ($extra_vars as $key => $val) 
			{
				$config->{$key} = $val;
			}
		}

		// load module srls
		$args->config_srl = $config_srl;
		$output = executeQueryArray("paynoty.getModuleSrls", $args);
		if (!$output->toBool()) return $output;
		$module_srls = array();
		if ($output->toBool() && $output->data) 
		{
			foreach ($output->data as $no => $val) 
			{
				$module_srls[] = $val->module_srl;
			}
		}
		$config->module_srls = join(',', $module_srls);
		Context::set('config', $config);

		// editor
		$oEditorModel = getModel('editor');
		$config = $oEditorModel->getEditorConfig(0);
		// set options.
		$option->skin = $config->editor_skin;
		$option->content_style = $config->content_style;
		$option->content_font = $config->content_font;
		$option->content_font_size = $config->content_font_size;
		$option->colorset = $config->sel_editor_colorset;
		$option->allow_fileupload = true;
		$option->enable_default_component = true;
		$option->enable_component = true;
		$option->disable_html = false;
		$option->height = 200;
		$option->enable_autosave = false;
		$option->primary_key_name = 'config_srl';
		$option->content_key_name = 'mail_content';
		$editor = $oEditorModel->getEditor($config_srl, $option);
		Context::set('editor', $editor);

		$this->setTemplateFile('insert');
	}
}
?>
