<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  paypalModel
 * @author NURIGO(contact@nurigo.net)
 * @brief  paypalModel
 */
class paypalModel extends paypal 
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
		$args->sort_index = "module_srl";
		$args->page = Context::get('page');
		$args->list_count = 100;
		$args->page_count = 10;
		$output = executeQueryArray('paypal.getModInstList', $args);
		if(!$output->toBool()) return $output;
		$list = $output->data;
		if(!is_array($list)) $list = array();
		foreach($list as $key=>$val)
		{
			$pg_modules[$val->module_srl] = $val;
		}
	}

	/**
	 * @brief return payment methods info.
	 */
	function getPaymentMethods($module_srl)
	{
		if(!$module_srl) return array();
		$oModuleModel = &getModel('module');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);

		$list = array();
		$modinfo = new stdClass();
		$modinfo->mid = $module_info->mid;
		$modinfo->module = 'paypal';
		$modinfo->act = 'dispPaypalForm';
		$modinfo->mode = 'submit';
		$modinfo->title = 'Paypal결제';
		$modinfo->payment_method = 'PP';
		$modinfo->guide = $module_info->guide;
		$list['paypal'] = $modinfo;

		return $list;
	}

	/*
	 * @brief return converted price 
	 */
	function getConvertedPrice($price, $rate)
	{
		// Paypal returns error if decimal number is not equal to 2.
		return number_format(round($price * $rate, 2), 2);
	}
	
}
