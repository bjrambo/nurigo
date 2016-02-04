<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nstoreAdminModel
 * @author NURIGO(contact@nurigo.net)
 * @brief  nstoreAdminModel
 */ 
class nstoreAdminModel extends nstore
{
	function getNstoreAdminDeleteModInst() {
		$oModuleModel = &getModel('module');

		$module_srl = Context::get('module_srl');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
		Context::set('module_info', $module_info);

		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path.'tpl', 'form_delete_modinst');
		$this->add('tpl', str_replace("\n"," ",$tpl));
	}

	function getNstoreAdminDeleteOrders() 
	{
		$oNstoreModel = &getModel('nstore');

		$order_info_arr = $oNstoreModel->getOrdersInfo(Context::get('order_srl'));
		Context::set('order_info_arr', $order_info_arr);

		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path.'tpl', 'form_delete_orders');
		$this->add('tpl', str_replace("\n"," ",$tpl));
	}


	function getNstoreAdminEscrowInfo()
	{
		$args->order_srl = Context::get('order_srl');
		$output = executeQuery('nstore.getEscrowInfo', $args);
		$this->add('data', $output->data);
	}

	function getSalesInfo($date=null)
	{
		if($date) $args->regdate = $date;
		$output = executeQuery('nstore.getSalesInfo', $args);
		if(!$output->data->amount) $output->data->amount = 0;
		return $output->data;
	}


    function getTotalStatus()
    {
       	$this->order_status = $this->getOrderStatus();
        $output = executeQueryArray('nstore.getOrderStat', $args);
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

	function getModuleMidList($args)
	{
		$args->list_count = 100;
		$args->page_count = 10;
		$output = executeQueryArray('nstore.getModuleMidList', $args);
		if(!$output->toBool()) return $output;

		ModuleModel::syncModuleToSite($output->data);

		return $output;
	}
}

/* End of file nstore.admin.model.php */
/* Location: ./modules/nstore/nstore.admin.model.php */
