<?php

/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  cashpayAdminView
 * @author NURIGO(contact@nurigo.net)
 * @brief  cashpayAdminView
 */
class cashpayAdminView extends cashpay
{
	/**
	 * @brief initialize view class
	 */
	function init()
	{
		$oModuleModel = getModel('module');

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

		// set template path
		$tpl_path = $this->module_path . 'tpl';
		$this->setTemplatePath($tpl_path);
		Context::set('tpl_path', $tpl_path);
	}

	/**
	 * @brief print account input form
	 */
	function dispCashpayAdminInsert()
	{
		$this->setTemplateFile('insert');
	}

	/**
	 * @brief print module instance list
	 */
	function dispCashpayAdminModInstList()
	{
		// get the module instance list
		$args = new stdClass();
		$args->sort_index = "module_srl";
		$args->page = Context::get('page');
		$args->list_count = 20;
		$args->page_count = 10;
		$args->s_module_category_srl = Context::get('module_category_srl');
		$output = executeQueryArray('cashpay.getModInstList', $args);
		if(!$output->toBool())
		{
			return $output;
		}
		$list = $output->data;
		if(!is_array($list))
		{
			$list = array();
		}
		Context::set('list', $list);

		// get the module categories
		$oModuleModel = getModel('module');
		$module_category = $oModuleModel->getModuleCategories();
		Context::set('module_category', $module_category);

		$this->setTemplateFile('modinstlist');
	}

	/**
	 * @brief print module instance creation form
	 */
	function dispCashpayAdminInsertModInst()
	{
		// get the skin list
		$oModuleModel = getModel('module');
		$skin_list = $oModuleModel->getSkins($this->module_path);
		Context::set('skin_list', $skin_list);

		// get the mobile skin list
		$mskin_list = $oModuleModel->getSkins($this->module_path, "m.skins");
		Context::set('mskin_list', $mskin_list);

		// get the layout list
		$oLayoutModel = getModel('layout');
		$layout_list = $oLayoutModel->getLayoutList();
		Context::set('layout_list', $layout_list);

		// get the mobile layout list
		$mobile_layout_list = $oLayoutModel->getLayoutList(0, "M");
		Context::set('mlayout_list', $mobile_layout_list);

		// get the module categories
		$module_category = $oModuleModel->getModuleCategories();
		Context::set('module_category', $module_category);
		$this->setTemplateFile('insertmodinst');
	}

	/**
	 * @brief print PC skin info
	 **/
	function dispCashpayAdminSkinInfo()
	{
		$oModuleAdminModel = getAdminModel('module');
		$skin_content = $oModuleAdminModel->getModuleSkinHTML($this->module_info->module_srl);
		Context::set('skin_content', $skin_content);
		$this->setTemplateFile('skininfo');
	}

	/**
	 * @brief print mobile skin info
	 **/
	function dispCashpayAdminMobileSkinInfo()
	{
		$oModuleAdminModel = getAdminModel('module');
		$skin_content = $oModuleAdminModel->getModuleMobileSkinHTML($this->module_info->module_srl);
		Context::set('skin_content', $skin_content);
		$this->setTemplateFile('skininfo');
	}

	/**
	 *
	 */
	function dispCashpayAdminDeleteMid()
	{
		$module_srl = Context::get('module_srl');
		if(!$module_srl)
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispCashpayAdminModInstList'));
			return;
		}

		$security = new Security();
		$security->encodeHTML('module_info..module', 'module_info..mid');

		$this->setTemplateFile('deletemid');
	}
}
/* End of file cashpay.admin.view.php */
/* Location: ./modules/cashpay/cashpay.admin.view.php */
