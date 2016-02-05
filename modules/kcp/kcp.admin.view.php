<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  kcpAdminView
 * @author NURIGO(contact@nurigo.net)
 * @brief  kcpAdminView
 */ 
class kcpAdminView extends kcp
{
	/**
	 * @brief initialize view class
	 */
	function init() 
	{
		$oModuleModel = &getModel('module');

		// use $this->module_srl if the module_srl is not passed
		$module_srl = Context::get('module_srl');
		if(!$module_srl && $this->module_srl)
		{
			$module_srl = $this->module_srl;
			Context::set('module_srl', $module_srl);
		}

		// prepare $module_info if the module_srl is passed.
		if($module_srl) 
		{
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
			if(!$module_info) 
			{
				Context::set('module_srl','');
				$this->act = 'list';
			} else {
				ModuleModel::syncModuleToSite($module_info);
				$this->module_info = $module_info;
				Context::set('module_info',$module_info);
			}
		}

		// set template path
		$tpl_path = $this->module_path.'tpl';
		$this->setTemplatePath($tpl_path);
		Context::set('tpl_path', $tpl_path);
	}

	/**
	 * @brief print account input form
	 */
	function dispKcpAdminInsert() 
	{
		$this->setTemplateFile('insert');
	}

	/**
	 * @brief print module instance list
	 */
	function dispKcpAdminModInstList() 
	{
		// get the module instance list
		$args->sort_index = "module_srl";
		$args->page = Context::get('page');
		$args->list_count = 20;
		$args->page_count = 10;
		$args->s_module_category_srl = Context::get('module_category_srl');
		$output = executeQueryArray('kcp.getModInstList', $args);
		if(!$output->toBool()) return $output;
		$list = $output->data;
		if(!is_array($list)) $list = array();
		Context::set('list', $list);

		// get the module categories
		$oModuleModel = &getModel('module');
		$module_category = $oModuleModel->getModuleCategories();
		Context::set('module_category', $module_category);

		$this->setTemplateFile('modinstlist');
	}

	/**
	 * @brief print module instance creation form
	 */
	function dispKcpAdminInsertModInst() 
	{
		// get the skin list
		$oModuleModel = &getModel('module');
		$skin_list = $oModuleModel->getSkins($this->module_path);
		Context::set('skin_list',$skin_list);

		// get the mobile skin list
		$mskin_list = $oModuleModel->getSkins($this->module_path, "m.skins");
		Context::set('mskin_list', $mskin_list);

		// get the layout list
		$oLayoutModel = &getModel('layout');
		$layout_list = $oLayoutModel->getLayoutList();
		Context::set('layout_list', $layout_list);

		// get the mobile layout list
		$mobile_layout_list = $oLayoutModel->getLayoutList(0,"M");
		Context::set('mlayout_list', $mobile_layout_list);

		// get the module categories
		$module_category = $oModuleModel->getModuleCategories();
		Context::set('module_category', $module_category);
		$this->setTemplateFile('insertmodinst');
	}

	/**
	 * @brief print PC skin info
	 **/
	function dispKcpAdminSkinInfo() 
	{
		$oModuleAdminModel = &getAdminModel('module');
		$skin_content = $oModuleAdminModel->getModuleSkinHTML($this->module_info->module_srl);
		Context::set('skin_content', $skin_content);
		$this->setTemplateFile('skininfo');
	}
}
/* End of file kcp.admin.view.php */
/* Location: ./modules/kcp/kcp.admin.view.php */
