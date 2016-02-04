<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  inipaymobileController
 * @author NURIGO(contact@nurigo.net)
 * @brief  inipaymobileController
 */
class inipaymobileController extends inipaymobile
{
	/**
	 * n_page에 transaction_srl값이 들어오면 NEXT_URL 처리로,
	 * r_page에 transaction_srl값이 들어오면 RETURN_URL 처리로 한다.
	 */
	function procInipaymobile()
	{
		$vars = Context::getRequestVars();
		debugPrint('procInipaymobile');
		debugPrint($vars);
		// 가상계좌 번호 발급, 안심클릭 처리
		if(Context::get('n_page'))
		{
			return $this->processNextUrl(Context::get('n_page'));
		}
		// 결과 페이지
		if(Context::get('r_page'))
		{
			return $this->processReturnUrl(Context::get('r_page'));
		}
/*
		// 가상계좌 입금시 inicis 서버에서 호출
		if(Context::get('no_oid'))
		{
			return $this->processReport(Context::get('no_oid'));
		}
*/
		// ISP, 가상계좌 입금처리, 실시간계좌이체시 inicis 서버에서 호출
		if(in_array(Context::get('P_TYPE'), array('ISP','BANK','VBANK')))
		{
			return $this->processNotiUrl();
		}
	}

	/**
	 * inipaymobile P_NEXT_URL 페이지 처리를 위한 코드
	 * 가상계좌, 안심클릭시 처리, P_REQ_URL에 POST로 P_TID와 P_MID를 넘겨줘야 결제요청이 완료됨
	 */
	function processNextUrl($transaction_srl)
	{
		$oEpayController = &getController('epay');
		$oEpayModel = &getModel('epay');


		$vars = Context::getRequestVars();
		debugPrint('processNextUrl');
		debugPrint($vars);
		// P_NOTI에 plugin_srl, epay_module_srl 등을 담고 있음
		parse_str($vars->P_NOTI, $output);
		foreach($output as $key=>$val)
		{
			Context::set($key, $val);
		}
		$vars->transaction_srl = $transaction_srl;
		$transaction_info = $oEpayModel->getTransactionInfo($transaction_srl);

		// before trigger
		$output = $oEpayController->beforePayment($vars);
		if(!$output->toBool()) return $output;

		$vars->P_RMESG1 = iconv('EUC-KR','UTF-8',$vars->P_RMESG1);

		// P_TID에 값이 없으면 취소되었음
		if(!$vars->P_TID)
		{
			$return_url = getNotEncodedUrl('','mid',Context::get('mid'),'act','dispNcartOrderComplete','order_srl',Context::get('order_srl'));
			$this->setRedirectUrl($return_url);
			return;
		}

		$post_data = array('P_TID'=>$vars->P_TID,'P_MID'=>$this->module_info->inicis_id);
		$response = $this->getRemoteResource($vars->P_REQ_URL, null, 3, 'POST', 'application/x-www-form-urlencoded',  array(), array(), $post_data);
		parse_str($response, $output);
		foreach($output as $key=>$val)
		{
			Context::set($key, $val);
		}
		$P_RMESG1 = iconv('EUC-KR','UTF-8',$output['P_RMESG1']);
		$P_VACT_NUM = Context::get('P_VACT_NUM');
		$P_VACT_DATE = Context::get('P_VACT_DATE');
		$P_VACT_TIME = Context::get('P_VACT_TIME');
		$P_VACT_NAME = iconv('EUC-KR','UTF-8', Context::get('P_VACT_NAME'));
		$P_VACT_BANK_CODE = trim(Context::get('P_VACT_BANK_CODE'));

		// inipaymobile_pass = TRUE로 해주어서 inipaymobile에서 결제처리되도록 함
		$_SESSION['inipaymobile_pass'] = TRUE;

		$output = new Object();
		$output->add('transaction_srl', $transaction_srl);
		if($vars->P_STATUS == '00')
		{
			if($transaction_info->payment_method == 'CC') $output->add('state', '2');
			if($transaction_info->payment_method == 'VA') $output->add('state', '1'); // not completed
		}
		else
		{
			$output->add('state', '3'); // error
		}
		$output->add('payment_method', $transaction_info->payment_method);
		$output->add('payment_amount', $transaction_info->payment_amount);
		$output->add('result_code', '0');
		$output->add('result_message', $P_RMESG1);
		$output->add('pg_tid', $vars->P_TID);
		$output->add('vact_bankname', $this->getBankName($P_VACT_BANK_CODE));
		$output->add('vact_num', $P_VACT_NUM);
		$output->add('vact_name', $P_VACT_NAME);
		$output->add('vact_inputname', '');
		debugPrint('afterPayment args');
		debugPrint($output);

		// afterPayment will call an after trigger
		$output = $oEpayController->afterPayment($output);
		if(!$output->toBool()) return $output;
		$return_url = $output->get('return_url');
		if($return_url) $this->setRedirectUrl($return_url);
	}

