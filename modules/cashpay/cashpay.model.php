<?php

/**
 * @class  cashpayModel
 * @author NURIGO(contact@nurigo.net)
 * @brief  cashpayModel
 */
class cashpayModel extends cashpay
{
	/**
	 * @brief initialize this class
	 */
	function init()
	{
	}

	/**
	 * @brief return pg info.
	 */
	function triggerGetPgModules(&$pg_modules)
	{
		// get the module instance list
		$args = new stdClass();
		$args->sort_index = "module_srl";
		$args->page = 1;
		$args->list_count = 100;
		$args->page_count = 10;
		$output = executeQueryArray('cashpay.getModInstList', $args);
		if(!$output->toBool())
		{
			return $output;
		}
		$list = $output->data;
		if(!is_array($list))
		{
			$list = array();
		}

		foreach($list as $key => $val)
		{
			$pg_modules[$val->module_srl] = $val;
		}
	}

	function getPaymentMethods($module_srl)
	{
		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);

		$list = array();
		$modinfo = new stdClass();
		$modinfo->mid = $module_info->mid;
		$modinfo->module = 'cashpay';
		$modinfo->act = 'dispCashpayForm';
		$modinfo->mode = 'submit';
		$modinfo->title = $module_info->account_title;
		$modinfo->payment_method = 'BT';
		$modinfo->guide = $module_info->description;
		$list['cashpay'] = $modinfo;
		return $list;
	}

	function getCashpayForm()
	{
		/*
		if (!$args->price) return new Object(0,'No input of price');
		if (!$args->epay_module_srl) return new Object(-1,'No input of epay_module_srl');
		if (!$args->module_srl) return new Object(-1,'No input of module_srl');

		Context::set('module_srl', $args->module_srl);
		Context::set('epay_module_srl', $args->epay_module_srl);
		Context::set('plugin_srl', $this->plugin_info->plugin_srl);

		Context::set('item_name', $args->item_name);
		Context::set('purchaser_name', $args->purchaser_name);
		Context::set('purchaser_email', $args->purchaser_email);
		Context::set('purchaser_telnum', $args->purchaser_telnum);
		Context::set('script_call_before_submit', $args->script_call_before_submit);
		Context::set('join_form', $args->join_form);
		 */

		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path . 'tpl', 'formdata');
		$this->add('tpl', str_replace("\n", " ", $tpl));
	}
}
