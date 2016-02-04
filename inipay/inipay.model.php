<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  inipayModel
 * @author NURIGO(contact@nurigo.net)
 * @brief  inipayModel
 */
class inipayModel extends inipay 
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
		$output = executeQueryArray('inipay.getModInstList', $args);
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

		if($module_info->method_creditcard=='Y')
		{
			$modinfo = new stdClass();
			$modinfo->mid = $module_info->mid;
			$modinfo->module = 'inipay';
			$modinfo->act = 'dispInipayForm';
			$modinfo->mode = 'submit';
			$modinfo->title = '신용카드결제';
			$modinfo->payment_method = 'CC';
			$modinfo->guide = $module_info->guide_creditcard;
			$list['inipay_creditcard'] = $modinfo;
		}
		if($module_info->method_directbank=='Y')
		{
			$modinfo = new stdClass();
			$modinfo->mid = $module_info->mid;
			$modinfo->module = 'inipay';
			$modinfo->mode = 'submit';
			$modinfo->act = 'dispInipayForm';
			$modinfo->title = '실시간계좌이체';
			$modinfo->payment_method = 'IB';
			$modinfo->guide = $module_info->guide_directbank;
			$list['inipay_banktransfer'] = $modinfo;
		}
		if($module_info->method_virtualaccount=='Y')
		{
			$modinfo = new stdClass();
			$modinfo->mid = $module_info->mid;
			$modinfo->module = 'inipay';
			$modinfo->mode = 'submit';
			$modinfo->act = 'dispInipayForm';
			$modinfo->title = '가상계좌';
			$modinfo->payment_method = 'VA';
			$modinfo->guide = $module_info->guide_virtualaccount;
			$list['inipay_virtualaccount'] = $modinfo;
		}
		if($module_info->method_mobilephone!='N')
		{
			$modinfo = new stdClass();
			$modinfo->mid = $module_info->mid;
			$modinfo->module = 'inipay';
			$modinfo->mode = 'submit';
			$modinfo->act = 'dispInipayForm';
			$modinfo->title = '휴대폰';
			$modinfo->payment_method = 'MP';
			$modinfo->guide = $module_info->guide_mobilephone;
			$list['inipay_mobilephone'] = $modinfo;
		}

		return $list;
	}
}
