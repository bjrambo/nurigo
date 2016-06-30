<?php
class textmessage extends ModuleObject
{

	/**
	 * @brief install textmessage module
	 * @return new Object
	 **/
	function moduleInstall()
	{
		return new Object();
	}

	/**
	 * @brief if update is necessary it returns true
	 **/
	function checkUpdate()
	{
		return false;
	}

	/**
	 * @brief update module
	 * @return new Object
	 **/
	function moduleUpdate()
	{
		return new Object();
	}

	/**
	 * @brief regenerate cache file
	 * @return none
	 **/
	function recompileCache()
	{
	}
}
/* End of file textmessage.class.php */
/* Location: ./modules/textmessage.class.php */
