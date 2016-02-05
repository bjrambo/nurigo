<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  inipaymobile
 * @author NURIGO(contact@nurigo.net)
 * @brief  inipaymobile
 */
class inipaymobile extends ModuleObject
{
	/**
	 * @brief module install
	 */
	function moduleInstall()
	{
		$oModuleModel = &getModel('module');
		$oModuleController = &getController('module');

		if(!$oModuleModel->getTrigger('epay.getPgModules', 'inipaymobile', 'model', 'triggerGetPgModules', 'before'))
		{
			$oModuleController->insertTrigger('epay.getPgModules', 'inipaymobile', 'model', 'triggerGetPgModules', 'before');
		}

		return new Object();
	}

	/**
	 * @brief check to see if update is necessary
	 */
	function checkUpdate()
	{
		$oModuleModel = &getModel('module');
		$oDB = &DB::getInstance();
		if(!$oModuleModel->getTrigger('epay.getPgModules', 'inipaymobile', 'model', 'triggerGetPgModules', 'before')) return true;
		return false;
	}

	/**
	 * @brief module update
	 */
	function moduleUpdate()
	{
		$oDB = &DB::getInstance();
		$oModuleModel = &getModel('module');
		$oModuleController = &getController('module');

		if (!$oModuleModel->getTrigger('epay.getPgModules', 'inipaymobile', 'model', 'triggerGetPgModules', 'before')) {
			$oModuleController->insertTrigger('epay.getPgModules', 'inipaymobile', 'model', 'triggerGetPgModules', 'before');
		}
	}

	/**
	 * @brief module uninstall
	 */
	function moduleUninstall()
	{
		$oModuleController = &getController('module');
		$oModuleController->deleteTrigger('epay.getPgModules', 'inipaymobile', 'model', 'triggerGetPgModules', 'before');
	}

	/**
	 * @brief recompile the cache after module install or update
	 */
	function recompileCache()
	{
	}
}
/* End of file inipaymobile.class.php */
/* Location: ./modules/inipaymobile/inipaymobile.class.php */
