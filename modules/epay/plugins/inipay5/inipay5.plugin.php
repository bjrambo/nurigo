<?php
define('INIPAY_HOME', _XE_PATH_.'files/epay/inipay5');
define('INIPAY_LOGDIR', _XE_PATH_.'files/epay/inipay5/log');
define('INIPAY_KEYDIR', _XE_PATH_.'files/epay/inipay5/key');

class inipay5 extends EpayPlugin 
{
	var $plugin_info;

	function pluginInstall($args) 
	{
		// mkdir
		FileHandler::makeDir(sprintf(_XE_PATH_."files/epay/%s/key",$args->plugin_srl));
		FileHandler::makeDir(sprintf(_XE_PATH_."files/epay/%s/log",$args->plugin_srl));
		// copy files
		FileHandler::copyFile(_XE_PATH_.'modules/epay/plugins/inipay5/.htaccess',sprintf(_XE_PATH_."files/epay/%s/.htaccess",$args->plugin_srl));
		FileHandler::copyFile(_XE_PATH_.'modules/epay/plugins/inipay5/readme.txt',sprintf(_XE_PATH_."files/epay/%s/readme.txt",$args->plugin_srl));
		FileHandler::copyFile(_XE_PATH_.'modules/epay/plugins/inipay5/key/pgcert.pem',sprintf(_XE_PATH_."files/epay/%s/key/pgcert.pem",$args->plugin_srl));
	}

	function inipay5() 
	{
		parent::EpayPlugin();
	}

	function init(&$args)
	{
		$this->plugin_info = new StdClass();
		foreach ($args as $key=>$val)
		{
			$this->plugin_info->{$key} = $val;
		}
		foreach ($args->extra_var as $key=>$val)
		{
			$this->plugin_info->{$key} = $val->value;
		}
		Context::set('plugin_info', $this->plugin_info);
	}

	/**
	 * item_name
	 * price
	 * purchaser_name
	 * purchaser_email
	 * purchaser_telnum
	 */
	function getFormData($args)
	{
		if (!$args->price) return new Object(0,'No input of price');
		if (!$args->epay_module_srl) return new Object(-1,'No input of epay_module_srl');
		if (!$args->module_srl) return new Object(-1,'No input of module_srl');

		Context::set('module_srl', $args->module_srl);
		Context::set('epay_module_srl', $args->epay_module_srl);
		Context::set('plugin_srl', $this->plugin_info->plugin_srl);

		Context::set('item_name', $args->item_name);
		Context::set('purchaser_name', $args->purchaser_name);
		Context::set('purchaser_email', $args->purchaser_email);
		Context::set('purchaser_telnum', $args->purchaser_telnum);
		Context::set('script_call_before_submit', $args->script_call_before_submit);
		Context::set('join_form', $args->join_form);

		$oTemplate = &TemplateHandler::getInstance();
		$tpl_path = _XE_PATH_."modules/epay/plugins/inipay5/tpl";
		$tpl_file = 'formdata.html';
		$form_data = $oTemplate->compile($tpl_path, $tpl_file);

		$output = new Object();
		$output->data = $form_data;
		return $output;
	}

	function processReview($args)
	{
		$inipayhome = sprintf(_XE_PATH_."files/epay/%s", $args->plugin_srl);

		require("libs/INILib.php");
		$inipay = new INIpay50;
		$inipay->SetField("inipayhome", $inipayhome);
		$inipay->SetField("type", "chkfake");
		$inipay->SetField("debug", "true");
		$inipay->SetField("enctype","asym");
		$inipay->SetField("admin", $this->plugin_info->inicis_pass);
		$inipay->SetField("checkopt", "false");
		$inipay->SetField("mid", $this->plugin_info->inicis_id);
		$inipay->SetField("price", $args->price);
		$inipay->SetField("nointerest", "no");
		$inipay->SetField("quotabase", iconv('UTF-8', 'EUC-KR', '선택:일시불:2개월:3개월:6개월'));

		/* 암호화 대상/값을 암호화 */
		$inipay->startAction();

		/* 암호화 결과 */
		if( $inipay->GetResult("ResultCode") != "00" ) {
			$resultMsg = iconv("EUC-KR", "UTF-8", $inipay->GetResult("ResultMsg"));
			return new Object(-1, $resultMsg);
		}

		/* 세션정보 저장 */
		$_SESSION['INI_MID'] = $this->plugin_info->inicis_id;	//상점ID
		$_SESSION['INI_ADMIN'] = $this->plugin_info->inicis_pass;	// 키패스워드(키발급시 생성, 상점관리자 패스워드와 상관없음)
		$_SESSION['INI_PRICE'] = $args->price;   //가격 
		$_SESSION['INI_RN'] = $inipay->GetResult("rn"); //고정 (절대 수정 불가)
		$_SESSION['INI_ENCTYPE'] = $inipay->GetResult("enctype"); //고정 (절대 수정 불가)

		Context::set('encfield', $inipay->GetResult('encfield'));
		Context::set('certid', $inipay->GetResult('certid'));
		Context::set('inicis_id', $this->plugin_info->inicis_id);
		Context::set('price', $args->price);
		Context::set('order_srl', $args->order_srl);
		$oTemplate = &TemplateHandler::getInstance();
		$tpl_path = _XE_PATH_."modules/epay/plugins/inipay5/tpl";
		$tpl_file = 'review.html';
		$tpl_data = $oTemplate->compile($tpl_path, $tpl_file);

		$output = new Object();
		$output->add('tpl_data', $tpl_data);
		return $output;
	}

