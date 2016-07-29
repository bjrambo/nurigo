<?php

/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  cashpayView
 * @author NURIGO(contact@nurigo.net)
 * @brief  cashpayView
 */
class cashpayView extends cashpay
{
	/**
	 * @brief initialize this class
	 */
	function init()
	{
		// set template path
		if($this->module_info->module != 'cashpay')
		{
			$this->module_info->skin = 'default';
		}
		if(!$this->module_info->skin)
		{
			$this->module_info->skin = 'default';
		}
		$this->setTemplatePath($this->module_path . "skins/{$this->module_info->skin}");
		Context::set('module_info', $this->module_info);
	}

	/**
	 * @brief print account information
	 */
	function dispCashpayForm()
	{
		if($this->module_info->login_required == 'Y')
		{
			if(!Context::get('is_logged'))
			{
				return new Object(-1, 'msg_login_required');
			}
		}
		$oEpayController = getController('epay');
		// get products info using cartnos
		$reviewOutput = $oEpayController->reviewOrder();
		if(!$reviewOutput->toBool())
		{
			return $reviewOutput;
		}
		Context::set('transaction_srl', $reviewOutput->transaction_srl);
		Context::set('order_srl', $reviewOutput->order_srl);
		Context::set('review_form', $reviewOutput->review_form);
		Context::set('item_name', $reviewOutput->item_name);
		Context::set('price', $reviewOutput->price);
		Context::set('purchaser_name', $reviewOutput->purchaser_name);
		Context::set('purchaser_email', $reviewOutput->purchaser_email);
		Context::set('purchaser_telnum', $reviewOutput->purchaser_telnum);

		$obj = new stdClass();
		$obj->bank_name = $this->module_info->bank_name;
		$obj->account_number = $this->module_info->account_number;
		$obj->account_holder = $this->module_info->account_holder;
		Context::set('account_info', $obj);

		$this->setTemplateFile('start');
	}
}
/* End of file cashpay.view.php */
/* Location: ./modules/cashpay/cashpay.view.php */
