<?php
class paypal extends EpayPlugin
{
	var $plugin = "paypal";
	var $plugin_srl;
	var $inicis_id;
	var $inicis_pass;
	var $site_url;
	var $logo_image;
	var $skin;

	function paypal()
	{
		parent::EpayPlugin();
	}

	function pluginInstall($args)
	{
	}

	function init(&$args)
	{
		$this->plugin_info = new StdClass();
		if ($args)
		{
			foreach ($args as $key=>$val)
			{
				$this->plugin_info->{$key} = $val;
			}
		}
		if ($args->extra_var)
		{
			foreach ($args->extra_var as $key=>$val)
			{
				$this->plugin_info->{$key} = $val->value;
			}
		}
		if (!$this->plugin_info->currency_code) $this->plugin_info->currency_code = 'USD';
		if(!$this->plugin_info->minimum_transactions) $this->plugin_info->minimum_transactions = 0;
		Context::set('plugin_info', $this->plugin_info);

		define('API_USERNAME', $this->plugin_info->api_username);
		define('API_PASSWORD', $this->plugin_info->api_password);
		define('API_SIGNATURE', $this->plugin_info->api_signature);
		define('API_ENDPOINT', $this->plugin_info->api_endpoint);
		define('SUBJECT', '');
		define('USE_PROXY', FALSE);
		define('PROXY_HOST', '127.0.0.1');
		define('PROXY_PORT', '808');
		define('PAYPAL_URL', $this->plugin_info->paypal_url);
		define('VERSION', '65.1');
		define('ACK_SUCCESS', 'SUCCESS');
		define('ACK_SUCCESS_WITH_WARNING', 'SUCCESSWITHWARNING');
	}

	function getFormData($args)
	{
		// check transaction count
		if($this->plugin_info->minimum_transactions)
		{
			$logged_info = Context::get('logged_info');
			if($logged_info)
			{
				$oEpayModel = &getModel('epay');
				$transaction_count = $oEpayModel->getTransactionCountByMemberSrl($logged_info->member_srl);
				if($transaction_count < $this->plugin_info->minimum_transactions) return new Object(0, 'Minimum transactions required');
			}
		}
		
		if (!$args->price) return new Object(0,'No input of price');
		$oTemplate = &TemplateHandler::getInstance();
		$tpl_path = _XE_PATH_."modules/epay/plugins/paypal/tpl";
		$tpl_file = 'formdata.html';
		Context::set('module_srl', $args->module_srl);
		Context::set('epay_module_srl', $args->epay_module_srl);
		Context::set('plugin_srl', $this->plugin_info->plugin_srl);
		Context::set('script_call_before_submit', $args->script_call_before_submit);
		Context::set('join_form', $args->join_form);

		if($this->plugin_info->conversion_rate)
		{
			$price = round($args->price * $this->plugin_info->conversion_rate, 2);
			Context::set('price', $price);
		}

		$html = $oTemplate->compile($tpl_path, $tpl_file);
		$output = new Object();
		$output->data = $html;
		return $output;
	}

	function processReview($args)
	{
		require_once($this->module_path.'CallerService.php');
		$cs = new CallerService();

		$currencyCodeType=$this->plugin_info->currency_code;
		$paymentType='Sale';
		$L_NAME0 = urlencode($args->order_title);
		$L_AMT0 = $args->price;
		if($this->plugin_info->conversion_rate)
		{
			$L_AMT0 = round($args->price * $this->plugin_info->conversion_rate, 2);
		}
		$L_QTY0 = '1';
		$L_NUMBER0 = $args->order_srl;
		$returnURL = urlencode(getNotEncodedFullUrl('','module','epay','act','procEpayDoPayment','epay_module_srl',$args->epay_module_srl,'plugin_srl',$args->plugin_srl,'order_srl',$args->order_srl,'currencyCodeType',$currencyCodeType,'paymentType',$paymentType));
		$cancelURL = urlencode(getNotEncodedFullUrl(''));

		$itemamt = 0.00;
		$itemamt = $L_QTY0*$L_AMT0;
		$amt = $itemamt;
		$maxamt= $amt+25.00;
		$nvpstr="";
	   
		$nvpstr="&L_NAME0=".$L_NAME0."&L_AMT0=".$L_AMT0."&L_QTY0=".$L_QTY0."&MAXAMT=".(string)$maxamt."&AMT=".(string)$amt."&ITEMAMT=".(string)$itemamt."&CALLBACKTIMEOUT=4&L_NUMBER0=".$L_NUMBER0."&L_DESC0=".$L_NAME0."&RETURNURL=".$returnURL."&CANCELURL=".$cancelURL ."&CURRENCYCODE=".$currencyCodeType."&PAYMENTACTION=".$paymentType;

		if($this->plugin_info->logo_image)
		{
			$nvpstr .= "&HDRIMG=" . getFullSiteUrl().$this->plugin_info->logo_image;
		}
	   
		$nvpstr = $nvpHeader.$nvpstr;
		debugPrint('$nvpstr');
		debugPrint($nvpstr);
	   
		/* Make the call to PayPal to set the Express Checkout token
		If the API call succeded, then redirect the buyer to PayPal
		to begin to authorize payment.  If an error occured, show the
		resulting errors
		*/
		$resArray=$cs->hash_call("SetExpressCheckout",$nvpstr);
		$_SESSION['reshash']=$resArray;

		$ack = strtoupper($resArray["ACK"]);
		Context::set('resArray', $resArray);

		if($ack=="SUCCESS"){
			// Redirect to paypal.com here
			$token = urldecode($resArray["TOKEN"]);
			$payPalURL = PAYPAL_URL.$token;
			$output = new Object();
			$output->add('return_url', $payPalURL);
			return $output;
		} else {
			$errorCode= $_SESSION['curl_error_no'] ;
			$errorMessage=$_SESSION['curl_error_msg'] ;	
			Context::set('errorCode',$errorCode);
			Context::set('errorMessage',$errorMessage);
			$oTemplate = &TemplateHandler::getInstance();
			$tpl_path = _XE_PATH_."modules/epay/plugins/paypal/tpl";
			$tpl_file = 'api_error.html';
			$html = $oTemplate->compile($tpl_path, $tpl_file);
			$output = new Object(-1);
			$output->data = $html;
			$output->setMessage($html);
			return $output;
		}
		return new Object();
	}

