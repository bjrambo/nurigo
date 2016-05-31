<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  textmessage
 * @author wiley (wiley@nurigo.net)
 * @brief  base class of textmessage module
 **/

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
	function recompileCache() { }
}
/* End of file textmessage.class.php */
/* Location: ./modules/textmessage.class.php */
