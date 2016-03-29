<?php

/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  cashpayController
 * @author NURIGO(contact@nurigo.net)
 * @brief  cashpayController
 */
class cashpayController extends cashpay
{
	/**
	 * @brief pay
	 */
	function procCashpayDoIt()
	{
		$oEpayController = getController('epay');

		$vars = Context::getRequestVars();
		$output = $oEpayController->beforePayment($vars);

		if(!$output->toBool())
		{
			return $output;
		}

		$obj = new stdClass();
		$obj->bank_name = $this->module_info->bank_name;
		$obj->account_number = $this->module_info->account_number;
		$obj->account_holder = $this->module_info->account_holder;

		$output = new Object();
		$output->add('transaction_srl', Context::get('transaction_srl'));
		$output->add('state', '1'); // not completed
		$output->add('payment_method', 'BT');
		$output->add('payment_amount', Context::get('price'));
		$output->add('result_code', '0');
		$output->add('result_message', 'success');
		$output->add('pg_tid', $this->keygen());
		$output->add('vact_bankname', $obj->bank_name);
		$output->add('vact_num', $obj->account_number);
		$output->add('vact_name', $obj->account_holder);
		$output->add('vact_inputname', context::get('depositor_name'));

		// afterPayment will call an after trigger
		$output = $oEpayController->afterPayment($output);
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

	/**
	 * @brief generate a key string.
	 * @return key string
	 **/
	function keygen()
	{
		$randval = rand(100000, 999999);
		$usec = explode(" ", microtime());
		$str_usec = str_replace(".", "", strval($usec[0]));
		$str_usec = substr($str_usec, 0, 6);
		return date("YmdHis") . $str_usec . $randval;
	}

	function getPaymethod($paymethod)
	{
		switch($paymethod)
		{
			case 'VBank':
				return 'VA';
			case 'Card':
			case 'VCard':
				return 'CC';
			case 'HPP':
				return 'MP';
			case 'DirectBank':
				return 'IB';
			default:
				return '  ';
		}
	}

	function getBankName($code)
	{
		switch($code)
		{
			case "03" :
				return "기업은행";
				break;
			case "04" :
				return "국민은행";
				break;
			case "05" :
				return "외환은행";
				break;
			case "07" :
				return "수협중앙회";
				break;
			case "11" :
				return "농협중앙회";
				break;
			case "20" :
				return "우리은행";
				break;
			case "23" :
				return "SC제일은행";
				break;
			case "31" :
				return "대구은행";
				break;
			case "32" :
				return "부산은행";
				break;
			case "34" :
				return "광주은행";
				break;
			case "37" :
				return "전북은행";
				break;
			case "39" :
				return "경남은행";
				break;
			case "53" :
				return "한국씨티은행";
				break;
			case "71" :
				return "우체국";
				break;
			case "81" :
				return "하나은행";
				break;
			case "88" :
				return "통합신한은행(신한,조흥은행)";
				break;
			case "D1" :
				return "동양종합금융증권";
				break;
			case "D2" :
				return "현대증권";
				break;
			case "D3" :
				return "미래에셋증권";
				break;
			case "D4" :
				return "한국투자증권";
				break;
			case "D5" :
				return "우리투자증권";
				break;
			case "D6" :
				return "하이투자증권";
				break;
			case "D7" :
				return "HMC투자증권";
				break;
			case "D8" :
				return "SK증권";
				break;
			case "D9" :
				return "대신증권";
				break;
			case "DB" :
				return "굿모닝신한증권";
				break;
			case "DC" :
				return "동부증권";
				break;
			case "DD" :
				return "유진투자증권";
				break;
			case "DE" :
				return "메리츠증권";
				break;
			case "DF" :
				return "신영증권";
				break;
			default   :
				return "";
				break;
		}
	}
}
/* End of file cashpay.controller.php */
/* Location: ./modules/cashpay/cashpay.controller.php */
