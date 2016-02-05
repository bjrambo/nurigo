<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  inipaymobileModel
 * @author NURIGO(contact@nurigo.net)
 * @brief  inipaymobileModel
 */
class inipaymobileModel extends inipaymobile 
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
		$args->page = 1;
		$args->list_count = 100;
		$args->page_count = 10;
		$output = executeQueryArray('inipaymobile.getModInstList', $args);
		if(!$output->toBool()) return $output;
		$list = $output->data;
		if(!is_array($list)) $list = array();
		foreach($list as $key=>$val)
		{
			$pg_modules[$val->module_srl] = $val;
		}
	}

	/**
	 * @brief 결제방식정보를 리턴
	 */
	function getPaymentMethods($module_srl)
	{
		if(!$module_srl) return array();
		$oModuleModel = &getModel('module');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);

		$list = array();

		if($module_info->method_creditcard=='Y')
		{
			$modinfo = new stdClass();
			$modinfo->mid = $module_info->mid;
			$modinfo->module = 'inipay';
			$modinfo->act = 'dispInipaymobileForm';
			$modinfo->mode = 'submit';
			$modinfo->title = '신용카드결제';
			$modinfo->payment_method = 'CC';
			$modinfo->guide = $module_info->guide_creditcard;
			$list['inipaymobile_creditcard'] = $modinfo;
		}
		if($module_info->method_directbank=='Y')
		{
			$modinfo = new stdClass();
			$modinfo->mid = $module_info->mid;
			$modinfo->module = 'inipay';
			$modinfo->mode = 'submit';
			$modinfo->act = 'dispInipaymobileForm';
			$modinfo->title = '실시간계좌이체';
			$modinfo->payment_method = 'IB';
			$modinfo->guide = $module_info->guide_directbank;
			$list['inipaymobile_banktransfer'] = $modinfo;
		}
		if($module_info->method_virtualaccount=='Y')
		{
			$modinfo = new stdClass();
			$modinfo->mid = $module_info->mid;
			$modinfo->module = 'inipay';
			$modinfo->mode = 'submit';
			$modinfo->act = 'dispInipaymobileForm';
			$modinfo->title = '가상계좌';
			$modinfo->payment_method = 'VA';
			$modinfo->guide = $module_info->guide_virtualaccount;
			$list['inipaymobile_virtualaccount'] = $modinfo;
		}
		if($module_Info->method_mobilephone=='Y')
		{
			$modinfo = new stdClass();
			$modinfo->mid = $module_info->mid;
			$modinfo->module = 'inipay';
			$modinfo->mode = 'submit';
			$modinfo->act = 'dispInipaymobileForm';
			$modinfo->title = '휴대폰';
			$modinfo->payment_method = 'MP';
			$modinfo->guide = $module_info->guide_mobilephone;
			$list['inipaymobile_mobilephone'] = $modinfo;
		}

		return $list;
	}
}