	/**
	 * inipaymobile P_RETURN_URL 페이지 처리를 위한 코드
	 * ISP 결제시 r_page에 order_srl이 담겨져옴, 결제처리는 P_NOTI_URL이 호출되므로 여기서는 그냥 결과만 보여줌
	 */
	function processReturnUrl($transaction_srl)
	{
		$oEpayModel = &getModel('epay');
		$vars = Context::getRequestVars();

		$transaction_info = $oEpayModel->getTransactionInfo($transaction_srl);

		$return_url = getNotEncodedUrl('','mid',Context::get('mid'),'act','dispNcartOrderComplete','order_srl',$transaction_info->order_srl);
		$this->setRedirectUrl($return_url);
	}

	/**
	 * 가상계좌 입금시 처리
	 */
	function processReport($order_srl)
	{
		$oEpayModel = &getModel('epay');
		$transaction_info = $oEpayModel->getTransactionByOrderSrl($order_srl);
		if(!$transaction_info) return new Object(-1, 'could not find transaction');

		$inipaymobile_home = sprintf(_XE_PATH_."files/epay/%s", $transaction_info->transaction_srl);
	
		$TEMP_IP = $_SERVER["REMOTE_ADDR"];
		$PG_IP  = substr($TEMP_IP,0, 10);

		//PG에서 보냈는지 IP로 체크
		if( $PG_IP != "203.238.37" && $PG_IP != "210.98.138" )  {
			return new Object(-1, 'msg_invalid_request');
		}

		/*
		$msg_id = $msg_id;             //메세지 타입
		$no_tid = $no_tid;             //거래번호
		$no_oid = $no_oid;             //상점 주문번호
		$id_merchant = $id_merchant;   //상점 아이디
		$cd_bank = $cd_bank;           //거래 발생 기관 코드
		$cd_deal = $cd_deal;           //취급 기관 코드
		$dt_trans = $dt_trans;         //거래 일자
		$tm_trans = $tm_trans;         //거래 시간
		$no_msgseq = $no_msgseq;       //전문 일련 번호
		$cd_joinorg = $cd_joinorg;     //제휴 기관 코드

		$dt_transbase = $dt_transbase; //거래 기준 일자
		$no_transeq = $no_transeq;     //거래 일련 번호
		$type_msg = $type_msg;         //거래 구분 코드
		$cl_close = $cl_close;         //마감 구분코드
		$cl_kor = $cl_kor;             //한글 구분 코드
		$no_msgmanage = $no_msgmanage; //전문 관리 번호
		$no_vacct = $no_vacct;         //가상계좌번호
		$amt_input = $amt_input;       //입금금액
		$amt_check = $amt_check;       //미결제 타점권 금액
		$nm_inputbank = $nm_inputbank; //입금 금융기관명
		$nm_input = $nm_input;         //입금 의뢰인
		$dt_inputstd = $dt_inputstd;   //입금 기준 일자
		$dt_calculstd = $dt_calculstd; //정산 기준 일자
		$flg_close = $flg_close;       //마감 전화
		*/

		/*
		//가상계좌채번시 현금영수증 자동발급신청시에만 전달
		$dt_cshr      = $dt_cshr;       //현금영수증 발급일자
		$tm_cshr      = $tm_cshr;       //현금영수증 발급시간
		$no_cshr_appl = $no_cshr_appl;  //현금영수증 발급번호
		$no_cshr_tid  = $no_cshr_tid;   //현금영수증 발급TID
		*/


		$logfile = fopen($inipaymobile_home."/log/vbank_" . date("Ymd") . ".log", "a+");
		$vars = Context::getRequestVars();
		foreach ($vars as $key=>$val) {
			fwrite( $logfile,$key." : ".$val."\n");
		}

		/*
		$output = $this->processPayment(Context::get('no_oid'), Context::get('amt_input'));
		if (!$output->toBool()) return $output;
		*/

		fwrite( $logfile,"************************************************\n\n");
		fclose( $logfile );

		//위에서 상점 데이터베이스에 등록 성공유무에 따라서 성공시에는 "OK"를 이니시스로
		//리턴하셔야합니다. 아래 조건에 데이터베이스 성공시 받는 FLAG 변수를 넣으세요
		//(주의) OK를 리턴하지 않으시면 이니시스 지불 서버는 "OK"를 수신할때까지 계속 재전송을 시도합니다
		//기타 다른 형태의 PRINT( echo )는 하지 않으시기 바랍니다

		$output = new Object();
		$output->order_srl = Context::get('no_oid');
		$output->amount = Context::get('amt_input');
		if ($output->amount == $transaction_info->payment_amount)
		{
			$output->add('state', '2'); // completed
			$output->add('result_code', '0');
			$output->add('result_message', 'success');
		}
		else
		{
			$output->setError(-1);
			$output->setMessage('amount does not match');
			$output->add('state', '3'); // failed
			$output->add('result_code', '1');
			$output->add('result_message', 'amount does not match');
		}
		$output->add('transaction_srl', $transaction_info->transaction_srl);
		$output->add('payment_method', 'VA');
		$output->add('payment_amount', $transaction_info->payment_amount);
		$output->add('pg_tid', $transaction_ifno->pg_tid);
		$output->add('vact_bankname', $transaction_info->vact_bankname);
		$output->add('vact_num', $transaction_info->vact_num);
		$output->add('vact_name', $transaction_info->vact_name);
		$output->add('vact_inputname', $transaction_info->vact_inputname);

		// afterPayment will call an after trigger
		$oEpayController = &getController('epay');
		$output = $oEpayController->afterPayment($output);
		if(!$output->toBool()) return $output;
		// OK를 출력하고 끝내야 한다
		echo "OK";
		exit(0);
	}

