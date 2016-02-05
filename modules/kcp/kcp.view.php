<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  kcpView
 * @author NURIGO(contact@nurigo.net)
 * @brief  kcpView
 */
class kcpView extends kcp
{
	/**
	 * @brief initialize this class
	 */
	function init()
	{
		// set template path
		if ($this->module_info->module != 'kcp') $this->module_info->skin = 'default';
		if (!$this->module_info->skin) $this->module_info->skin = 'default';
		$this->setTemplatePath($this->module_path."skins/{$this->module_info->skin}");
		if ($this->module_info->service_mode == 'test')
		{
			$this->module_info->site_cd = "T0000" ;
			$this->module_info->site_key = "3grptw1.zW0GSo4PQdaGvsF__";
		}
		Context::set('module_info',$this->module_info);
	}

	/**
	 * @brief epay.getPaymentForm 에서 호출됨
	 */
	function dispKcpForm() 
	{
		$oEpayController = &getController('epay');
		$oNcartModel = &getModel('ncart');

		$kcphome = sprintf(_XE_PATH_."files/epay/%s", $this->module_info->module_srl);

		// get products info using cartnos
		$reviewOutput = $oEpayController->reviewOrder();
		if(!$reviewOutput->toBool()) return $reviewOutput;
		debugPrint('$reviewOutput');
		debugPrint($reviewOutput);
		Context::set('review_form', $reviewOutput->review_form);
		Context::set('item_name', $reviewOutput->item_name);
		Context::set('price', $reviewOutput->price);
		Context::set('transaction_srl', $reviewOutput->transaction_srl);
		Context::set('order_srl', $reviewOutput->order_srl);

		// payment method 변환 <-- CC, IB, VA, MP 를 결제모듈에서 정의된 것으로 대체할 수 있으면 좋겠음.
		$payment_method = Context::get('payment_method');
		switch($payment_method)
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
		Context::set('payment_method', $payment_method);
		debugPrint($payment_method);
		$this->setTemplateFile('formdata');
	}
}
/* End of file kcp.view.php */
/* Location: ./modules/kcp/kcp.view.php */
