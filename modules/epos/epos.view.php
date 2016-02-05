<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  eposView
 * @author NURIGO(contact@nurigo.net)
 * @brief  eposView
 */
class eposView extends epos
{
	/**
	 * @brief initialize this class
	 */
	function init()
	{
		// default values
		if(!$this->module_info->service_type) $this->module_info->service_type = 'sandbox';

		// set template path
		if ($this->module_info->module != 'epos') $this->module_info->skin = 'default';
		if (!$this->module_info->skin) $this->module_info->skin = 'default';
		$this->setTemplatePath($this->module_path."skins/{$this->module_info->skin}");
		Context::set('module_info',$this->module_info);
	}

	/**
	 * @brief epay.getPaymentForm 에서 호출됨
	 */
	function dispEposForm() 
	{
		$oEpayController = &getController('epay');
		$oNcartModel = &getModel('ncart');
		$oModuleModel = &getModel('module');
		$oEposModuleConfig = $oModuleModel->getModuleConfig('epos');
		$oEposModel = &getModel('epos');

		// get products info using cartnos
		Context::set('plugin_srl', $this->module_info->module_srl);
		$review_args = Context::getRequestVars();
		$output = $oEpayController->reviewOrder();
		if(!$output->toBool()) return $output;

		Context::set('review_form', $output->review_form);
		//$cart_info = $output->cart_info;
		$transaction_srl = $output->transaction_srl;
		$order_srl = $output->order_srl;

		$cavalue = md5($this->module_info->storeid . $order_srl . $output->price . $this->module_info->cubkey);
		//Context::set('cart_info', $cart_info);
		Context::set('price', $output->price);
		Context::set('transaction_srl', $transaction_srl);
		Context::set('order_srl', $order_srl);
		Context::set('cavalue', $cavalue);

		$this->setTemplateFile('formdata');
	}

	function dispEposError()
	{
		$oEpayModel = &getModel('epay');
		$transaction_srl = Context::get('transaction_srl');
		$error_code = Context::get('error_code');
		$output = $oEpayModel->getTransactionInfo($transaction_srl);

		Context::set('error_code', $error_code);
		Context::set('info', $output);

		$this->setTemplateFile('error');
	}
}
/* End of file epos.view.php */
/* Location: ./modules/epos/epos.view.php */
