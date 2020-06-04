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

		if($transaction_info->state != 1)
		{
			//입금 대기 상태가 아니면 커넥션을 종료합니다.
			echo "OK";
			Context::close();
			exit;
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

	/**
	 * @brief 이니시스 카드 결제 전체 취소
	 */
	function doCancleIt($in_args)
	{
		$oModuleModel = getModel('module');
		$oEpayModel = getModel('epay');
		$transaction_info = $oEpayModel->getTransactionByOrderSrl($in_args->order_srl);

		if($transaction_info->state == "A") return false;

		$sel_args = new stdClass;
		$sel_args->sort_index = "module_srl";
		$sel_args->page = 0;
		$sel_args->list_count = 1;
		$sel_args->page_count = 1;
		$output_sel = executeQueryArray('inipaystandard.getModuleList', $sel_args);
		foreach($output_sel->data as $n=>$pgval){
			$def_md_info = $pgval;
			break;
		}

		$ini_pg_info = $oModuleModel->getModuleInfoByModuleSrl($def_md_info->module_srl);

		if($ini_pg_info->ini_card_auto_cancle != "Y") return false;

		$reason = "관리자 취소";

		require_once('libs/INIStdPayUtil.php');
	
		$util = new INIStdPayUtil();
	
		$authMap = array();
		$iniapi_pay_key = $ini_pg_info->inipay_iniapikey;
		$authMap["type"] = "Refund";
		$authMap["mid"] = $ini_pg_info->inipay_mid;
		$authMap["paymethod"] = "Card";
		$authMap["timestamp"] = date("YmdHis");
		$authMap["clientIp"] = $_SERVER['SERVER_ADDR'];
		$authMap["tid"] = $in_args->pg_tid;
		$authMap["msg"] = $reason;
		$authMap["charset"] = 'UTF-8';
		$has_ori = $iniapi_pay_key.$authMap["type"].$authMap["paymethod"].$authMap["timestamp"].$authMap["clientIp"].$authMap["mid"].$authMap["tid"];
		$mKey = $util->makeHash($has_ori, "sha512");
		$authMap["hashData"] = $mKey;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://iniapi.inicis.com/api/v1/refund");
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($authMap));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		$chRs = curl_exec ($ch);
		$chCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if($chCode == 200)
		{
			$ini_result = json_decode($chRs);
			if(!$ini_result) return false;

			$u_args = new stdClass();
			$u_args->transaction_srl = $transaction_info->transaction_srl;
			$u_args->result_code = $ini_result->resultCode;
			$u_args->result_message = $ini_result->resultMsg;
			$u_args->pg_tid = $in_args->pg_tid;
			if($ini_result->resultCode == "00")
			{
				$u_args->state = "A";
				$this->insertCardCancleLog($transaction_info,"A");
			}
			else
			{
				$u_args->state = $transaction_info->state;
			}

			$extra_vars = unserialize($transaction_info->extra_vars);
			foreach($ini_result as $key => $val)
			{
				$extra_vars->{$key} = $val;
			}

			$u_args->extra_vars = serialize($extra_vars);
			$output = executeQuery('epay.updateTransaction', $u_args);
			ModuleHandler::triggerCall('inipaystandard.cancle', 'after', $transaction_info);
			if(!$output->toBool())
			{
				return false;
			}

			return true;

		}else{
			return false;
		}

		return false;
	}

	/**
	 * @brief 이니시스 카드 결제 부분 취소
	 */
	function doCanclePart($in_args)
	{
		$oModuleModel = getModel('module');
		$oEpayModel = getModel('epay');
		
		$transaction_info = $oEpayModel->getTransactionByOrderSrl($in_args->order_srl);

		$total_price = $transaction_info->payment_amount;
		$ca_output = executeQueryArray("inipaystandard.getCancleListByOrderSrl",$transaction_info);
		foreach ($ca_output->data as $key => $cancleInfo)
		{
			$total_price = $total_price - $cancleInfo->cancle_amount;
		}

		$re_price = (int)$total_price-(int)$in_args->cancle_part_price;

		$sel_args = new stdClass;
		$sel_args->sort_index = "module_srl";
		$sel_args->page = 0;
		$sel_args->list_count = 1;
		$sel_args->page_count = 1;
		$output_sel = executeQueryArray('inipaystandard.getModuleList', $sel_args);
		foreach($output_sel->data as $n=>$pgval){
			$def_md_info = $pgval;
			break;
		}

		$ini_pg_info = $oModuleModel->getModuleInfoByModuleSrl($def_md_info->module_srl);
		
		$reason = "관리자 취소";
		if(trim($in_args->cancle_desc) != "")
		{
			$reason = trim($in_args->cancle_desc);
		}

		$reason = cut_str($reason,40,"");
	
		require_once('libs/INIStdPayUtil.php');

		$util = new INIStdPayUtil();
	
		$authMap = array();
		$iniapi_pay_key = $ini_pg_info->inipay_iniapikey;
		$authMap["type"] = "PartialRefund";
		$authMap["mid"] = $ini_pg_info->inipay_mid;
		$authMap["paymethod"] = "Card";
		$authMap["timestamp"] = date("YmdHis");
		$authMap["clientIp"] = $_SERVER['SERVER_ADDR'];
		$authMap["tid"] = $transaction_info->pg_tid;
		$authMap["msg"] = $reason;
		$authMap["charset"] = 'UTF-8';
		$authMap["price"] = $in_args->cancle_part_price;
		$authMap["confirmPrice"] = $re_price;
		$authMap["currency"] = "WON";
		$has_ori = $iniapi_pay_key.$authMap["type"].$authMap["paymethod"].$authMap["timestamp"].$authMap["clientIp"].$authMap["mid"].$authMap["tid"].$authMap["price"].$authMap["confirmPrice"];
		$mKey = $util->makeHash($has_ori, "sha512");
		$authMap["hashData"] = $mKey;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://iniapi.inicis.com/api/v1/refund");
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($authMap));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		$chRs = curl_exec ($ch);
		$chCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		$part_result = new stdClass();
		$part_result->result = false;
		if($chCode == 200)
		{
			$ini_result = json_decode($chRs);
			if(!$ini_result)
			{
				return $part_result;
			}
			$part_result->result = false;
			$u_args = new stdClass();
			$u_args->transaction_srl = $transaction_info->transaction_srl;
			$u_args->result_code = $ini_result->resultCode;
			$u_args->result_message = $ini_result->resultMsg;
			$u_args->pg_tid = $transaction_info->pg_tid;

			if($ini_result->resultCode == "00")
			{
				$part_result->result = true;
				$transaction_info->ori_tid = $ini_result->prtcTid;
				$transaction_info->part_tid = $ini_result->tid;
				$transaction_info->part_remains_amount = $ini_result->prtcRemains;
				$transaction_info->part_cancle_amount = $ini_result->prtcPrice;
				$transaction_info->part_cancle_type = $ini_result->prtcType;
				$transaction_info->part_cancle_cnt = $ini_result->prtcCnt;
				$transaction_info->cancle_desc = $reason;
				$this->insertCardCancleLog($transaction_info,"P");
				ModuleHandler::triggerCall('inipaystandard.partCancle', 'after', $transaction_info);
				return $part_result;
			}
			else
			{
				$part_result->result = false;
				$part_result->result_desc = $u_args;
				return $part_result;
			}
			return $ini_result;
		}else{
			$part_result->result = false;
			$rs_end = new stdClass();
			$rs_end->result_code = $chCode;
			$rs_end->result_message = "CURL:".$chRs;
			$part_result->result_desc = $rs_end;
			return $ini_result;
		}

	}

	function insertCardCancleLog($log_args,$insert_type="A")
	{
		$log_args->cancle_type = $insert_type;
		unset($log_args->regdate);
		if($insert_type == "A")
		{
			$log_args->cancle_amount = $log_args->payment_amount;
			$log_args->cancle_desc = "관리자 취소";
		}
		else
		{
			$log_args->cancle_amount = $log_args->part_cancle_amount;
		}
		executeQueryArray("inipaystandard.insertCancleLog",$log_args);
	}
}