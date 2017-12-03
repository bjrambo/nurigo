<?php

/**
 * @class  inipaystandard
 * @author CONORY (https://www.conory.com)
 * @brief The parent class of the inipaystandard module
 */
class inipaystandard extends ModuleObject
{

	function __construct()
	{
		if(Context::getSslStatus() == 'optional')
		{
			$ssl_actions = array(
				'procInipaystandardDoIt',
				'procInipay',
				'dispInipaystandardForm'
			);
			Context::addSSLActions($ssl_actions);
		}
	}

	/**
	 * @brief install module
	 */
	function moduleInstall()
	{
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');

		return $this->makeObject();
	}

	/**
	 * @brief update check.
	 */
	function checkUpdate()
	{
		$oDB = DB::getInstance();
		$oModuleModel = getModel('module');

		if(!$oModuleModel->getTrigger('moduleHandler.init', 'inipaystandard', 'controller', 'triggerModuleHandler', 'before'))
		{
			return true;
		}
		if(!$oModuleModel->getTrigger('epay.getPgModules', 'inipaystandard', 'model', 'triggerGetPgModules', 'before'))
		{
			return true;
		}

		return false;
	}

	/**
	 * @brief module update
	 */
	function moduleUpdate()
	{
		$oDB = DB::getInstance();
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');

		if(!$oModuleModel->getTrigger('epay.getPgModules', 'inipaystandard', 'model', 'triggerGetPgModules', 'before'))
		{
			$oModuleController->insertTrigger('epay.getPgModules', 'inipaystandard', 'model', 'triggerGetPgModules', 'before');
		}

		if(!$oModuleModel->getTrigger('moduleHandler.init', 'inipaystandard', 'controller', 'triggerModuleHandler', 'before'))
		{
			$oModuleController->insertTrigger('moduleHandler.init', 'inipaystandard', 'controller', 'triggerModuleHandler', 'before');
		}

		return $this->makeObject(0, 'success_updated');
	}

	/**
	 * @brief Uninstall module
	 */
	function moduleUninstall()
	{
		return $this->makeObject();
	}

	/**
	 * @brief cache file recompile.
	 */
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