	function processPayment($args)
	{
		$inipayhome = sprintf(_XE_PATH_."files/epay/%s", $args->plugin_srl);

		$vars = Context::getRequestVars();
		extract(get_object_vars($vars));

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

		/* 지불요청 */
		$inipay->startAction();

		$utf8ResultMsg = iconv('EUC-KR', 'UTF-8', $inipay->GetResult('ResultMsg'));
		$utf8VACTName = iconv('EUC-KR', 'UTF-8', $inipay->GetResult('VACT_Name'));
		$utf8VACTInputName = iconv('EUC-KR', 'UTF-8', $inipay->GetResult('VACT_InputName'));

		// error check
		if ($inipay->GetResult('ResultCode') != '00') 
		{
			$output = new Object(-1, $utf8ResultMsg);
			$output->add('state', '3'); // failure
		}
		else
		{
			$output = new Object(0, $utf8ResultMsg);
			if ($this->getPaymethod(Context::get('paymethod'))=='VA')
			{
				$output->add('state', '1'); // not completed
			} else {
				$output->add('state', '2'); // completed (success)
			}
		}

		$output->add('payment_method', $this->getPaymethod(Context::get('paymethod')));
		$output->add('payment_amount', $_SESSION['INI_PRICE']);
		$output->add('result_code', $inipay->GetResult('ResultCode'));
		$output->add('result_message', $utf8ResultMsg);
		$output->add('vact_num', $inipay->GetResult('VACT_Num')); // 계좌번호
		$output->add('vact_bankname', $this->getBankName($inipay->GetResult('VACT_BankCode'))); //은행코드
		$output->add('vact_bankcode', $inipay->GetResult('VACT_BankCode')); //은행코드
		$output->add('vact_name', $utf8VACTName); // 예금주
		$output->add('vact_inputname', $utf8VACTInputName); // 송금자
		$output->add('vact_regnum', $inipay->GetResult('VACT_RegNum')); //송금자 주번
		$output->add('vact_date', $inipay->GetResult('VACT_Date')); // 송금일자
		$output->add('vact_time', $inipay->GetResult('VACT_Time')); // 송금시간
		$output->add('pg_tid', $inipay->GetResult('TID'));
		return $output;
	}

	function processReport(&$transaction)
	{
		$inipayhome = sprintf(_XE_PATH_."files/epay/%s", $transaction->plugin_srl);

	
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
		if ($output->amount == $transaction->payment_amount)
		{
			echo "OK";
			$output->setError(0);
			$output->state = '2'; // successfully completed
		}
		else
		{
			$output->setError(-1);
			$output->setMessage('amount not match');
			$output->state = '1'; // not completed
		}
		return $output;
	}

	function getReceipt($pg_tid, $paymethod = NULL)
	{
		Context::set('tid', $pg_tid);
		Context::set('paymethod', $paymethod);
		$oTemplate = &TemplateHandler::getInstance();
		$tpl_path = _XE_PATH_."modules/epay/plugins/inipay5/tpl";
		$tpl_file = 'receipt.html';
		$tpl = $oTemplate->compile($tpl_path, $tpl_file);
		return $tpl;
	}

	function getReport() 
	{
		$output = new Object();
		$output->order_srl = Context::get('no_oid');
		$output->amount = Context::get('amt_input');
		return $output;
	}

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
/* End of file inipay5.plugin.php */
/* Location: ./modules/epay/plugins/inipay5/inipay5.plugin.php */
