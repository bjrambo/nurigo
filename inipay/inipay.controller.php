<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  inipayController
 * @author NURIGO(contact@nurigo.net)
 * @brief  inipayController
 */
class inipayController extends inipay
{
	/**
	 * default action
	 * 가상계좌 입금시 호출될 URL을 이니시스 관리자페이지에서 http://domain/mid 형식으로 설정한다.
	 */
	function procInipay()
	{
		$vars = Context::getRequestVars();
        // 가상계좌 입금시 inicis 서버에서 호출
        if(Context::get('no_oid'))
        {
            return $this->processReport(Context::get('no_oid'));
        }
	}

	/**
	 * @brief pay
	 */
	function procInipayDoIt()
	{
		$oEpayController = &getController('epay');

		$inipayhome = sprintf(_XE_PATH_."files/epay/%s", Context::get('module_srl'));

		$vars = Context::getRequestVars();

		$output = $oEpayController->beforePayment($vars);
		if(!$output->toBool()) return $output;

		$goodname = Context::get('goodname');
		$currency = Context::get('currency');

		require("libs/INILib.php");
		$inipay = new INIpay50;
		$inipay->SetField("inipayhome", $inipayhome);
		$inipay->SetField("type", "securepay");
		$inipay->SetField("pgid", "INIphp".$pgid); // $pgid is global var which defined in INICls.php
		$inipay->SetField("subpgip","203.238.3.10");
		$inipay->SetField("admin", $_SESSION['INI_ADMIN']);
		$inipay->SetField("debug", "true");
		$inipay->SetField("uid", $uid);
		$inipay->SetField("goodname", iconv("UTF-8", "EUC-KR", $goodname));
		$inipay->SetField("currency", $currency);

		$inipay->SetField("mid", $_SESSION['INI_MID']);
		$inipay->SetField("rn", $_SESSION['INI_RN']);
		$inipay->SetField("price", $_SESSION['INI_PRICE']);
		$inipay->SetField("enctype", $_SESSION['INI_ENCTYPE']);

		$inipay->SetField("buyername", iconv("UTF-8", "EUC-KR", Context::get('buyername')));
		$inipay->SetField("buyertel", Context::get('buyertel'));
		$inipay->SetField("buyeremail", Context::get('buyeremail'));
		$inipay->SetField("paymethod", Context::get('paymethod'));
		$inipay->SetField("encrypted", Context::get('encrypted'));
		$inipay->SetField("sessionkey", Context::get('sessionkey'));
		$inipay->SetField("url", 'www.nurigo.net');
		$inipay->SetField("cardcode", Context::get('cardcode'));

		$inipay->SetField("parentemail", Context::get('parentemail'));
		$inipay->SetField("recvname", Context::get('recvname'));
		$inipay->SetField("recvtel", Context::get('recvtel'));
		$inipay->SetField("recvaddr", Context::get('recvaddr'));
		$inipay->SetField("recvpostnum", Context::get('recvpostnum'));
		$inipay->SetField("recvmsg", Context::get('recvmsg'));
		$inipay->SetField("joincard", Context::get('joincard'));
		$inipay->SetField("joinexpire", Context::get('joinexpire'));
		$inipay->SetField("id_customer", Context::get('id_customer'));

		// 지불요청
		$inipay->startAction();

		$utf8ResultMsg = iconv('EUC-KR', 'UTF-8', $inipay->GetResult('ResultMsg'));
		$utf8VACTName = iconv('EUC-KR', 'UTF-8', $inipay->GetResult('VACT_Name'));
		$utf8VACTInputName = iconv('EUC-KR', 'UTF-8', $inipay->GetResult('VACT_InputName'));

		// error check
		if ($inipay->GetResult('ResultCode') != '00') 
		{
			$payArgs = new Object(-1, $utf8ResultMsg);
			$payArgs->add('state', '3'); // failure
		}
		else
		{
			$payArgs = new Object(0, $utf8ResultMsg);
			if ($this->getPaymethod(Context::get('paymethod'))=='VA')
			{
				$payArgs->add('state', '1'); // not completed
			} else {
				$payArgs->add('state', '2'); // completed (success)
			}
		}
		$payArgs->add('transaction_srl', Context::get('transaction_srl'));
		$payArgs->add('payment_method', $this->getPaymethod(Context::get('paymethod')));
		$payArgs->add('payment_amount', $_SESSION['INI_PRICE']);
		$payArgs->add('result_code', $inipay->GetResult('ResultCode'));
		$payArgs->add('result_message', $utf8ResultMsg);
		$payArgs->add('vact_num', $inipay->GetResult('VACT_Num')); // 계좌번호
		$payArgs->add('vact_bankname', $this->getBankName($inipay->GetResult('VACT_BankCode'))); //은행코드
		$payArgs->add('vact_bankcode', $inipay->GetResult('VACT_BankCode')); //은행코드
		$payArgs->add('vact_name', $utf8VACTName); // 예금주
		$payArgs->add('vact_inputname', $utf8VACTInputName); // 송금자
		$payArgs->add('vact_regnum', $inipay->GetResult('VACT_RegNum')); //송금자 주번
		$payArgs->add('vact_date', $inipay->GetResult('VACT_Date')); // 송금일자
		$payArgs->add('vact_time', $inipay->GetResult('VACT_Time')); // 송금시간
		$payArgs->add('pg_tid', $inipay->GetResult('TID'));


		// afterPayment will call an after trigger and returns return_url in $output
		$output = $oEpayController->afterPayment($payArgs);
		if(!$output->toBool()) return $output;
		$return_url = $output->get('return_url');
		if($return_url) $this->setRedirectUrl($return_url);
	}

	/**
	 * @brief 가상계좌 입금시 처리
	 */
	function processReport($order_srl)
	{
        $oEpayModel = &getModel('epay');
        $transaction_info = $oEpayModel->getTransactionByOrderSrl($order_srl);
        if(!$transaction_info) return new Object(-1, 'could not find transaction');
	
		$TEMP_IP = $_SERVER["REMOTE_ADDR"];
		$PG_IP  = substr($TEMP_IP,0, 10);

		//PG에서 보냈는지 IP로 체크
		if( $PG_IP != "203.238.37" && $PG_IP != "210.98.138" )  {
			debugPrint($PG_IP . 'is not from inicis');
			return new Object(-1, 'msg_invalid_request');
		}

		/*
		// 이니시스에서 보내오는 request 변수들
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


		$inipayhome = sprintf(_XE_PATH_."files/epay/%s", $transaction_info->module_srl);
		$logfile = fopen($inipayhome."/log/vbank_" . date("Ymd") . ".log", "a+");
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
			$output->add('state', '2'); // successfully completed
			$output->add('result_code', '0');
			$output->add('result_message', 'Success');
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

		// doPayment will call an after trigger
		$oEpayController = &getController('epay');
		$output = $oEpayController->afterPayment($output);
		if(!$output->toBool()) return $output;
		// OK를 출력하고 끝내야 한다.
		echo "OK";
		exit(0);
	}

	/**
	 * 이니시스 결제방식코드를 epay모듈코드로 변환
	 */
	function getPaymethod($paymethod)
	{
		switch ($paymethod) {
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
}
/* End of file inipay.controller.php */
/* Location: ./modules/inipay/inipay.controller.php */
