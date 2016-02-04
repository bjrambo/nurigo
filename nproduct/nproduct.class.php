<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nproduct
 * @author NURIGO(contact@nurigo.net)
 * @brief  nproduct
 */
require_once(_XE_PATH_.'modules/nproduct/nproduct.item.php');
require_once(_XE_PATH_.'modules/nproduct/ExtraItem.class.php');

define('WAIT_FOR_DEPOSIT', '1');
define('PREPARE_DELIVERY', '2');

class nproduct extends ModuleObject
{
	const ORDER_STATE_PAID = '2';
	const ORDER_STATE_COMPLETE = '3';

	/**
	 * @brief constructor
	 */
	function nproduct()
	{
		$this->ORDER_STATE_COMPLETE = nproduct::ORDER_STATE_COMPLETE;
		$this->ORDER_STATE_PAID = nproduct::ORDER_STATE_PAID;
		$this->order_status = array('0'=>'카트보관', '1'=>'입금대기', '2'=>'입금완료', '3'=>'구매완료','A'=>'취소','B'=>'반품,교환','C'=>'환불');
	}

	/**
	 * @brief check json format
	 */
	function isJson($data)
	{
		$output = @json_decode($data);
		return is_array($output);
	}

	/**
	 * @brief convert csv to json
	 */
	function convertCsvToJson($csvData)
	{
		$list = array();
		$array = array_filter(explode(',', $csvData));
		foreach($array as $element)
		{
			$obj = new stdClass();
			$obj->item_srl = $element;
			$obj->force_purchase = 'N';
			$list[] = $obj;
		}
		return json_encode($list);
	}

	/**
	 * @brief 모듈 설치 실행
	 **/
	function moduleInstall()
	{
		$oModuleModel = &getModel('module');
		$oModuleController = &getController('module');
		return new Object();
	}

	/**
	 * @brief 설치가 이상없는지 체크
	 **/
	function checkUpdate()
	{
		$oModuleModel = &getModel('module');
		$oDB = &DB::getInstance();
				
		//extra_Vars check
		$output = $this->checkModuleExtraVars();
		if($output == 'true') return true;

		// 2013. 09. 25 when add new menu in sitemap, custom menu add
		if(!$oModuleModel->getTrigger('menu.getModuleListInSitemap', 'nproduct', 'model', 'triggerModuleListInSitemap', 'after')) return true;

		// 2013/10/29
		if(!$oDB->isColumnExists('nproduct_items', 'updatetime')) return true;

		// 2014/04/29
		if(!$oDB->isColumnExists('nproduct_items', 'related_items')) return true;

		// 2014/12/30
		if(!$oDB->isColumnExists('nproduct_items', 'minimum_order_quantity')) return true;

		return false;
	}

	function checkModuleExtraVars($condition = null)
	{
		$oModuleModel = &getModel('module');
		$oModuleAdminModel = &getAdminModel('module');
		$oNproductModel =  &getModel('nproduct');

		$args->module = 'nproduct';
		$args->site_srl = '0';
		$output = $oModuleAdminModel->getModuleMidList($args); // module_list get
		if(!$output->data) return;

		foreach($output->data as $k => $v)
		{
			// proc_module get
			$extra_output = $oModuleModel->getModuleExtraVars($v->module_srl);
			$proc_module = $extra_output[$v->module_srl]->proc_module;

			// default extra_vars
			$default_extra_forms = $oNproductModel->getNproductExtraVars($proc_module);
			if($default_extra_forms)
			{
				// current extra_vars
				$item_extra_output = $oNproductModel->getItemExtraByModuleSrl($v->module_srl);
				if($item_extra_output)
				{
					$item_extra = array();
					foreach($item_extra_output as $key => $val)
					{
						$item_extra[] = $val->column_name;
					}
				}

				if(!$item_extra_output)
				{
					if($condition == 'install') $this->updateExtraVars($v->module_srl);
					else return 'true';
				}
				else
				{
					foreach($default_extra_forms as $key => $val)
					{
						if(!in_array($val->column_name, $item_extra))
						{
							if($condition == 'install') $this->updateExtraVars($v->module_srl, $val->column_name);
							else return 'true';
						}
					}
				}
			}
		}
	}

	function updateExtraVars($module_srl, $condition=null)
	{
		$oModuleModel =  &getModel('module');
		$oNproductModel =  &getModel('nproduct');
		$oNprocutAdminController = &getAdminController('nproduct');

		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
		if(!$module_info) return;

		$default_extra_forms = $oNproductModel->getNproductExtraVars($module_info->proc_module);
		if(!$default_extra_forms) return;

		foreach($default_extra_forms as $key=>$val)
		{
			$extra->module_srl = $module_srl;
			$extra->column_type = $val->column_type;
			$extra->column_name = $val->column_name;
			$extra->column_title = $val->column_title;
			$extra->default_value = explode("\n", str_replace("\r", '',$val->default_value));
			$extra->required = $val->required;
			$extra->is_active = (isset($extra->required));
			$extra->description = $val->description;

			if($condition)
			{
				if($condition == $val->column_name)
				{
					$output = $oNprocutAdminController->insertItemExtra($extra);
					unset($extra);
				}
			}
			else
			{
				$output = $oNprocutAdminController->insertItemExtra($extra);
				unset($extra);
			}
		}
	}

	/**
	 * @brief 업데이트(업그레이드)
	 **/
	function moduleUpdate()
	{
		$oDB = &DB::getInstance();
		$oModuleModel = &getModel('module');
		$oModuleController = &getController('module');

		// 2013. 09. 25 when add new menu in sitemap, custom menu add
		if(!$oModuleModel->getTrigger('menu.getModuleListInSitemap', 'nproduct', 'model', 'triggerModuleListInSitemap', 'after'))
			$oModuleController->insertTrigger('menu.getModuleListInSitemap', 'nproduct', 'model', 'triggerModuleListInSitemap', 'after');

		$this->checkModuleExtraVars('install');

		// 2013/10/29
		if(!$oDB->isColumnExists('nproduct_items', 'updatetime')) $oDB->addColumn('nproduct_items', 'updatetime', 'date');

		// 2014/04/29
		if(!$oDB->isColumnExists('nproduct_items', 'related_items')) $oDB->addColumn('nproduct_items', 'related_items', 'text');

		// 2014/12/30
		if(!$oDB->isColumnExists('nproduct_items', 'minimum_order_quantity')) $oDB->addColumn('nproduct_items', 'minimum_order_quantity', 'number', '11', '0', TRUE);

		return new Object(0, 'success_updated');
	}

	function moduleUninstall()
	{
	}

	/**
	 * @brief 캐시파일 재생성
	 **/
	function recompileCache()
	{
	}
}
/* End of file nproduct.class.php */
/* Location: ./modules/nproduct/nproduct.class.php */
