<?php
/**
 * vi:set ts=4 sw=4 noexpandtab fileencoding=utf-8:
 * @class epayAdminView
 * @author NURIGO(contact@nurigo.net)
 * @brief epay admin view
 **/
class epayAdminView extends epay
{
	/**
	 * @brief initialize this module.
	 */
	function init()
	{
		$template_path = sprintf("%stpl/",$this->module_path);
		$this->setTemplatePath($template_path);	


		// module model 객체 생성
		$oModuleModel = &getModel('module');

		// 모듈 카테고리 목록을 구함
		$module_category = $oModuleModel->getModuleCategories();
		Context::set('module_category', $module_category);

        if(Context::get('module')=='cympusadmin')
        {
            $classfile = _XE_PATH_.'modules/cympusadmin/cympusadmin.class.php';
            if(file_exists($classfile))
            {
                    require_once($classfile);
                    cympusadmin::init();
            }
        }

		// module_srl이 있으면 미리 체크하여 존재하는 모듈이면 module_info 세팅
		$module_srl = Context::get('module_srl');
		if(!$module_srl && $this->module_srl)
		{
			$module_srl = $this->module_srl;
			Context::set('module_srl', $module_srl);
		}

		$oModuleModel = &getModel('module');

		// module_srl이 넘어오면 해당 모듈의 정보를 미리 구해 놓음
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
	}

	/**
	 * @brief list module instances.
	 **/
	function dispEpayAdminEpayList()
	{
		// load epay module instances
		$args->sort_index = "module_srl";
		$args->page = Context::get('page');
		$args->list_count = 20;
		$args->page_count = 10;
		$args->s_module_category_srl = Context::get('module_category_srl');
		$output = executeQueryArray('epay.getEpayList', $args);
		ModuleModel::syncModuleToSite($output->data);

		// set variables for template
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('epay_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);

		// set template file
		$this->setTemplateFile('epaylist');
	}

	/**
	 * @brief module instance creation form
	 */
	function dispEpayAdminInsertEpay()
	{
		$oModuleModel = &getModel('module');
		$oEpayModel = &getModel('epay');

		$module_srl = Context::get('module_srl');
		if(!$module_srl && $this->module_srl)
		{
			$module_srl = $this->module_srl;
			Context::set('module_srl', $module_srl);
		}

		// module_srl이 넘어오면 해당 모듈의 정보를 미리 구해 놓음
		if($module_srl)
		{
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
			if(!$module_info)
			{
				return new Object(-1, 'msg_invalid_request');
			}
			else
			{
				ModuleModel::syncModuleToSite($module_info);
				Context::set('module_info',$module_info);
			}
		}

		// 스킨 목록을 구해옴
		$skin_list = $oModuleModel->getSkins($this->module_path);
		Context::set('skin_list',$skin_list);

		$mskin_list = $oModuleModel->getSkins($this->module_path, "m.skins");
		Context::set('mskin_list', $mskin_list);

		// 레이아웃 목록을 구해옴
		$oLayoutModel = &getModel('layout');
		$layout_list = $oLayoutModel->getLayoutList();
		Context::set('layout_list', $layout_list);

		$mobile_layout_list = $oLayoutModel->getLayoutList(0,"M");
		Context::set('mlayout_list', $mobile_layout_list);

		// plugins
		$plugins = $oEpayModel->getPluginList();
		Context::set('plugins', $plugins);

		$pg_modules = array();
		$output = ModuleHandler::triggerCall('epay.getPgModules', 'before', $pg_modules);
		if(!$output->toBool()) return $output;
		Context::set('pg_modules', $pg_modules);

		$this->setTemplateFile('insertepay');
	}
	
	/**
	 * @brief list plugins.
	 */
	function dispEpayAdminPluginList()
	{
		$args->page = Context::get('page');
		$output = executeQueryArray('epay.getPluginList', $args);
		if (!$output->toBool()) return $output;

		Context::set('plugins', $output->data);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);

		$this->setTemplateFile('pluginlist');
	}

	/**
	 * @brief plugin creation form.
	 */
	function dispEpayAdminInsertPlugin()
	{
		// plugins
		$oEpayModel = &getModel('epay');
		$plugins = $oEpayModel->getPluginsXmlInfo();
		Context::set('plugins', $plugins);

		$this->setTemplateFile('insertplugin');
	}

	/**
	 * @brief plugin update form.
	 */
	function dispEpayAdminUpdatePlugin()
	{
		$oEpayModel = &getModel('epay');

		$plugin_srl = Context::get('plugin_srl');

		// plugin info
		$plugin_info = $oEpayModel->getPluginInfo($plugin_srl);
		Context::set('plugin_info', $plugin_info);

		$this->setTemplateFile('updateplugin');
	}

	/**
	 * @brief list transactions
	 */
	function dispEpayAdminTransactions()
	{
		$classfile = _XE_PATH_.'modules/cympusadmin/cympusadmin.class.php';
		if(file_exists($classfile))
		{
				require_once($classfile);
				$output = cympusadmin::init();
				if(!$output->toBool()) return $output;
		}

		// transactions
		$args->page = Context::get('page');
		if(Context::get('search_key'))
		{
			$search_key = Context::get('search_key');
			$search_value = Context::get('search_value');
			$args->{$search_key} = $search_value;
		}
		$output = executeQueryArray('epay.getTransactionList',$args);
		if(!$output->toBool()) return $output;
		$list = $output->data;
		ModuleHandler::triggerCall('epay.getTransactionList', 'after', $list);
		Context::set('list', $list);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);

		// module instances
		$output = executeQueryArray('epay.getAllModInstList');
		$modinst_list = array();
		$list = $output->data;
		if(!is_array($list)) $list = array();
		foreach($list as $key=>$modinfo)
		{
			$modinst_list[$modinfo->module_srl] = $modinfo;
		}
		Context::set('modinst_list',$modinst_list);

		$this->setTemplateFile('transactions');
	}

	/**
	 * @brief 스킨 정보 보여줌
	 **/
	function dispEpayAdminSkinInfo() 
	{
		// 공통 모듈 권한 설정 페이지 호출
		$oModuleAdminModel = &getAdminModel('module');
		$skin_content = $oModuleAdminModel->getModuleSkinHTML($this->module_info->module_srl);
		Context::set('skin_content', $skin_content);
		$this->setTemplateFile('skininfo');
	}

	/**
	 * @brief 스킨 정보 보여줌
	 **/
	function dispEpayAdminMobileSkinInfo() 
	{
		// 공통 모듈 권한 설정 페이지 호출
		$oModuleAdminModel = &getAdminModel('module');
		$skin_content = $oModuleAdminModel->getModuleMobileSkinHTML($this->module_info->module_srl);
		Context::set('skin_content', $skin_content);
		$this->setTemplateFile('skininfo');
	}

	/**
	 * @brief display the grant information
	 **/
	function dispEpayAdminGrantInfo()
	{
		// get the grant infotmation from admin module
		$oModuleAdminModel = getAdminModel('module');
		$grant_content = $oModuleAdminModel->getModuleGrantHTML($this->module_info->module_srl, $this->xml_info->grant);
		Context::set('grant_content', $grant_content);

		$this->setTemplateFile('grant_list');
	}
}
/* End of file epay.admin.view.php */
/* Location: ./modules/epay/epay.admin.view.php */
