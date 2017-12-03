<?php
use PayPal\Api\ExecutePayment;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;

/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  paypalController
 * @author NURIGO(contact@nurigo.net)
 * @brief  paypalController
 */
class paypalController extends paypal
{
	function procPaypalExecutePayment()
	{
		$order_srl = Context::get('order_srl');
		$transaction_srl = Context::get('transaction_srl');
		$paymentId = $_SESSION['paymentId'];

		if(isset($_GET['success']) && $_GET['success'] == 'true')
		{
			if(!$apiContext)
			{
				require __DIR__ . '/bootstrap.php';
			}

			$oEpayModel = getModel('epay');
			$transaction_info = $oEpayModel->getTransactioninfo($transaction_srl);
			// States : created; approved; failed; canceled; expired. (value generated by PayPal)
			$payment = Payment::get($paymentId, $apiContext);

			$payment_object = json_decode($payment->toJSON());
			//debugprint($payment_object);
			$oPaypalModel = getModel('paypal');
			$oModuleModel = getModel('module');
			// total amount from paypal transaction 
			$total_amount = $payment_object->transactions[0]->amount->total;
			$oPaypalModuleConfig = $oModuleModel->getModuleConfig('paypal');
			// total amount before paypal transaction
			$original_price = $oPaypalModel->getConvertedPrice($transaction_info->payment_amount, $oPaypalModuleConfig->conversion_rate);
			if($original_price != $total_amount)
			{
				$redirectUrl = getNotEncodedUrl('act', 'dispPaypalError', 'transaction_srl', $transaction_srl, 'error_code', '1');
				$this->setRedirectUrl($redirectUrl);
				return;
			}

			$execution = new PaymentExecution();
			$execution->setPayerId($_GET['PayerID']);
			$result = $payment->execute($execution, $apiContext);

			switch($result->getState())
			{
				case 'created':
					$state = '1';
					break;
				case 'approved':
					$state = '2';
					break;
				case 'failed':
				case 'canceled':
				case 'expired':
					$state = '3';
					break;
			}

			$args = return $this->makeObject();
			$args->add('state', $state);
			$args->add('payment_amount', $transaction_info->payment_amount);
			$args->add('result_code', $result->getState());
			$args->add('result_message', 'Success');
			$args->add('payment_method', 'PP');
			$args->add('transaction_srl', $transaction_srl);

			// afterPayment will call an after trigger
			$oEpayController = getController('epay');
			$output = $oEpayController->afterPayment($args);
			if(!$output->toBool())
			{
				return $output;
			}
			$return_url = $output->get('return_url');
			if($return_url)
			{
				$this->setRedirectUrl($return_url);
			}
		}
		else
		{
			$redirectUrl = getNotEncodedUrl('act', 'dispPaypalError', 'transaction_srl', $transaction_srl, 'error_code', '2');
			$this->setRedirectUrl($redirectUrl);
		}
	}
}
/* End of file paypal.controller.php */
/* Location: ./modules/paypal/paypal.controller.php */
