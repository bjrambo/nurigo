<?php

/**
 * @class  cympuserAdminController
 * @author billy(contact@nurigo.net)
 * @brief  cympuserAdminController
 */
class cympuserAdminController extends cympuser
{
	/**
	 * @brief constructor
	 */
	function init()
	{
	}

	function procCympuserAdminConfig()
	{
		$config = self::getConfig();
		$obj = Context::getRequestVars();

		if(!$config)
		{
			$config = new stdClass();
		}
		$config->layout_srl = $obj->layout_srl;
		$config->mlayout_srl = $obj->mlayout_srl;
		$config->skin = $obj->skin;
		$config->mskin = $obj->mskin;

		$output = self::setConfig($config);
		if(!$output->toBool())
		{
			return $output;
		}

		$this->setMessage('success_updated');

		if (Context::get('success_return_url'))
		{
			$this->setRedirectUrl(Context::get('success_return_url'));
		}
		else
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispCympuserAdminConfig'));
		}
	}
}
