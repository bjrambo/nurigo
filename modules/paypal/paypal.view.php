<?php
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;

/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  paypalView
 * @author NURIGO(contact@nurigo.net)
 * @brief  paypalView
 */
class paypalView extends paypal
{
	/**
	 * @brief initialize this class
	 */
	function init()
	{
		// set template path
		if ($this->module_info->module != 'paypal') $this->module_info->skin = 'default';
		if (!$this->module_info->skin) $this->module_info->skin = 'default';
		$this->setTemplatePath($this->module_path."skins/{$this->module_info->skin}");
		Context::set('module_info',$this->module_info);
	}

	/**
	 * @brief epay.getPaymentForm 에서 호출됨
	 */
	function dispPaypalForm() 
	{
		$oEpayController = &getController('epay');
		$oNcartModel = &getModel('ncart');
		$oModuleModel = &getModel('module');
		$oPaypalModuleConfig = $oModuleModel->getModuleConfig('paypal');
		$oPaypalModel = &getModel('paypal');
		$paypalhome = sprintf(_XE_PATH_."files/epay/%s", $this->module_info->module_srl);

		$logged_info = Context::get('logged_info');
		if($logged_info)
		{
			$oEpayModel = &getModel('epay');
			$transaction_count = $oEpayModel->getTransactionCountByMemberSrl($logged_info->member_srl);
			if($transaction_count < $oPaypalModuleConfig->minimum_transactions)
			{
				Context::set('error_code', '3');
				Context::set('minimum_transactions_count', $oPaypalModuleConfig->minimum_transactions);
				$this->setTemplateFile('error');
				return;
			}
		}

		// get products info using cartnos
		$output = $oEpayController->reviewOrder();
		if(!$output->toBool()) return $output;

		Context::set('review_form', $output->review_form);
		//$cart_info = $output->cart_info;
		$transaction_srl = $output->transaction_srl;
		$order_srl = $output->order_srl;

		//Context::set('cart_info', $cart_info);
		Context::set('price', $output->price);
		Context::set('transaction_srl', $transaction_srl);
		Context::set('order_srl', $order_srl);

		require __DIR__ . '/bootstrap.php';

		// Paypal account - 'paypal' / Credit card - 'credit_card'
		$payer = new Payer();
		$payer->setPaymentMethod("paypal");

		$item = new Item();
		$item_name = $output->item_name;

		$item->setName($item_name)
		     ->setCurrency($oPaypalModuleConfig->currency_code)
		     ->setQuantity(1)
		     ->setPrice($oPaypalModel->getConvertedPrice($output->price, $oPaypalModuleConfig->conversion_rate));

		$itemList = new ItemList();
		$itemList->setItems(array($item));

		/*
		$details = new Details();
		$details->setShipping(number_format($cart_info->delivery_fee, 2))
				->setTax(number_format($cart_info->vat, 2))
				->setSubtotal(number_format($cart_info->supply_amount, 2));
		 */
		
		$amount = new Amount();
		$amount->setCurrency($oPaypalModuleConfig->currency_code)
			   ->setTotal($oPaypalModel->getConvertedPrice($output->price, $oPaypalModuleConfig->conversion_rate));
//			   ->setDetails($details);

		$transaction = new Transaction();
		$transaction->setAmount($amount)
					->setItemList($itemList)
					->setDescription("Payment description");

		$baseUrl = getBaseUrl();
		$redirectUrls = new RedirectUrls();

	
		$returnURL = getNotEncodedFullUrl('', 'module', $this->module_info->module, 'act', 'procPaypalExecutePayment', 'success', 'true', 'order_srl', $order_srl, 'transaction_srl', $transaction_srl);
		$cancelURL = getNotEncodedFullUrl('', 'module', $this->module_info->module, 'act', 'procPaypalExecutePayment', 'success', 'false', 'order_srl', $order_srl, 'transaction_srl', $transaction_srl);
		
		$redirectUrls->setReturnUrl($returnURL)
					 ->setCancelUrl($cancelURL);

		$payment = new Payment();
		$payment->setIntent("sale")
			->setPayer($payer)
			->setRedirectUrls($redirectUrls)
			->setTransactions(array($transaction));
		try {
			$output = $payment->create($apiContext);
		} catch (PayPal\Exception\PPConnectionException $ex) {
			echo "Exception: " . $ex->getMessage() . PHP_EOL;
			var_dump($ex->getData());	
			exit(1);
		}

		foreach($payment->getLinks() as $link) {
			if($link->getRel() == 'approval_url') {
				$redirectUrl = $link->getHref();
				break;
			}
		}

		$_SESSION['paymentId'] = $payment->getId();
		Context::set('redirectUrl', $redirectUrl);
		Context::set('payment_method', $payment_method);
		$this->setTemplateFile('formdata');
	}

	function dispPaypalError()
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
/* End of file paypal.view.php */
/* Location: ./modules/paypal/paypal.view.php */
