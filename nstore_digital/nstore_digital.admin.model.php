<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nstore_digitalAdminModel
 * @author NURIGO(contact@nurigo.net)
 * @brief  nstore_digitalAdminModel
 */ 
class nstore_digitalAdminModel extends nstore_digital
{

	function getNstore_digitalAdminDeleteModInst() 
	{
		$oModuleModel = &getModel('module');

		$module_srl = Context::get('module_srl');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
		Context::set('module_info', $module_info);

		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path.'tpl', 'form_delete_modinst');
		$this->add('tpl', str_replace("\n"," ",$tpl));
	}

	function getNstore_digitalAdminDeleteOrders() 
	{
		$oNstore_digitalModel = &getModel('nstore_digital');

		$order_info_arr = $oNstore_digitalModel->getOrdersInfo(Context::get('order_srl'));
		Context::set('order_info_arr', $order_info_arr);

		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path.'tpl', 'form_delete_orders');
		$this->add('tpl', str_replace("\n"," ",$tpl));
	}

	function getNstore_digitalAdminDeletePeriods() 
	{
		$oNstore_digitalModel = &getModel('nstore_digital');

		$period_info_arr = $oNstore_digitalModel->getPeriodsInfo(Context::get('period_srl'));

		Context::set('period_info_arr', $period_info_arr);

		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path.'tpl', 'form_delete_periods');
		$this->add('tpl', str_replace("\n"," ",$tpl));
	}

	function getSalesInfo($date=null)
	{
		if($date) $args->regdate = $date;
		$output = executeQuery('nstore_digital.getSalesInfo', $args);
		if(!$output->data->amount) $output->data->amount = 0;
		return $output->data;
	}

	function getTotalStatus()
	{
		$this->order_status = $this->getOrderStatus();
		$output = executeQueryArray('nstore_digital.getOrderStat', $args);
		if(!$output->toBool()) return $output;
		$list = $output->data;
		if(!is_array($list)) $list = array();

		$stat_arr = array();
		$keys = array_keys($this->order_status);

		foreach ($keys as $key) {
			$stat_arr[$key] = new StdClass();
			$stat_arr[$key]->count = 0;
			$stat_arr[$key]->title = $this->order_status[$key];
		}
		foreach ($list as $key=>$val) {
			$stat_arr[$val->order_status]->count = $val->count;
			$stat_arr[$val->order_status]->title = $this->order_status[$val->order_status];
		}
		return $stat_arr;
	}

}
/* End of file nstore_digital.admin.model.php */
/* Location: ./modules/nstore_digital/nstore_digital.admin.model.php */
