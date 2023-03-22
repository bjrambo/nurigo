<?php

/**
 * @class  paynotyModel
 * @author NURIGO(contact@nurigo.net)
 * @brief  paynotyModel
 */
class paynotyModel extends paynoty
{
	protected static $config = NULL;
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
			if(!$config)
			{
				$config = new stdClass();
			}
			if(!isset($config->use))
			{
				$config->use = 'N';
			}
			if(!$config->phone_number_type)
			{
				$config->phone_number_type = 'payinfo';
			}
			self::$config = $config;
		}

		return self::$config;
	}

	function getNotifyMessage($args)
	{
		switch($args->template_code)
		{
			case 'C001':
				$str = sprintf('%s님! %s 상품구매가 완료되었습니다! 감사합니다!', $args->nick_name, $args->product_name);
				return $str;
				break;
		}
	}
}
