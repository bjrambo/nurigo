<?php
class manualorder extends EpayPlugin
{
	var $plugin = "manualorder";
	var $plugin_srl;
	var $inicis_id;
	var $inicis_pass;
	var $site_url;
	var $logo_image;
	var $skin;

	function manualorder()
	{
		parent::EpayPlugin();
	}

	function init(&$args)
	{
		$this->plugin_info = new StdClass();
		foreach ($args as $key=>$val)
		{
			$this->plugin_info->{$key} = $val;
		}
		
		if (!$this->plugin_info->account_title) $this->plugin_info->account_title = '결제대행';
		Context::set('plugin_info', $this->plugin_info);
	}

	function getFormData($args)
	{
		if (!$args->price) return new Object(0,'No input of price');
		$oTemplate = &TemplateHandler::getInstance();
		$tpl_path = _XE_PATH_."modules/epay/plugins/manualorder/tpl";
		$tpl_file = 'formdata.html';
		Context::set('module_srl', $args->module_srl);
		Context::set('epay_module_srl', $args->epay_module_srl);
		Context::set('plugin_srl', $this->plugin_info->plugin_srl);

		Context::set('item_name', $args->item_name);
		Context::set('purchaser_name', $args->purchaser_name);
		Context::set('purchaser_email', $args->purchaser_email);
		Context::set('purchaser_telnum', $args->purchaser_telnum);
		Context::set('script_call_before_submit', $args->script_call_before_submit);
		Context::set('join_form', $args->join_form);

		$html = $oTemplate->compile($tpl_path, $tpl_file);
		$output = new Object();
		$output->data = $html;
		return $output;
	}

	function processReview($args)
	{
		Context::set('price', $args->price);
		Context::set('order_title', $args->order_title);
		Context::set('manorder_pid', $args->manorder_pid);

		$oTemplate = &TemplateHandler::getInstance();
		$tpl_path = _XE_PATH_."modules/epay/plugins/manualorder/tpl";
		$tpl_file = 'review.html';
		$tpl_data = $oTemplate->compile($tpl_path, $tpl_file);

		$output = new Object();
		$output->add('tpl_data', $tpl_data);
		return $output;
	}

	function processPayment($args)
	{
		$output = new Object();
		$output->add('state', '1'); // not completed
		$output->add('payment_method', 'MO');
		$output->add('payment_amount', $args->price);
		$output->add('result_code', '0');
		$output->add('result_message', 'success');
		$output->add('pg_tid', $this->keygen());
		return $output;
	}

	function dispExtra1(&$epayObj)
	{
		$oEpayModel = &getModel('epay');
		$epayObj->setLayoutFile('default_layout');

		$vars = Context::getRequestVars();

		Context::set('manorder_pid', $vars->manorder_pid);

		$output = $oEpayModel->getEpayCheckUserId();

		$oTemplate = &TemplateHandler::getInstance();
		$tpl_path = _XE_PATH_."modules/epay/plugins/manualorder/tpl";

		if($output)
		{
			unset($vars->act);
			Context::set('request_vars', $vars);
			extract(get_object_vars($vars));
			
			$tpl_file = 'start.html';
		}
		else 
		{
			$tpl_file = 'invalid.html';
		}

		return $oTemplate->compile($tpl_path, $tpl_file);
	}

	/**
	 * @brief generate a key string.
	 * @return key string
	 **/
	function keygen()
	{
		$randval = rand(100000, 999999);
		$usec = explode(" ", microtime());
		$str_usec = str_replace(".", "", strval($usec[0]));
		$str_usec = substr($str_usec, 0, 6);
		return date("YmdHis") . $str_usec . $randval;
	}
}
/* End of file epay.view.php */
/* Location: ./modules/epay/epay.view.php */
