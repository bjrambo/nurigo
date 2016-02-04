<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  eposController
 * @author NURIGO(contact@nurigo.net)
 * @brief  eposController
 */
class eposController extends epos
{
	function processOrderInfo($strOrderInfo)
	{
		$oEpayModel = &getModel('epay');

		debugPrint('processOrderInfo');
		$xmlObj = simplexml_load_string($strOrderInfo);

		if(!isset($xmlObj->CAVALUE)) $this->stop('CAVALUE is null');
		$cavalue = $xmlObj->CAVALUE->__toString();

		$orderInfo = $xmlObj->ORDERINFO;
		if(!isset($orderInfo)) $this->stop('ORDERINFO is null');

		if(!isset($orderInfo->ORDERNUMBER)) $this->stop('ORDERNUMBER is null');
		$orderNumber = $orderInfo->ORDERNUMBER->__toString();
		debugPrint('order_srl : ' . $orderNumber);

		// get transaction information
		$transactionInfo = $oEpayModel->getTransactionByOrderSrl($orderNumber);
		debugPrint($transactionInfo);
		if(!$transactionInfo) $this->stop('cannot find transaction information');

		header(sprintf("Location: %s", $transactionInfo->return_url));
	}

	function processResult($strRsXML)
	{
		debugPrint('processResult');
		debugPrint($strRsXML);

		$oEpayController = &getController('epay');
		$oEpayModel = &getModel('epay');

		$xmlObj = simplexml_load_string($strRsXML);

		if(!isset($xmlObj->CAVALUE)) $this->stop('CAVALUE is null');
		$cavalue = $xmlObj->CAVALUE->__toString();

		$orderInfo = $xmlObj->ORDERINFO;
		if(!isset($orderInfo)) $this->stop('ORDERINFO is null');

		if(!isset($orderInfo->ORDERNUMBER)) $this->stop('ORDERNUMBER is null');
		$orderNumber = $orderInfo->ORDERNUMBER->__toString();

		if(!isset($orderInfo->AMOUNT)) $this->stop('AMOUNT is null');
		$amount = intval($orderInfo->AMOUNT->__toString());

		$authInfo = $xmlObj->AUTHINFO;
		if(!isset($authInfo)) $this->stop('AUTHINFO is null');
		// AUTHSTATUS
		if(!isset($authInfo->AUTHSTATUS)) $this->stop('AUTHSTATUS is null');
		$authStatus = $authInfo->AUTHSTATUS->__toString();
		// AUTHCODE
		if(!isset($authInfo->AUTHCODE)) $this->stop('AUTHCODE is null');
		$authCode = $authInfo->AUTHCODE->__toString();
		// AUTHMSG
		if(!isset($authInfo->AUTHMSG)) $this->stop('AUTHMSG is null');
		$authMsg = $authInfo->AUTHMSG->__toString();
		// AUTHTIME
		if(!isset($authInfo->AUTHTIME)) $this->stop('AUTHTIME is null');
		$authTime = $authInfo->AUTHTIME->__toString();


		debugPrint('order number : ' . $orderNumber . ' , status : ' . $authStatus);

		// get transaction information
		$transactionInfo = $oEpayModel->getTransactionByOrderSrl($orderNumber);
		if(!$transactionInfo) $this->stop('cannot find transaction information');

		$oModuleModel = &getModel('module');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($transactionInfo->plugin_srl);
		debugPrint($module_info);

		// check amount
		if($amount != $transactionInfo->payment_amount) $this->stop('AMOUNT dismatch');
		debugPrint($transactionInfo);

        $output = $oEpayController->beforePayment($transactionInfo);
		debugPrint($output);
		if(!$output->toBool()) return $output;

		// result code
		$state = '3';
		if($authStatus == '0000') $state = '2';

		$output = new Object();
		$output->add('transaction_srl', $transactionInfo->transaction_srl);
		$output->add('state', $state); // not completed
		$output->add('payment_method', 'CC');
		$output->add('payment_amount', $transactionInfo->payment_amount);
		$output->add('result_code', $authStatus);
		$output->add('result_message', $authMsg);
		$output->add('pg_tid', $authCode.':'.$authTime);

		// afterPayment will call an after trigger
		$output = $oEpayController->afterPayment($output);
		if(!$output->toBool()) return $output;

		// set return_url
		$extra_vars = unserialize($transactionInfo->extra_vars);
		$extra_vars->return_url = $output->get('return_url');
		$oEpayController->updateExtraVars($transactionInfo->transaction_srl, serialize($extra_vars));

		$urlInfo = parse_url(getFullUrl(''));
		$host = $urlInfo['host'];
	
		$strInput = $host . $module_info->cubkey;
		$strMD5Hash = md5($strInput);	
		$strXML = sprintf("<?xml version='1.0' encoding='UTF-8'?><MERCHANTXML><CAVALUE>%s</CAVALUE><RETURL>%s/modules/epos/orderinfo.php</RETURL></MERCHANTXML>", $strMD5Hash, getFullUrl(''));
		echo $strXML;
		exit();
	}

}
/* End of file epos.controller.php */
/* Location: ./modules/epos/epos.controller.php */
