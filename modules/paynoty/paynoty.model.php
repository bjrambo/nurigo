<?php

/**
 * @class  paynotyModel
 * @author NURIGO(contact@nurigo.net)
 * @brief  paynotyModel
 */
class paynotyModel extends paynoty
{
	private static $config = NULL;
	/**
	 * @brief constructor
	 */
	function init()
	{
	}

	function getConfig()
	{
		if(self::$config === NULL)
		{
			$oModuleModel = getModel('module');
			$config = $oModuleModel->getModuleConfig('paynoty');
			self::$config = $config;
		}

		return self::$config;
	}
}
