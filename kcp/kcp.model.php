<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  kcpModel
 * @author NURIGO(contact@nurigo.net)
 * @brief  kcpModel
 */
class kcpModel extends kcp 
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
		$output = executeQueryArray('kcp.getModInstList', $args);
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
			$modinfo->module = 'kcp';
			$modinfo->act = 'dispKcpForm';
			$modinfo->mode = 'submit';
			$modinfo->title = '신용카드';
			$modinfo->payment_method = 'CC';
			$modinfo->guide = $module_info->guide_creditcard;
			$list['kcp_creditcard'] = $modinfo;
		}
		if($module_info->method_directbank=='Y')
		{
			$modinfo = new stdClass();
			$modinfo->mid = $module_info->mid;
			$modinfo->module = 'kcp';
			$modinfo->mode = 'submit';
			$modinfo->act = 'dispKcpForm';
			$modinfo->title = '실시간계좌이체';
			$modinfo->payment_method = 'IB';
			$modinfo->guide = $module_info->guide_directbank;
			$list['kcp_banktransfer'] = $modinfo;
		}
		if($module_info->method_virtualaccount=='Y')
		{
			$modinfo = new stdClass();
			$modinfo->mid = $module_info->mid;
			$modinfo->module = 'kcp';
			$modinfo->mode = 'submit';
			$modinfo->act = 'dispKcpForm';
			$modinfo->title = '가상계좌';
			$modinfo->payment_method = 'VA';
			$modinfo->guide = $module_info->guide_virtualaccount;
			$list['kcp_virtualaccount'] = $modinfo;
		}
		if($module_info->method_mobilephone=='Y')
		{
			$modinfo = new stdClass();
			$modinfo->mid = $module_info->mid;
			$modinfo->module = 'kcp';
			$modinfo->mode = 'submit';
			$modinfo->act = 'dispKcpForm';
			$modinfo->title = '휴대폰';
			$modinfo->payment_method = 'MP';
			$modinfo->guide = $module_info->guide_mobilephone;
			$list['kcp_mobilephone'] = $modinfo;
		}

		return $list;
	}

	/**
	 * @brief translate epay code to kcp code
	 */
	function getKcpCode($code)
	{
		$payment_method = "100000000000";
        switch($code)
        {
            case "CC":
                $payment_method = "100000000000";
                break;
            case "IB":
                $payment_method = "010000000000";
                break;
            case "VA":
                $payment_method = "001000000000";
                break;
            case "MP":
                $payment_method = "000010000000";
                break;
            default:
                $payment_method = "100000000000";
        }
		return $payment_method;
	}

	/**
	 * @brief translate kcp code to epay code
	 */
	function getEpayCode($code)
	{
		$payment_method = "CC";
        switch($code)
        {
            case "100000000000":
                $payment_method = "CC";
                break;
            case "010000000000":
                $payment_method = "IB";
                break;
            case "001000000000":
                $payment_method = "VA";
                break;
            case "000010000000":
                $payment_method = "MP";
                break;
            default:
                $payment_method = "CC";
        }
		return $payment_method;
	}
}
