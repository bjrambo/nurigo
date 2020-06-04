<?php

/**
 * @class  inipaystandardAdminView
 * @author CONORY (https://www.conory.com)
 * @brief The admin view class of the inipaystandard module
 */
class inipaystandardAdminView extends inipaystandard
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
		$module_srl = Context::get('module_srl');
		if(!$module_srl && $this->module_srl)
		{
			$module_srl = $this->module_srl;
			Context::set('module_srl', $module_srl);
		}

		$oModuleModel = getModel('module');
		if($module_srl)
		{
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
			if(!$module_info)
			{
				Context::set('module_srl', '');
				$this->act = 'list';
			}
			else
			{
				ModuleModel::syncModuleToSite($module_info);
				$this->module_info = $module_info;
				Context::set('module_info', $module_info);
			}
		}

		// get the module category list
		$module_category = $oModuleModel->getModuleCategories();
		Context::set('module_category', $module_category);

		$security = new Security();
		$security->encodeHTML('module_info.');
		$security->encodeHTML('module_category..');

		// setup template path
		$template_path = sprintf("%stpl/", $this->module_path);
		$this->setTemplatePath($template_path);

		Context::addJsFile($this->module_path . 'tpl/js/inipaystandard_admin.js');
	}

	/**
	 * @brief module list
	 */
	function dispInipaystandardAdminModuleList()
	{
		$args = new stdClass;
		$args->sort_index = "module_srl";
		$args->page = Context::get('page');
		$args->list_count = 20;
		$args->page_count = 10;
		$args->s_module_category_srl = Context::get('module_category_srl');

		$search_target = Context::get('search_target');
		$search_keyword = Context::get('search_keyword');

		switch($search_target)
		{
			case 'mid':
				$args->s_mid = $search_keyword;
				break;
			case 'browser_title':
				$args->s_browser_title = $search_keyword;
				break;
		}

		$output = executeQueryArray('inipaystandard.getModuleList', $args);
		ModuleModel::syncModuleToSite($output->data);

		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('module_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);

		$this->setTemplateFile('module_list');
	}

	/**
	 * @brief insert Module
	 */
	function dispInipaystandardAdminInsertModule()
	{
		// get the skins list
		$oModuleModel = getModel('module');
		$skin_list = $oModuleModel->getSkins($this->module_path);
		Context::set('skin_list', $skin_list);

		// get the layouts list
		$oLayoutModel = getModel('layout');
		$layout_list = $oLayoutModel->getLayoutList();
		Context::set('layout_list', $layout_list);

		$security = new Security();
		$security->encodeHTML('skin_list..title');
		$security->encodeHTML('layout_list..title', 'layout_list..layout');

		$this->setTemplateFile('insert_module');
	}

	function dispInipaystandardAdminCardPartCancle()
	{
		$args = Context::getRequestVars();
		$ca_rs = getController('inipaystandard')->doCanclePart($args);
		if($ca_rs->result == true){
			$this->add("result","ok");
		}else{
			$this->add("result","fail");
			$this->add("result_code",$ca_rs->result_desc->result_code);
			$this->add("result_msg",$ca_rs->result_desc->result_message);
		}
	}
}