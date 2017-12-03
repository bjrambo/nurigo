<?php
/**
 * @class store_review
 * @author NURIGO(contact@nurigo.net)
 * @brief store_review module's high class
 **/
require_once(_XE_PATH_ . 'modules/store_review/store_review.item.php');

class store_review extends ModuleObject
{

	/**
	 * @brief implemented if additional tasks are required when installing
	 **/
	function moduleInstall()
	{
		return $this->makeObject();
	}

	/**
	 * @brief method to check if installation is succeeded
	 **/
	function checkUpdate()
	{
		$oDB = &DB::getInstance();
		$oModuleModel = getModel('module');

		return false;
	}

	/**
	 * @brief Execute update
	 **/
	function moduleUpdate()
	{
		$oDB = &DB::getInstance();
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');

		return $this->makeObject(0, 'success_updated');
	}

	/**
	 * @brief Regenerate cache file
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
/* End of file store_review.admin.controller.php */
/* Location: ./modules/store_review/store_review.admin.controller.php */
