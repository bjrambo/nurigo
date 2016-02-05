<?php
class store_searchAdminController extends store_search
{
	function procStore_searchAdminDel()
	{
		$oModuleController = &getController('module');
		$oModuleController->deleteModuleExtend('integration_search','','');
		$this->setRedirectUrl(getNotencodedUrl("","module","admin","act","dispStore_searchAdminView"));
		if($oModuleController)$this->setMessage("success_deleted");
	}

	/**
	 * Save Settings
	 *
	 * @return mixed
	 */
	function procStore_searchAdminInsertConfig()
	{
		// Get configurations (using module model object)
		$oModuleModel = &getModel('module');
		$config = $oModuleModel->getModuleConfig('store_search');

		$args->skin = Context::get('skin');

		$oModuleController = &getController('module');
		$output = $oModuleController->insertModuleConfig('store_search',$args);

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispStore_searchAdminContent');
		return $this->setRedirectUrl($returnUrl, $output);
	}

}


?>
