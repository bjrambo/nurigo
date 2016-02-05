<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  paynotyController
 * @author contact@nurigo.net
 * @brief  paynotyController
 */
class paynotyController extends paynoty 
{

	function sendMessages($content, $mail_content, $title, $sender, $config) 
	{
		$oTextmessageController = &getController('textmessage');
		$oPaynotyModel = &getModel('paynoty');

		if (in_array($config->sending_method,array('1','2'))&&$oTextmessageController) 
		{
			$args->recipient_no = explode(',',$config->admin_phones);
			//$args->sender_no = $receiver->recipient_no;
			$args->content = $content;
			$output = $oTextmessageController->sendMessage($args);
			if (!$output->toBool()) return $output;
		}

		if (in_array($config->sending_method,array('1','3'))) 
		{
			if ($config->sender_email)
			{
				$sender_email_address = $config->sender_email;
			}
			else
			{
				$sender_email_address = $sender->email_address;
			}
			if ($config->sender_name)
			{
				$sender_name = $config->sender_name;
			}
			else
			{
				$sender_name = $sender->nick_name;
			}
			$oMail = new Mail();
			$oMail->setTitle($title);
			$oMail->setContent($mail_content);
			$oMail->setSender($sender_name, $sender_email_address);
			$target_email = explode(',',$config->admin_emails);
			foreach ($target_email as $email_address) 
			{
				$email_address = trim($email_address);
				if (!$email_address) continue;
				$oMail->setReceiptor($email_address, $email_address);
				$oMail->send();
			}
		}
		return new Object();
	}

	function processPaynoty(&$config,&$obj,&$sender,&$module_info) 
	{
		// message content
		$sms_message = $this->mergeKeywords($config->content, $obj);
		$sms_message = $this->mergeKeywords($sms_message, $module_info);
		$sms_message = str_replace("&nbsp;", "", strip_tags($sms_message));

		// mail content
		$mail_content = $this->mergeKeywords($config->mail_content, $obj);
		$mail_content = $this->mergeKeywords($mail_content, $module_info);

/*
		// get document info.
		$oDocumentModel = &getModel('document');
		$oDocument = $oDocumentModel->getDocument($obj->document_srl);
		debugPrint('oDocument : ' . serialize($oDocument));
 */

		$tmp_obj->article_url = getFullUrl('','document_srl', $obj->document_srl);
		$tmp_content = $this->mergeKeywords($mail_content, $tmp_obj);
		$tmp_message = $this->mergeKeywords($sms_message, $tmp_obj);
		$output = $this->sendMessages($tmp_message, $tmp_content, $obj->order_title, $sender, $config);
		if (!$output->toBool()) return $output;
		return new Object();
	}

	/**
	 * @brief trigger for document insertion.
	 * @param $obj : document object.
	 **/

	function triggerCompletePayment(&$state) 
	{		
		// if module_srl not set, just return with success;
		if (!$state->epay_module_srl) 
		{
			return;
		}

		// if module_srl is wrong, just return with success
		$args->module_srl = $state->module_srl;
		$output = executeQuery('module.getMidInfo', $state);
		if (!$output->toBool() || !$output->data) 
		{
			return;
		}
		$module_info = $output->data;
		unset($args);
		if (!$module_info) 
		{
			return;
		}

		// check login.
		$sender = new StdClass();
		$sender->nick_name = $state->buyername;
		$sender->email_address = $state->buyeremail;
		$logged_info = Context::get('logged_info');
		if ($logged_info) 
		{
			$sender = $logged_info;
		}

		// get configuration info. no configuration? just return.
		$oPaynotyModel = &getModel('paynoty');
		$config_list = $oPaynotyModel->getConfigListByModuleSrl($state->epay_module_srl);
		if (!$config_list) 
		{
			return;
		}

		foreach ($config_list as $key=>$val) 
		{
			$output = $this->processPaynoty($val,$state,$sender,$module_info);
			if(!$output->toBool())
			{
				debugPrint('processPaynoty : '.$output->getMessage());
			}	
		}
	}
}
?>
