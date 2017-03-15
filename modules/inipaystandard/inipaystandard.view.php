<?php

/**
 * @class  inipaystandardView
 * @author CONORY (https://www.conory.com)
 * @brief The view class of the inipaystandard module
 */
class inipaystandardView extends inipaystandard
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	function dispInipaystandardForm()
	{
		$oEpayController = getController('epay');
		$reviewOutput = $oEpayController->reviewOrder();

		if(!$reviewOutput->toBool())
		{
			return $reviewOutput;
		}

		$payment_method = Context::get('payment_method');
		$_SESSION['inipaystandard']['payment_method'] = $payment_method;
		$_SESSION['inipaystandard']['transaction_srl'] = $reviewOutput->transaction_srl;
		$_SESSION['inipaystandard']['price'] = $reviewOutput->price;
		$_SESSION['inipaystandard']['error_return_url'] = Context::get('error_return_url');

		Context::set('order_srl', $reviewOutput->order_srl);
		Context::set('review_form', $reviewOutput->review_form);
		Context::set('item_name', $reviewOutput->item_name);
		Context::set('price', $reviewOutput->price);
		Context::set('purchaser_name', $reviewOutput->purchaser_name);
		Context::set('purchaser_email', $reviewOutput->purchaser_email);
		Context::set('purchaser_telnum', '010-0000-0000');

		switch($payment_method)
		{
			case "CC":
				$payment_method = "Card";
				break;
			case "IB":
				$payment_method = "DirectBank";
				break;
			case "VA":
				$payment_method = "VBank";
				break;
			case "MP":
				$payment_method = "HPP";
				break;
			default:
				$payment_method = "Card";
		}
		Context::set('payment_method', $payment_method);

		// 휴대폰 소액 결제 타입 (1 - 콘텐츠, 2 - 실물)
		if($this->module_info->method_mobilephone == 'M')
		{
			$HPP = '2';
		}
		else
		{
			$HPP = '1';
		}

		$acceptmethod = sprintf('HPP(%s):Card(0):OCB:receipt:cardpoint', $HPP);

		if(!$this->module_info->va_receipt || $this->module_info->va_receipt == 'Y')
		{
			$acceptmethod .= ':va_receipt';
		}

		Context::set('acceptmethod', $acceptmethod);

		require_once('libs/INIStdPayUtil.php');

		$SignatureUtil = new INIStdPayUtil();

		if($this->module_info->ini_payment_test_mode == 'Y')
		{
			$inipay_mid = 'INIpayTest';
			$inipay_signkey = 'SU5JTElURV9UUklQTEVERVNfS0VZU1RS';
		}
		else
		{
			$inipay_mid = $this->module_info->inipay_mid;
			$inipay_signkey = $this->module_info->inipay_signkey;
		}

		$timestamp = $SignatureUtil->getTimestamp();
		Context::set('timestamp', $timestamp);
		Context::set('pay_mid', $inipay_mid);

		Context::set('mKey', $SignatureUtil->makeHash($inipay_signkey, "sha256"));

		$params = array('oid' => $reviewOutput->order_srl, 'price' => $reviewOutput->price, 'timestamp' => $timestamp,);

		Context::set('sign', $SignatureUtil->makeSignature($params, "sha256"));

		//template
		$template_path = sprintf("%sskins/%s/", $this->module_path, $this->module_info->skin);
		if(!is_dir($template_path) || !$this->module_info->skin)
		{
			$this->module_info->skin = 'default';
			$template_path = sprintf("%sskins/%s/", $this->module_path, $this->module_info->skin);
		}

		$this->setTemplatePath($template_path);
		$this->setTemplateFile('pay');
	}
}
