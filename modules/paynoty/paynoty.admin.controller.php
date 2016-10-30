<?php

/**
 * @class  paynotyAdminController
 * @author NURIGO(contact@nurigo.net)
 * @brief  paynotyAdminController
 */
class paynotyAdminController extends paynoty
{
	/**
	 * @brief constructor
	 */
	function init()
	{
	}

	/**
	 * @brief saving config values.
	 **/
	function procPaynotyAdminConfig()
	{
		$oModuleController = getController('module');

		$obj = Context::getRequestVars();
		$config = new stdClass();
		$config_vars = array(
			'use',
			'mail_content',
			'sending_method',
			'sender_no',
			'admin_phones',
			'content',
			'admin_emails',
			'sender_name',
			'sender_email',
			'sender_key',
			'variable_name',
			'phone_number_type'
		);

		foreach($config_vars as $val)
		{
			$config->{$val} = $obj->{$val};
		}

		$output = $oModuleController->insertModuleConfig('paynoty', $config);
		if(!$output->toBool())
		{
			return new Object(-1, '설정에 오류가 있었습니다.');
		}

		$this->setMessage('success_updated');

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON')))
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispPaynotyAdminConfig');
			header('location: ' . $returnUrl);
			return;
		}
	}
}
