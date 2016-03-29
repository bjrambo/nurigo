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
		$params = Context::gets('admin_phones', 'admin_emails', 'sender_name', 'sender_email', 'content', 'mail_content', 'module_srls', 'msgtype', 'sending_method');
		$params->config_srl = Context::get('config_srl');

		if($params->config_srl)
		{
			// delete existences
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
			unset($args);
			$args->config_srl = $params->config_srl;
			$args->module_srl = $srl;
			$output = executeQuery('paynoty.insertModuleSrl', $args);
			if(!$output->toBool())
			{
				return $output;
			}
		}

		//$params->extra_vars = serialize($extra_vars);

		debugPrint('params : ' . serialize($params));
		// insert paynoty
		$output = executeQuery('paynoty.insertConfig', $params);
		debugPrint('insertConfig : ' . serialize($output));
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
			$args->config_srl = $config_srl;
			$query_id = "paynoty.deleteConfig";
			executeQuery($query_id, $args);
			$query_id = "paynoty.deleteModule";
			executeQuery($query_id, $args);
		}
		$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispPaynotyAdminList');
		$this->setRedirectUrl($redirectUrl);
	}
}