	/**
	 * @brief 가상계좌 채번(P_STATUS:00), 가상계좌 입금처리(P_STATUS:02), 계좌이체 결과처리
	 * inicis  서버에서 P_NOTI_URL 에 입력된 url로 호출해 줍니다.
	 */
	function processNotiUrl()
	{
		debugPrint('processNotiUrl');
		$oEpayController = &getController('epay');
		$vars = Context::getRequestVars();

		// 가상계좌 채번 후 이니시스 서버에서 한번 더 호출되는데 그냥 return 하자. 도대체 왜 호출해주는거지?
		if($vars->P_TYPE=='VBANK' && $vars->P_STATUS=='00') return;

		// P_NOTI에 transaction_srl, order_srl, epay_module_srl 등을 담고 있음
		parse_str($vars->P_NOTI, $output);
		debugPrint($output);
		foreach($output as $key=>$val)
		{
			Context::set($key, $val);
		}

		$inipaymobile_home = sprintf(_XE_PATH_."files/epay/%s", Context::get('transaction_srl'));
		debugPrint($inipaymobile_home);
		$logfile = fopen($inipaymobile_home."/log/vbank_" . date("Ymd") . ".log", "a+");
		$vars = Context::getRequestVars();
		debugPrint($vars);
		foreach ($vars as $key=>$val) {
			fwrite( $logfile,$key." : ".$val."\n");
		}
		fwrite( $logfile,"************************************************\n\n");
		fclose( $logfile );


		// beforePayment 에서는 transaction_srl값이 필수임.
		$transaction_srl = Context::get('transaction_srl');
		$vars->transaction_srl = $transaction_srl;
		$output = $oEpayController->beforePayment($vars);
		debugPrint($output);
		if(!$output->toBool()) return $output;

		//PG에서 보냈는지 IP로 체크
		$PGIP = $_SERVER['REMOTE_ADDR'];
		debugPrint('$PGIP');
		debugPrint($PGIP);
		if(($PGIP != "211.219.96.165" && $PGIP != "118.129.210.25") && !$_SESSION['inipaymobile_pass'])
		{
			$obj = new Object(-1, 'msg_invalid_request');
			$obj->data = '정상적인 경로로 호출되지 않았습니다.';
			return $obj;
		}

		/*
		// 이니시스 NOTI 서버에서 받은 Value
		$P_TID;				// 거래번호
		$P_MID;				// 상점아이디
		$P_AUTH_DT;			// 승인일자
		$P_STATUS;			// 거래상태 (00:성공, 01:실패) , 가상계좌일 때 02:입금통보
		$P_TYPE;			// 지불수단
		$P_OID;				// 상점주문번호
		$P_FN_CD1;			// 금융사코드1
		$P_FN_CD2;			// 금융사코드2
		$P_FN_NM;			// 금융사명 (은행명, 카드사명, 이통사명)
		$P_AMT;				// 거래금액
		$P_UNAME;			// 결제고객성명
		$P_RMESG1;			// 결과코드
		$P_RMESG2;			// 결과메시지
		$P_NOTI;			// 노티메시지(상점에서 올린 메시지)
		$P_AUTH_NO;			// 승인번호
		 */

		$P_TID = Context::get('P_TID');
		$P_MID = Context::get('P_MID');
		$P_AUTH_DT = Context::get('P_AUTH_DT');
		$P_STATUS = Context::get('P_STATUS');
		$P_TYPE = Context::get('P_TYPE');
		$P_OID = Context::get('P_OID');
		$P_FN_CD1 = Context::get('P_FN_CD1');
		$P_FN_CD2 = Context::get('P_FN_CD2');
		$P_FN_NM = Context::get('P_FN_NM');
		$P_AMT = Context::get('P_AMT');
		$P_UNAME = Context::get('P_UNAME');
		$P_RMESG1 = iconv('EUC-KR','UTF-8', Context::get('P_RMESG1'));
		$P_RMESG2 = iconv('EUC-KR','UTF-8', Context::get('P_RMESG2'));
		$P_NOTI = Context::get('P_NOTI');
		$P_AUTH_NO = Context::get('P_AUTH_NO');
		$P_VACT_NUM = Context::get('P_VACT_NUM');
		$P_VACT_DATE = Context::get('P_VACT_DATE');
		$P_VACT_TIME = Context::get('P_VACT_TIME');
		$P_VACT_NAME = iconv('EUC-KR','UTF-8', Context::get('P_VACT_NAME'));
		$P_VACT_BANK_CODE = trim(Context::get('P_VACT_BANK_CODE'));


		/***********************************************************************************
		 ' 위에서 상점 데이터베이스에 등록 성공유무에 따라서 성공시에는 "OK"를 이니시스로 실패시는 "FAIL" 을
		 ' 리턴하셔야합니다. 아래 조건에 데이터베이스 성공시 받는 FLAG 변수를 넣으세요
		 ' (주의) OK를 리턴하지 않으시면 이니시스 지불 서버는 "OK"를 수신할때까지 계속 재전송을 시도합니다
		 ' 기타 다른 형태의 echo "" 는 하지 않으시기 바랍니다
		'***********************************************************************************/

		$args = new Object(0, $P_RMESG1 . ' ' . $P_RMESG2);
		//WEB 방식의 경우 가상계좌 채번 결과 무시 처리
		//(APP 방식의 경우 해당 내용을 삭제 또는 주석 처리 하시기 바랍니다.)
	
		// ISP, 실시간계좌이체 일 때 성공 00, 가상계좌일 때 입금통보 02
		if(in_array($P_TYPE, array('ISP','BANK')))
		{
		
			if($P_STATUS=='00')
			{
				$args->add('state', '2'); // completed (success)
			}
			else
			{
				$args->add('state', '3'); // failed
			}
			$args->add('transaction_srl', Context::get('transaction_srl'));
			$args->add('payment_method', $this->getPaymethod($P_TYPE));
			$args->add('payment_amount', $P_AMT);
			$args->add('result_code', $P_STATUS);
			$args->add('result_message', $P_RMESG1);
			$args->add('vact_num', $P_VACT_NUM);
			$args->add('vact_bankname', $this->getBankName($P_VACT_BANK_CODE));
			$args->add('vact_bankcode', $P_VACT_BANK_CODE);
			$args->add('vact_name', $P_VACT_NAME);
			$args->add('vact_inputname', '');
			$args->add('vact_regnum', '');
			$args->add('vact_date', $P_VACT_DATE); // 입금마감 일자
			$args->add('vact_time', $P_VACT_TIME); // 입금마감 시간
			$args->add('pg_tid', $P_TID);
			debugPrint($args);

			// afterPayment will call an after trigger
			$output = $oEpayController->afterPayment($args);
			if(!$output->toBool()) return $output;
			$return_url = $output->get('return_url');
			if($return_url) $this->setRedirectUrl($return_url);
		}
		else if($P_TYPE == 'VBANK')
		{
			// 가상계좌 입금통보는 P_STATUS가 02
			if($P_STATUS=='02')
			{
				$args->add('state', '2'); // completed (success)
				$args->add('result_message', 'success');
			}
			else
			{
				$args->add('state', '3'); // failed
				$args->add('result_message', 'failure');
			}
			$args->add('transaction_srl', Context::get('transaction_srl'));
			$args->add('payment_method', $this->getPaymethod($P_TYPE));
			$args->add('payment_amount', $P_AMT);
			$args->add('result_code', $P_STATUS);
			$args->add('pg_tid', $P_TID);

			// afterPayment will call an after trigger
			$output = $oEpayController->afterPayment($args);
			if(!$output->toBool())
			{
				echo "FAIL";
			}
			else
			{
				echo "OK";
			}
			exit(0);
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

	/**
	 * @brief 이니시스 결제방식코드를 epay코드로 변환
	 */
	function getPaymethod($paymethod)
	{
		switch ($paymethod) {
			case 'VBANK':
				return 'VA';
			case 'ISP':
				return 'CC';
			case 'HPP':
				return 'MP';
			case 'BANK':
				return 'IB';
			default:
				return '  ';
		}
	}

	/**
	 * 이니시스 은행코드를 은행명으로 변환
	 */
	function getBankName($code)
	{
	    switch($code) {
		case "03" : return "기업은행"; break;
		case "04" : return "국민은행"; break;
		case "05" : return "외환은행"; break;
		case "07" : return "수협중앙회"; break;
		case "11" : return "농협중앙회"; break;
		case "20" : return "우리은행"; break;
		case "23" : return "SC제일은행"; break;
		case "31" : return "대구은행"; break;
		case "32" : return "부산은행"; break;
		case "34" : return "광주은행"; break;
		case "37" : return "전북은행"; break;
		case "39" : return "경남은행"; break;
		case "53" : return "한국씨티은행"; break;
		case "71" : return "우체국"; break;
		case "81" : return "하나은행"; break;
		case "88" : return "통합신한은행(신한,조흥은행)"; break;
		case "D1" : return "동양종합금융증권"; break;
		case "D2" : return "현대증권"; break;
		case "D3" : return "미래에셋증권"; break;
		case "D4" : return "한국투자증권"; break;
		case "D5" : return "우리투자증권"; break;
		case "D6" : return "하이투자증권"; break;
		case "D7" : return "HMC투자증권"; break;
		case "D8" : return "SK증권"; break;
		case "D9" : return "대신증권"; break;
		case "DB" : return "굿모닝신한증권"; break;
		case "DC" : return "동부증권"; break;
		case "DD" : return "유진투자증권"; break;
		case "DE" : return "메리츠증권"; break;
		case "DF" : return "신영증권"; break;
		default   : return ""; break;
	    }
	}

	/**
	 * @brief 이니시스 결제서버에 요청처리
	 */
	function getRemoteResource($url, $body = null, $timeout = 3, $method = 'GET', $content_type = null, $headers = array(), $cookies = array(), $post_data = array())
	{
		try
		{
			requirePear();
			require_once('HTTP/Request.php');

			$parsed_url = parse_url(__PROXY_SERVER__);
			if($parsed_url["host"])
			{
				$oRequest = new HTTP_Request(__PROXY_SERVER__);
				$oRequest->setMethod('POST');
				$oRequest->_timeout = $timeout;
				$oRequest->addPostData('arg', serialize(array('Destination' => $url, 'method' => $method, 'body' => $body, 'content_type' => $content_type, "headers" => $headers, "post_data" => $post_data)));
			}
			else
			{
				$oRequest = new HTTP_Request($url);
				if(method_exists($oRequest,'setConfig')) $oRequest->setConfig(array('ssl_verify_peer' => FALSE, 'ssl_verify_host' => FALSE));

				if(count($headers))
				{
					foreach($headers as $key => $val)
					{
						$oRequest->addHeader($key, $val);
					}
				}
				if($cookies[$host])
				{
					foreach($cookies[$host] as $key => $val)
					{
						$oRequest->addCookie($key, $val);
					}
				}
				if(count($post_data))
				{
					foreach($post_data as $key => $val)
					{
						$oRequest->addPostData($key, $val);
					}
				}
				if(!$content_type)
					$oRequest->addHeader('Content-Type', 'text/html');
				else
					$oRequest->addHeader('Content-Type', $content_type);
				$oRequest->setMethod($method);
				if($body)
					$oRequest->setBody($body);

				$oRequest->_timeout = $timeout;
			}

			$oResponse = $oRequest->sendRequest();

			$code = $oRequest->getResponseCode();
			$header = $oRequest->getResponseHeader();
			$response = $oRequest->getResponseBody();
			if($c = $oRequest->getResponseCookies())
			{
				foreach($c as $k => $v)
				{
					$cookies[$host][$v['name']] = $v['value'];
				}
			}

			if($code > 300 && $code < 399 && $header['location'])
			{
				return $this->getRemoteResource($header['location'], $body, $timeout, $method, $content_type, $headers, $cookies, $post_data);
			}

			if($code != 200)
				return;

			return $response;
		}
		catch(Exception $e)
		{
			return NULL;
		}
	}
}
/* End of file inipaymobile.controller.php */
/* Location: ./modules/inipaymobile/inipaymobile.controller.php */
