<?php
/**
 * @class currency 
 */
class currency extends ModuleObject
{
	/**
	 * @brief Implement if additional tasks are necessary when installing
	 */
	function moduleInstall()
	{
		return new Object();
	}

	/**
	 * @brief a method to check if successfully installed
	 */
	function checkUpdate()
	{
		return false;
	}

	/**
	 * @brief Execute update
	 */
	function moduleUpdate()
	{
		return new Object(0,'success_updated');
	}

	/**
	 * @brief Re-generate the cache file
	 */
	function recompileCache()
	{
	}
}
/* End of file currency.class.php */
/* Location: ./modules/currency/currency.class.php */
