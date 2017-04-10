<?php

/**
 * @class  paynotyController
 * @author contact@nurigo.net
 * @brief  paynotyController
 */
class paynotyController extends paynoty
{
	function init()
	{

	}

	function triggerCompletePayment(&$obj)
	{
		$oPaynotyModel = getModel('paynoty');
		$oTextmessageController = getController('textmessage');
		$config = $oPaynotyModel->getConfig();

		if($config->use != 'Y')
		{
			return new Object();
		}

		$order_info = getModel('ncart')->getOrderInfo($obj->order_srl);
		$extra_vars = unserialize($order_info->extra_vars);
		$product_name = $order_info->title;

		if(preg_match('/^\\$user_lang->[a-zA-Z0-9]+$/', $product_name))
		{
			$product_name = preg_replace_callback('!\$user_lang->([a-z0-9\_]+)!is', array($this,'_replaceLangCode'), $product_name);
		}

		$args = new stdClass();
		$args->module_srl = $obj->module_srl;
		$output = executeQuery('module.getMidInfo', $args);

		$module_info = $output->data;
		$logged_info = Context::get('logged_info');
		if(Context::get('is_logged'))
		{
			$obj->p_name = $logged_info->nick_name;
			$obj->email_address = $logged_info->email_address;
		}
		else
		{
			$obj->p_name = $obj->vact_name;
		}
		$obj->order_title = $product_name;

		$sms_message = paynoty::mergeKeywords($config->content, $obj);
		$sms_message = paynoty::mergeKeywords($sms_message, $module_info);
		$sms_message = str_replace("&nbsp;", "", strip_tags($sms_message));

		$mail_content = paynoty::mergeKeywords($config->mail_content, $obj);
		$mail_content = paynoty::mergeKeywords($mail_content, $module_info);

		$tmp_obj = new stdClass();
		$tmp_obj->article_url = getFullUrl('', 'document_srl', $obj->document_srl);
		$mail_content = $this->mergeKeywords($mail_content, $tmp_obj);
		$sms_message = $this->mergeKeywords($sms_message, $tmp_obj);

		if(isset($config->sending_method['cta']) || isset($config->sending_method['sms']) && $oTextmessageController)
		{
			$args = new stdClass();
			$args->product_name = $product_name;
			$args->content = $sms_message;
			if($config->phone_number_type == 'logged')
			{
				if(!Context::get('is_logged') && !$config->variable_name || !Context::get('is_logged'))
				{
					$args->recipient_no = $extra_vars->phone[0] . $extra_vars->phone[1] . $extra_vars->phone[2];
				}
				else
				{
					$args->recipient_no = $logged_info->{$config->variable_name}[0].$logged_info->{$config->variable_name}[1].$logged_info->{$config->variable_name}[2];
				}
			}
			else
			{
				$args->recipient_no = $extra_vars->phone[0] . $extra_vars->phone[1] . $extra_vars->phone[2];
			}
			$args->sender_no = $config->sender_no;
			if(isset($config->sending_method['cta']) || isset($config->sending_method['sms']) && isset($config->sending_method['cta']))
			{
				$args->type = 'cta';
				if($config->sender_key)
				{
					$args->sender_key = $config->sender_key;
				}
				$json_args = new stdClass();
				$json_args->type = 'cta';
				$json_args->to = $args->recipient_no;
				$json_args->text = $args->content;
				$extension = array($json_args);
				$args->extension = json_encode($extension);
			}
			elseif(isset($config->sending_method['sms']))
			{
				$args->type = 'sms';
			}
			$output = $oTextmessageController->sendmessage($args);
			if(!$output->toBool())
			{
				return $output;
			}
		}

		if(isset($config->sending_method['email']))
		{
			if($config->sender_email)
			{
				$sender_email_address = $config->sender_email;
			}

			if($config->sender_name)
			{
				$sender_name = $config->sender_name;
			}

			$oMail = new Mail();
			$oMail->setTitle($product_name);
			$oMail->setContent($mail_content);
			$oMail->setSender($sender_name, $sender_email_address);
			$target_email = explode(',', $config->admin_emails);
			$oMail->setReceiptor($obj->email_address, $obj->email_address);
			$oMail->send();
			foreach($target_email as $email_address)
			{
				$email_address = trim($email_address);
				if(!$email_address)
				{
					continue;
				}
				$oMail->setReceiptor($email_address, $email_address);
				$oMail->send();
			}
		}
	}

	function _replaceLangCode($matches)
	{
		static $lang = null;

		if(is_null($lang))
		{
			$site_module_info = Context::get('site_module_info');
			if(!$site_module_info)
			{
				$oModuleModel = getModel('module');
				$site_module_info = $oModuleModel->getDefaultMid();
				Context::set('site_module_info', $site_module_info);
			}
			$cache_file = sprintf('%sfiles/cache/lang_defined/%d.%s.php', _XE_PATH_, $site_module_info->site_srl, Context::getLangType());
			if(!file_exists($cache_file))
			{
				$oModuleAdminController = getAdminController('module');
				$oModuleAdminController->makeCacheDefinedLangCode($site_module_info->site_srl);
			}

			if(file_exists($cache_file))
			{
				$moduleAdminControllerMtime = filemtime(_XE_PATH_ . 'modules/module/module.admin.controller.php');
				$cacheFileMtime = filemtime($cache_file);
				if($cacheFileMtime < $moduleAdminControllerMtime)
				{
					$oModuleAdminController = getAdminController('module');
					$oModuleAdminController->makeCacheDefinedLangCode($site_module_info->site_srl);
				}

				require_once($cache_file);
			}
		}
		if(!Context::get($matches[1]) && $lang[$matches[1]]) return $lang[$matches[1]];

		return str_replace('$user_lang->','',$matches[0]);
	}
}
