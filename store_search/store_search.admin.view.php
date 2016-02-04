<?php
class store_searchAdminView extends store_search
{

	/**
	 * Cofiguration of integration serach module
	 *
	 * @var object module config
	 */
	var $config = null;

	/**
	 * Initialization
	 *
	 * @return void
	 */
	function init()
	{
		// Get configurations (using module model object)
		$oModuleModel = &getModel('module');
		$this->config = $oModuleModel->getModuleConfig('integration_search');
		Context::set('config',$this->config);

		$this->setTemplatePath($this->module_path."/tpl/");
	}

	/**
	 * Module selection and skin set
	 *
	 * @return Object
	 */
	function dispStore_searchAdminContent()
	{
		// Get a list of skins(themes)
		$oModuleModel = &getModel('module');
		$skin_list = $oModuleModel->getSkins($this->module_path);
		Context::set('skin_list',$skin_list);
		// Get a list of module categories
		$module_categories = $oModuleModel->getModuleCategories();
		// Generated mid Wanted list
		$obj = new stdClass();
		$obj->site_srl = 0;

		$security = new Security();
		$security->encodeHTML('skin_list..title');

		$this->setTemplateFile("index");
	}

	/**
	 * Skin Settings
	 *
	 * @return Object
	 */
	function dispStore_searchAdminSkinInfo()
	{
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

		// 공통 모듈 권한 설정 페이지 호출
		$oModuleAdminModel = &getAdminModel('module');
		$skin_content = $oModuleAdminModel->getModuleSkinHTML($this->module_info->module_srl);
		Context::set('skin_content', $skin_content);
		$this->setTemplateFile('skininfo');
	}
	
	function dispStore_searchAdminView()
	{ 
		$this->setTemplateFile('store_search');
	}	
}	

?>
