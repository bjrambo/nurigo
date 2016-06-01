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
	function procPaynotyAdminInsert()
	{
		$params = Context::gets('admin_phones', 'sender_no', 'admin_emails', 'sender_name', 'sender_email', 'content', 'mail_content', 'module_srls', 'msgtype', 'sending_method');
		$params->config_srl = Context::get('config_srl');

		if($params->config_srl)
		{
			// delete existences
			$args = new stdClass();
			$args->config_srl = $params->config_srl;
			$output = executeQuery('paynoty.deleteConfig', $args);
			if(!$output->toBool())
			{
				return $output;
			}
			$output = executeQuery('paynoty.deleteModule', $args);
			if(!$output->toBool())
			{
				return $output;
			}
		}
		else
		{
			// new sequence
			$params->config_srl = getNextSequence();
		}

		// insert module srls
		$module_srls = explode(',', $params->module_srls);
		foreach($module_srls as $srl)
		{
			$args = new stdClass();
			$args->config_srl = $params->config_srl;
			$args->module_srl = $srl;
			$output = executeQuery('paynoty.insertModuleSrl', $args);
			if(!$output->toBool())
			{
				return $output;
			}
		}

		//$params->extra_vars = serialize($extra_vars);
		
		// insert paynoty
		$output = executeQuery('paynoty.insertConfig', $params);
		if(!$output->toBool())
		{
			return $output;
		}

		$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispPaynotyAdminModify', 'config_srl', $params->config_srl);
		$this->setRedirectUrl($redirectUrl);
	}

	function procPaynotyAdminDelete()
	{
		$config_srl = Context::get('config_srl');
		if(!$config_srl)
		{
			return new Object(-1, 'msg_invalid_request');
		}

		if($config_srl)
		{
			// delete existences
			$args = new stdClass();
			$args->config_srl = $config_srl;
			$output_config = executeQuery('paynoty.deleteConfig', $args);
			$output_module = executeQuery('paynoty.deleteModule', $args);
		}
		$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispPaynotyAdminList');
		$this->setRedirectUrl($redirectUrl);
	}
}