	function processPayment(&$args)
	{
		$pp_ret = new Object();

		require_once($this->module_path.'CallerService.php');
		$cs = new CallerService();

		$token = urlencode(Context::get('token'));

		$nvpstr="&TOKEN=".$token;

		$nvpstr = $nvpHeader.$nvpstr;

		$resArray=$cs->hash_call("GetExpressCheckoutDetails",$nvpstr);

		if(isset($resArray['L_NAME0'])) $args->epay_order_title = $resArray['L_NAME0'];

		$_SESSION['reshash']=$resArray;
		$ack = strtoupper($resArray["ACK"]);

		if($ack == 'SUCCESS' || $ack == 'SUCCESSWITHWARNING'){
			$_SESSION['token']=Context::get('token');
			$_SESSION['payer_id'] = Context::get('PayerID');

			$_SESSION['paymentAmount']=Context::get('paymentAmount');
			$_SESSION['currCodeType']=Context::get('currencyCodeType');
			$_SESSION['paymentType']=Context::get('paymentType');

			$resArray=$_SESSION['reshash'];
			$_SESSION['TotalAmount']= $resArray['AMT'] + $resArray['SHIPDISCAMT'];
			Context::set('resArray', $resArray);

			// do payment
			$token =urlencode( $_SESSION['token']);
			$paymentAmount =urlencode ($_SESSION['TotalAmount']);
			$paymentType = urlencode($_SESSION['paymentType']);
			$currCodeType = urlencode($_SESSION['currCodeType']);
			$payerID = urlencode($_SESSION['payer_id']);
			$serverName = urlencode($_SERVER['SERVER_NAME']);

			$nvpstr='&TOKEN='.$token.'&PAYERID='.$payerID.'&PAYMENTACTION='.$paymentType.'&AMT='.$paymentAmount.'&CURRENCYCODE='.$currCodeType.'&IPADDRESS='.$serverName ;

			$resArray=$cs->hash_call("DoExpressCheckoutPayment",$nvpstr);
			Context::set('resArray', $resArray);

			$ack = strtoupper($resArray["ACK"]);


			$pp_ret->add('payment_method', 'PP');
			$pp_ret->add('payment_amount', $_SESSION['TotalAmount']);
			$pp_ret->add('result_code', $resArray['REASONCODE']);
			$pp_ret->add('result_message', $resArray['ACK']);
			$pp_ret->add('pg_tid', $resArray['TRANSACTIONID']);
			$pp_ret->add('state', '2');

			if($ack != 'SUCCESS' && $ack != 'SUCCESSWITHWARNING'){
				$_SESSION['reshash']=$resArray;

				$oTemplate = &TemplateHandler::getInstance();
				$tpl_path = _XE_PATH_."modules/epay/plugins/paypal/tpl";
				$tpl_file = 'api_error.html';
				$obj = new Object(-1);
				$obj->add('state', '3');
				$obj->add('result_code', $ack);
				$obj->add('result_message', $ack);
				$obj->data = $oTemplate->compile($tpl_path,$tpl_file);
				return $obj;

/*
				$location = getNotEncodedUrl('','module','epay','act','dispEpayApiError');
				header("Location: $location");
				return;
*/
			}

		} else  {
			Context::set('resArray', $resArray);
			$oTemplate = &TemplateHandler::getInstance();
			$tpl_path = _XE_PATH_."modules/epay/plugins/paypal/tpl";
			$tpl_file = 'api_error.html';
			$obj = new Object(-1, $ack);
			$obj->add('state', '3');
			$obj->add('result_code', $ack);
			$obj->add('result_message', $ack);
			$obj->data = $oTemplate->compile($tpl_path,$tpl_file);
			return $obj;
		}
		return $pp_ret;
	}
}
/* End of file payplus6.plugin.php */
/* Location: ./modules/epay/plugins/payplus6/payplus6.plugin.php */
