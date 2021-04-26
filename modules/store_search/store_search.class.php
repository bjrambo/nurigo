<?php
/**
 * @class  store_search
 * @author NURIGO (contact@nurigo.net)
 * @brief view class of the store_search module
 **/

require_once(_XE_PATH_ . 'modules/integration_search/integration_search.class.php');

class store_search extends ModuleObject
{

	/**
	 * @brief Implement if additional tasks are necessary when installing
	 **/
	function moduleInstall()
	{
		$oModuleController = getController('module');
		$oModuleController->insertModuleExtend('integration_search', 'store_search', 'view', '');
		$oModuleController->insertModuleExtend('integration_search', 'store_search', 'model', '');
		$oModuleController->insertModuleExtend('integration_search', 'store_search', 'mobile', '');
		return $this->makeObject();
	}

	/**
	 * @brief a method to check if successfully installed
	 **/
	function checkUpdate()
	{
		$oModuleModel = getModel('module');
		if(!$oModuleModel->getModuleExtend('integration_search', 'view', ''))
		{
			return true;
		}
		if(!$oModuleModel->getModuleExtend('integration_search', 'model', ''))
		{
			return true;
		}
		if(!$oModuleModel->getModuleExtend('integration_search', 'mobile', ''))
		{
			return true;
		}
		return false;
	}

	/**
	 * @brief Execute update
	 **/
	function moduleUpdate()
	{
		$oModuleController = getController('module');
		$oModuleModel = getModel('module');
		if(!$oModuleModel->getModuleExtend('integration_search', 'view', ''))
		{
			$oModuleController->insertModuleExtend('integration_search', 'store_search', 'view', '');
		}
		if(!$oModuleModel->getModuleExtend('integration_search', 'model', ''))
		{
			$oModuleController->insertModuleExtend('integration_search', 'store_search', 'model', '');
		}
		if(!$oModuleModel->getModuleExtend('integration_search', 'mobile', ''))
		{
			$oModuleController->insertModuleExtend('integration_search', 'store_search', 'mobile', '');
		}
		return $this->makeObject(0, 'success_updated');
	}

	/**
	 * @brief Re-generate the cache file
	 **/
	function recompileCache()
	{
	}

	/**
	 * Create new Object for php7.2
	 * @param int $code
	 * @param string $msg
	 * @return BaseObject|Object
	 */
	public function makeObject($code = 0, $msg = 'success')
	{
		return class_exists('BaseObject') ? new BaseObject($code, $msg) : new Object($code, $msg);
	}
}
