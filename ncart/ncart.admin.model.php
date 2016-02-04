<?php
    /**
     * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
     * @class  ncartAdminModel
     * @author NURIGO(contact@nurigo.net)
     * @brief  ncartAdminModel
     */ 
class ncartAdminModel extends ncart
{

	function getNcartAdminDeleteModInst() {
		$oModuleModel = &getModel('module');

		$module_srl = Context::get('module_srl');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
		Context::set('module_info', $module_info);

		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path.'tpl', 'form_delete_modinst');
		$this->add('tpl', str_replace("\n"," ",$tpl));
	}


	function getNcartAdminDeleteOrders() 
	{
		$oNcartModel = &getModel($this->getExtMod());

		$order_info_arr = $oNcartModel->getOrdersInfo(Context::get('order_srl'));
		Context::set('order_info_arr', $order_info_arr);

		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path.'tpl', 'form_delete_orders');
		$this->add('tpl', str_replace("\n"," ",$tpl));
	}

	function getNcartAdminFieldInfo()
	{
		$args->field_srl = Context::get('field_srl');
		$output = executeQuery('ncart.getFieldInfo', $args);
		if(!$output->toBool()) return $output;

		$this->add('data', $output->data);
	}

	/**
	 * get order details
	 */
	function getNcartAdminOrderDetails() 
	{
		$oNcartModel = &getModel('ncart');
		$oEpayModel = &getModel('epay');

		$order_srl = Context::get('order_srl');
		$order_info = $oNcartModel->getOrderInfo($order_srl);

		$payment_info = $oEpayModel->getTransactionByOrderSrl($order_srl);
		Context::set('payment_info',$payment_info);
		Context::set('order_info', $order_info);
		Context::set('order_status', $this->getOrderStatus());
		Context::set('delivery_companies', $oNcartModel->getDeliveryCompanies());
		Context::set('payment_method', $this->getPaymentMethods());
		Context::set('delivery_inquiry_urls', $this->delivery_inquiry_urls);

		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path.'tpl', 'form_orderdetails');
		$this->add('tpl', str_replace("\n"," ",$tpl));
	}
}

/* End of file ncart.admin.model.php */
/* Location: ./modules/ncart/ncart.admin.model.php */
