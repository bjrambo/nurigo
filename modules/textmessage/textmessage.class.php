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
	 * @return Object
	 **/
	function moduleInstall()
	{
		return $this->makeObject();
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
	 * @return Object
	 **/
	function moduleUpdate()
	{
		return $this->makeObject();
	}

	/**
	 * @brief regenerate cache file
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
/* End of file textmessage.class.php */
/* Location: ./modules/textmessage.class.php */
