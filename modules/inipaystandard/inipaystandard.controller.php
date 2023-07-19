<?php

/**
 * @class  inipaystandardController
 * @author CONORY (https://www.conory.com)
 * @brief Controller class of inipaystandard modules
 */
class inipaystandardController extends inipaystandard
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	/**
	 * @brief default action ( 입금내역통보 URL : http://domain/mid )
	 */
	function procInipay()
	{
		if(Context::get('no_oid'))
		{
			return $this->processReport(Context::get('no_oid'), Context::get('amt_input'));
		}
	}

	/**
	 * @brief module Handler 트리거
	 **/
	function triggerModuleHandler(&$obj)
	{
		if($obj->act == 'procInipaystandardDoIt' && $_GET['mid'])
		{
			$obj->mid = $_GET['mid'];

			Context::set('mid', $obj->mid);
		}

		return $this->makeObject();
	}

	/**
	 * @brief 결제 처리
	 **/
	function procInipaystandardDoIt()
	{
		if(!$_SESSION['inipaystandard']['transaction_srl'])
		{
			return $this->makeObject(-1, 'msg_invalid_request');
		}

		$oEpayController = getController('epay');

		$vars = Context::getRequestVars();
		$vars->transaction_srl = $_SESSION['inipaystandard']['transaction_srl'];

		//결제 실패시
		if(strcmp("0000", $vars->resultCode) !== 0)
		{
			$args = new stdClass();
			$args->transaction_srl = $vars->transaction_srl;
			$args->result_message = $vars->resultMsg;
			$args->result_code = $vars->resultCode;
			$args->state = 3;

			$output = executeQuery('epay.updateTransaction', $args);
			if(!$output->toBool())
			{
				return $output;
			}

			return $this->makeObject(-1, '결제가 취소되었습니다.');
		}
		else if(!$vars->authUrl || !$vars->authToken)
		{
			return $this->makeObject(-1, 'msg_invalid_request');
		}

		$output = $oEpayController->beforePayment($vars);
		if(!$output->toBool())
		{
			return $output;
		}

		require_once('libs/INIStdPayUtil.php');
		require_once('libs/HttpClient.php');

		$util = new INIStdPayUtil();
		$httpUtil = new HttpClient();

		$timestamp = $util->getTimestamp();

		$signParam = array();
		$signParam["authToken"] = $vars->authToken;
		$signParam["timestamp"] = $timestamp;
		$signature = $util->makeSignature($signParam);

		$authMap = array();

		if($this->module_info->ini_payment_test_mode == 'Y')
		{
			$authMap["mid"] = 'INIpayTest';
		}
		else
		{
			$authMap["mid"] = $this->module_info->inipay_mid;
		}

		$authMap["authToken"] = $vars->authToken;
		$authMap["signature"] = $signature;
		$authMap["timestamp"] = $timestamp;
		$authMap["charset"] = 'UTF-8';
		$authMap["format"] = 'JSON';
		$authMap["price"] = $_SESSION['inipaystandard']['price'];

		//결제 인증
		$authResultString = '';
		if($httpUtil->processHTTP($vars->authUrl, $authMap))
		{
			$authResultString = $httpUtil->body;
		}
		else
		{
			echo "Http Connect Error\n";
			echo $httpUtil->errormsg;
			throw new Exception("Http Connect Error");
		}

		//인증 결과
		$resultMap = json_decode($authResultString, true);

		//성공
		if(strcmp("0000", $resultMap["resultCode"]) == 0)
		{
			$payArgs = $this->makeObject(0, $resultMap["resultMsg"]);

			//가상계좌
			if($this->getPaymethod($resultMap["payMethod"]) == 'VA')
			{
				$payArgs->add('state', '1');
			}
			else
			{
				$payArgs->add('state', '2');
			}
		}
		//실패
		else
		{
			$payArgs = $this->makeObject(-1, $resultMap["resultMsg"]);
			$payArgs->add('state', '3');
		}

		$payArgs->add('transaction_srl', $_SESSION['inipaystandard']['transaction_srl']);
		$payArgs->add('payment_method', $this->getPaymethod($resultMap["payMethod"]));
		$payArgs->add('payment_amount', $resultMap["TotPrice"]);
		$payArgs->add('result_code', $resultMap["resultCode"]);
		$payArgs->add('result_message', $resultMap["resultMsg"]);
		$payArgs->add('pg_tid', $resultMap["tid"]);

		//가상계좌
		if($this->getPaymethod($resultMap["payMethod"]) == 'VA')
		{
			$payArgs->add('vact_num', $resultMap["VACT_Num"]);
			$payArgs->add('vact_bankname', $this->getBankName($resultMap["VACT_BankCode"]));
			$payArgs->add('vact_bankcode', $resultMap["VACT_BankCode"]);
			$payArgs->add('vact_name', $resultMap["VACT_Name"]);
			$payArgs->add('vact_inputname', $resultMap["VACT_InputName"]);
			$payArgs->add('vact_date', $resultMap["VACT_Date"]);
			$payArgs->add('vact_time', $resultMap["VACT_Time"]);
		}

		$output = $oEpayController->afterPayment($payArgs);
		if(!$output->toBool())
		{
			//DB 에러시 결제 취소
			if(!$httpUtil->processHTTP($vars->netCancel, $authMap))
			{
				echo "Http Connect Error\n";
				echo $httpUtil->errormsg;
				throw new Exception("Http Connect Error");
			}
			return $output;
		}

		$return_url = $output->get('return_url');
		if($return_url)
		{
			$this->setRedirectUrl($return_url);
		}

		unset($_SESSION['inipaystandard']);
	}

	/**
	 * @brief 가상계좌 입금시 처리
	 */
	function processReport($order_srl, $amount)
	{
		$oEpayModel = getModel('epay');
		$transaction_info = $oEpayModel->getTransactionByOrderSrl($order_srl);

		if(!$transaction_info)
		{
			return $this->makeObject(-1, 'could not find transaction');
		}

		// PG 서버에서 보냈는지 IP 체크 (보안)
		$pgIpArray = array(
			'203.238.37.3',
			'203.238.37.15',
			'203.238.37.16',
			'203.238.37.25',
			'39.115.212.9',
		);
		if(!in_array($_SERVER['REMOTE_ADDR'], $pgIpArray))
		{
			return $this->makeObject(-1, 'msg_invalid_request');
		}

		// 입금액 체크
		if($transaction_info->payment_amount == $amount)
		{
			$payArgs = $this->makeObject(0, 'success');
			$payArgs->add('state', '2');
			$payArgs->add('result_code', '0');
			$payArgs->add('result_message', 'success');
		}
		else
		{
			$payArgs = $this->makeObject(-1, '입금액이 일치하지않습니다.');
			$payArgs->add('state', '3');
			$payArgs->add('result_code', '1');
			$payArgs->add('result_message', '입금액이 일치하지않습니다.');
		}

		$payArgs->add('transaction_srl', $transaction_info->transaction_srl);
		$payArgs->add('payment_method', 'VA');
		$payArgs->add('payment_amount', $transaction_info->payment_amount);
		$payArgs->add('pg_tid', $transaction_info->pg_tid);
		$payArgs->add('vact_bankname', $transaction_info->vact_bankname);
		$payArgs->add('vact_num', $transaction_info->vact_num);
		$payArgs->add('vact_name', $transaction_info->vact_name);
		$payArgs->add('vact_inputname', $transaction_info->vact_inputname);

		$oEpayController = getController('epay');
		$output = $oEpayController->afterPayment($payArgs);

		if(!$output->toBool())
		{
			return $output;
		}

		Context::close();
		echo "OK";
		exit;
	}

	/**
	 * @brief 결제방식 코드변환
	 */
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

	/**
	 * @brief 이니시스 은행코드
	 */
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