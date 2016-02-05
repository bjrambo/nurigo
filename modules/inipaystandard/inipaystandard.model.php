<?php
	/**
	 * @class  inipaystandardModel
	 * @author CONORY (http://www.conory.com)
	 * @brief The model class fo the inipaystandard module
	 */
	class inipaystandardModel extends inipaystandard
	{
		/**
		 * @brief Initialization
		 */
		function init()
		{
		}
		
		/**
		 * @brief return pg info.
		 */
		function triggerGetPgModules(&$pg_modules)
		{
			$args = new stdClass;
			$args->sort_index = "module_srl";
			$args->page = Context::get('page');
			$args->list_count = 100;
			$args->page_count = 10;
			$output = executeQueryArray('inipaystandard.getModuleList', $args);
			
			foreach($output->data as $key=>$val)
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
			
			$oModuleModel = getModel('module');
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);

			$list = array();

			if($module_info->method_creditcard == 'Y')
			{
				$modinfo = new stdClass;
				$modinfo->mid = $module_info->mid;
				$modinfo->module = 'inipaystandard';
				$modinfo->act = 'dispInipaystandardForm';
				$modinfo->mode = 'submit';
				$modinfo->title = '신용카드결제';
				$modinfo->payment_method = 'CC';
				$modinfo->guide = $module_info->guide_creditcard;
				$list['inipaystandard_creditcard'] = $modinfo;
			}
			
			if($module_info->method_directbank == 'Y')
			{
				$modinfo = new stdClass;
				$modinfo->mid = $module_info->mid;
				$modinfo->module = 'inipaystandard';
				$modinfo->mode = 'submit';
				$modinfo->act = 'dispInipaystandardForm';
				$modinfo->title = '실시간계좌이체';
				$modinfo->payment_method = 'IB';
				$modinfo->guide = $module_info->guide_directbank;
				$list['inipaystandard_banktransfer'] = $modinfo;
			}
			
			if($module_info->method_virtualaccount == 'Y')
			{
				$modinfo = new stdClass;
				$modinfo->mid = $module_info->mid;
				$modinfo->module = 'inipaystandard';
				$modinfo->mode = 'submit';
				$modinfo->act = 'dispInipaystandardForm';
				$modinfo->title = '가상계좌';
				$modinfo->payment_method = 'VA';
				$modinfo->guide = $module_info->guide_virtualaccount;
				$list['inipaystandard_virtualaccount'] = $modinfo;
			}
			
			if($module_info->method_mobilephone != 'N')
			{
				$modinfo = new stdClass;
				$modinfo->mid = $module_info->mid;
				$modinfo->module = 'dispInipaystandardForm';
				$modinfo->mode = 'submit';
				$modinfo->act = 'dispInipayForm';
				$modinfo->title = '휴대폰';
				$modinfo->payment_method = 'MP';
				$modinfo->guide = $module_info->guide_mobilephone;
				$list['inipaystandard_mobilephone'] = $modinfo;
			}
			
			return $list;
		}
	}