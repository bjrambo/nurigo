<?php

class inipaymobile extends EpayPlugin 
{
	var $plugin_info;

	function pluginInstall($args) 
	{
		// do nothing
	}

	function inipaymobile() 
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
		$tpl_path = _XE_PATH_."modules/epay/plugins/inipaymobile/tpl";
		$tpl_file = 'formdata.html';
		$form_data = $oTemplate->compile($tpl_path, $tpl_file);

		$output = new Object();
		$output->data = $form_data;
		return $output;
	}

	function processReview($args)
	{
		$oModuleModel = &getModel('module');
		$epay_module_srl = Context::get('epay_module_srl');

		$epay_module_info = $oModuleModel->getModuleInfoByModuleSrl($epay_module_srl);

		Context::set('price', $args->price);
		Context::set('order_srl', $args->order_srl);
		Context::set('order_title', $args->order_title);
		Context::set('purchaser_name', $args->purchaser_name);

		/*
		<!--************************************************************************************ 
		신용카드(안심클릭), 가상계좌, 휴대폰, 문화상품권, 해피머니 사용시 필수 항목 - 인증결과를 해당 url로 post함, 즉 이 URL이 화면상에 보여지게 됨 
		************************************************************************************--> 
		 <input type="hidden" name="P_NEXT_URL" value=" "> 

		 <!--************************************************************************************ 
		신용카드(ISP), 계좌이체, 가상계좌 필수항목 - 이 URL로 ISP 승인결과 및 가상계좌 입금정보가 리턴됨 
		************************************************************************************--> 
		 <input type="hidden" name="P_NOTI_URL" value=" "> 

		 <!--************************************************************************************ 
		신용카드(ISP), 계좌이체 필수항목 - ISP, 금결원계좌이체 동작이 완료된 후 이 URL이 화면상에 보여짐 
		************************************************************************************--> 
		 <input type="hidden" name="P_RETURN_URL" value=" "> 
		*/

		Context::set('next_url', getNotEncodedFullUrl('') . '/' . $epay_module_info->mid . '?n_page=' . $args->order_srl);
		Context::set('return_url', getNotEncodedFullUrl('') . '/' . $epay_module_info->mid . '?r_page=' . $args->order_srl);
		Context::set('noti_url', getNotEncodedFullUrl('','module','epay','act','procEpayDoPayment','module_srl',$args->module_srl, 'epay_module_srl',$args->epay_module_srl, 'plugin_srl',$this->plugin_info->plugin_srl, 'order_srl', $args->order_srl, 'epay_target_module', $args->target_module));

		$oTemplate = &TemplateHandler::getInstance();
		$tpl_path = _XE_PATH_."modules/epay/plugins/inipaymobile/tpl";
		$tpl_file = 'review.html';
		$tpl_data = $oTemplate->compile($tpl_path, $tpl_file);

		$output = new Object();
		$output->add('tpl_data', $tpl_data);
		return $output;
	}

	function processPayment($args)
	{
/*
		$vpresult = Context::get('vpresult');
		if($vpresult=='00')
		{
			$obj = new Object(-1, '취소되었습니다');
			$obj->data = '취소되었습니다';
			return $obj;
		}
*/
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
		$P_STATUS;			// 거래상태 (00:성공, 01:실패)
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




		$PageCall_time = date("H:i:s");

		$value = array(
			"PageCall time" => $PageCall_time,
			"P_TID"			=> $P_TID,  
			"P_MID"     => $P_MID,  
			"P_AUTH_DT" => $P_AUTH_DT,      
			"P_STATUS"  => $P_STATUS,
			"P_TYPE"    => $P_TYPE,     
			"P_OID"     => $P_OID,  
			"P_FN_CD1"  => $P_FN_CD1,
			"P_FN_CD2"  => $P_FN_CD2,
			"P_FN_NM"   => $P_FN_NM,  
			"P_AMT"     => $P_AMT,  
			"P_UNAME"   => $P_UNAME,  
			"P_RMESG1"  => $P_RMESG1,  
			"P_RMESG2"  => $P_RMESG2,
			"P_NOTI"    => $P_NOTI,  
			"P_AUTH_NO" => $P_AUTH_NO,
			"P_VACT_NUM" => $P_VACT_NUM,
			"P_VACT_DATE" => $P_VACT_DATE,
			"P_VACT_TIME" => $P_VACT_TIME,
			"P_VACT_NAME" => $P_VACT_NAME,
			"P_VACT_BANK_CODE" => $P_VACT_BANK_CODE
		);
		debugPrint('$value');
		debugPrint($value);


		// 결제처리에 관한 로그 기록
		//writeLog($value);


		/***********************************************************************************
		 ' 위에서 상점 데이터베이스에 등록 성공유무에 따라서 성공시에는 "OK"를 이니시스로 실패시는 "FAIL" 을
		 ' 리턴하셔야합니다. 아래 조건에 데이터베이스 성공시 받는 FLAG 변수를 넣으세요
		 ' (주의) OK를 리턴하지 않으시면 이니시스 지불 서버는 "OK"를 수신할때까지 계속 재전송을 시도합니다
		 ' 기타 다른 형태의 echo "" 는 하지 않으시기 바랍니다
		'***********************************************************************************/

		// if(데이터베이스 등록 성공 유무 조건변수 = true)
		if(!$_SESSION['inipaymobile_pass']) echo "OK"; //절대로 지우지 마세요


		$output = new Object(0, $P_RMESG1 . ' ' . $P_RMESG2);
		//$output = new Object(-1, $utf8ResultMsg);
		//$output->add('state', '3'); // failure

		//WEB 방식의 경우 가상계좌 채번 결과 무시 처리
		//(APP 방식의 경우 해당 내용을 삭제 또는 주석 처리 하시기 바랍니다.)
		$output->add('state', '2'); // completed (success)

		if($P_STATUS != "00") $output->add('state', '3');
		if($P_TYPE == "VBANK")	//결제수단이 가상계좌이며
		{
			if($P_STATUS != "02") //입금통보 "02" 가 아니면(가상계좌 채번 : 00 또는 01 경우)
			{
				$output->add('state', '1'); // not completed
			}
		}

		$output->add('payment_method', $this->getPaymethod($P_TYPE));
		$output->add('payment_amount', $P_AMT);
		$output->add('result_code', $P_STATUS);
		$output->add('result_message', $P_RMESG1);
		$output->add('vact_num', $P_VACT_NUM);
		$output->add('vact_bankname', $this->getBankName($P_VACT_BANK_CODE));
		$output->add('vact_bankcode', $P_VACT_BANK_CODE);
		$output->add('vact_name', $P_VACT_NAME);
		$output->add('vact_inputname', '');
		$output->add('vact_regnum', '');
		$output->add('vact_date', $P_VACT_DATE); // 입금마감 일자
		$output->add('vact_time', $P_VACT_TIME); // 입금마감 시간
		$output->add('pg_tid', $P_TID);
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

	function getReceipt($pg_tid)
	{
		Context::set('tid', $pg_tid);
		$oTemplate = &TemplateHandler::getInstance();
		$tpl_path = _XE_PATH_."modules/epay/plugins/inipaymobile/tpl";
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

	function dispExtra1()
	{
                $oTemplate = &TemplateHandler::getInstance();
                $tpl_path = _XE_PATH_."modules/epay/plugins/inipaymobile/tpl";
                $tpl_file = 'success.html';
                return $oTemplate->compile($tpl_path, $tpl_file);
	}

	function dispExtra2()
	{
                $oTemplate = &TemplateHandler::getInstance();
                $tpl_path = _XE_PATH_."modules/epay/plugins/inipaymobile/tpl";
                $tpl_file = 'cancel.html';
                return $oTemplate->compile($tpl_path, $tpl_file);
	}

	function getPaymethod($paymethod)
	{
		switch ($paymethod) {
			case 'VBANK':
				return 'VA';
			case 'WCARD':
				return 'CC';
			case 'MOBILE':
				return 'MP';
			case 'BANK':
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
/* End of file inipaymobile.plugin.php */
/* Location: ./modules/epay/plugins/inipaymobile/inipaymobile.plugin.php */
