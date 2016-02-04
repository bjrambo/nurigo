<?php
class payplus6 extends EpayPlugin {
	var $plugin_info;

	function pluginInstall($args) 
	{
		// mkdir
		FileHandler::makeDir(sprintf(_XE_PATH_."files/epay/%s/log",$args->plugin_srl));
		// copy files
		FileHandler::copyFile(_XE_PATH_.'modules/epay/plugins/payplus6/.htaccess',sprintf(_XE_PATH_."files/epay/%s/.htaccess",$args->plugin_srl));
		FileHandler::copyFile(_XE_PATH_.'modules/epay/plugins/payplus6/readme.txt',sprintf(_XE_PATH_."files/epay/%s/readme.txt",$args->plugin_srl));
	}

	function payplus6()
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

		if ($this->plugin_info->service_mode == 'test')
		{
			$this->plugin_info->site_cd = "T0000" ;
			$this->plugin_info->site_key = "3grptw1.zW0GSo4PQdaGvsF__";
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
		Context::set('purchaser_cellphone', $args->purchaser_cellphone);
		Context::set('purchaser_telnum', $args->purchaser_telnum);
		Context::set('script_call_before_submit', $args->script_call_before_submit);
		Context::set('join_form', $args->join_form);

		$oTemplate = &TemplateHandler::getInstance();
		$tpl_path = _XE_PATH_."modules/epay/plugins/payplus6/tpl";
		$tpl_file = 'formdata.html';
		$form_data = $oTemplate->compile($tpl_path, $tpl_file);

		$output = new Object();
		$output->data = $form_data;
		return $output;
	}

	function processReview($args) {
/*
		$inipayhome = sprintf(_XE_PATH_."files/epay/%s", $args->plugin_srl);
		Context::set('encfield', $inipay->GetResult('encfield'));
		Context::set('certid', $inipay->GetResult('certid'));
		Context::set('inicis_id', $this->inicis_id);
		$_SESSION['PAYPLUS_PRICE'] = $args->price;
*/
		if(isset($args->service_period) && is_array($args->service_period))
		{
			Context::set('good_expr', '1:' . $args->service_period[0] . $args->service_period[1]);
		}
		else
		{
			Context::set('good_expr', '0');
		}

		Context::set('order_srl', $args->order_srl);
		Context::set('price', $args->price);
		Context::set('buyr_name', $args->purchaser_name);
		Context::set('buyr_mail', $args->purchaser_email);
		Context::set('buyr_tel1', $args->purchaser_cellphone);
		Context::set('buyr_tel2', $args->purchaser_telnum);
		Context::set('params', $args);
		$oTemplate = &TemplateHandler::getInstance();
		$tpl_path = _XE_PATH_."modules/epay/plugins/payplus6/tpl";
		$tpl_file = 'review.html';
		$tpl_data = $oTemplate->compile($tpl_path, $tpl_file);

		$output = new Object();
		$output->add('tpl_data', $tpl_data);
		return $output;
	}

	function processPayment($args) {
		$vars = Context::getRequestVars();
		extract(get_object_vars($vars));

		require("libs/pp_ax_hub_lib.php");

		// initialize variables
		$amount = $good_mny;

		$c_PayPlus = new C_PP_CLI;
		$c_PayPlus->mf_clear();

		if ($req_tx == "pay")
		{
			// 승인요청
			$c_PayPlus->mf_set_encx_data($enc_data, $enc_info);
		}
		else if ($req_tx == 'mod')
		{
			// 취소/매입 요청
			$tran_cd = '00200000';
			$c_PayPlus->mf_set_modx_data('tno', $tno);
			$c_PayPlus->mf_set_modx_data('mod_type', $mod_type);
			$c_PayPlus->mf_set_modx_data('mod_ip', $cust_ip);
			$c_PayPlus->mf_set_modx_data('mod_desc', $mod_desc);
		}

		// 실행
		if ($tran_cd != "")
		{
			$g_conf_bin_dir  = dirname(__FILE__);
			$g_conf_home_dir = sprintf(_XE_PATH_."files/epay/%s", $args->plugin_srl);
			if ($this->plugin_info->service_mode == 'test')
			{
				$g_conf_gw_url    = "testpaygw.kcp.co.kr";
			}
			else
			{
				$g_conf_gw_url    = "paygw.kcp.co.kr"; // real service
			}
			$g_conf_site_cd = $this->plugin_info->site_cd;
			$g_conf_site_key = $this->plugin_info->site_key;
			$g_conf_site_name = $this->plugin_info->site_name;
			$g_conf_log_level = "3";           // 변경불가
			$g_conf_gw_port   = "8090";        // 포트번호(변경불가)

			$c_PayPlus->mf_do_tx($trace_no, $g_conf_bin_dir, $g_conf_home_dir, $g_conf_site_cd, $g_conf_site_key, $tran_cd, "",
				$g_conf_gw_url, $g_conf_gw_port, "payplus_cli_slib", $ordr_idxx,
				$cust_ip, "3" , 0, 0); // 응답 전문 처리
			$res_cd  = $c_PayPlus->m_res_cd;  // 결과 코드
			$res_msg = iconv('EUC-KR', 'UTF-8', $c_PayPlus->m_res_msg); // 결과 메시지
		}
		else
		{
			$c_PayPlus->m_res_cd  = "9562";
			$c_PayPlus->m_res_msg = "연동 오류|Payplus Plugin이 설치되지 않았거나 tran_cd값이 설정되지 않았습니다.";
		}
		
		// 승인 결과 값 추출
		if ($req_tx == 'pay')
		{
			if ($res_cd == '0000')
			{
				$tno = $c_PayPlus->mf_get_res_data('tno'); // KCP 거래 고유 번호
				$amount = $c_PayPlus->mf_get_res_data('amount'); // KCP 실제 거래 금액
				$pnt_issue = $c_PayPlus->mf_get_res_data('pnt_issue'); // 결제 포인트사 코드
				// 신용카드
				if ($use_pay_method == '100000000000')
				{
					$card_cd = $c_PayPlus->mf_get_res_data("card_cd"); // 카드사 코드
					$card_name = $c_PayPlus->mf_get_res_data("card_name"); // 카드 종류
					$app_time = $c_PayPlus->mf_get_res_data("app_time"); // 승인 시간
					$app_no = $c_PayPlus->mf_get_res_data("app_no"); // 승인 번호
					$noinf = $c_PayPlus->mf_get_res_data("noinf"); // 무이자 여부 ( 'Y' : 무이자 )
					$quota = $c_PayPlus->mf_get_res_data("quota"); // 할부 개월 수
				}

				// 가상계좌
				if ( $use_pay_method == "001000000000" )
				{
					$bankname = iconv('EUC-KR','UTF-8',$c_PayPlus->mf_get_res_data("bankname")); // 입금할 은행 이름
					$depositor = iconv('EUC-KR','UTF-8',$c_PayPlus->mf_get_res_data("depositor")); // 입금할 계좌 예금주
					$account = $c_PayPlus->mf_get_res_data("account"); // 입금할 계좌 번호
					$va_date = $c_PayPlus->mf_get_res_data("va_date"); // 가상계좌 입금마감시간
				}

			}
		}

		// error check
		if ($res_cd != '0000')
		{
			$output = new Object(-1, $res_msg);
			$output->add('state', constant('STATE_FAILURE')); // failure
		}
		else
		{
			$output = new Object(0, $utf8ResultMsg);
			if ($this->getPaymethod($use_pay_method)=='VA')
			{
				$output->add('state', constant('STATE_NOTCOMPLETED')); // not completed
			} else {
				$output->add('state', constant('STATE_COMPLETED')); // completed (success)
			}
		}

		$output->add('payment_method', $this->getPaymethod($use_pay_method));
		$output->add('payment_amount', $amount);
		$output->add('result_code', $res_cd);
		$output->add('result_message', $res_msg);

		$output->add('vact_bankname', $bankname); // 은행명
		$output->add('vact_num', $account); // 계좌번호
		$output->add('vact_name', $depositor); // 예금주
		$output->add('vact_date', $va_date); // 송금일자

		$output->add('pg_tid', $tno);

		return $output;
	}

	function processReport(&$transaction) {
		$payplushome = sprintf(_XE_PATH_."files/epay/%s", $transaction->plugin_srl);
		$TEMP_IP = $_SERVER["REMOTE_ADDR"];
		$PG_IP  = substr($TEMP_IP,0, 10);

		//PG에서 보냈는지 IP로 체크
		if ($PG_IP != "203.238.36") {
			return new Object(-1, 'msg_invalid_request');
		}

		$logfile = fopen($payplushome."/log/vbank_" . date("Ymd") . ".log", "a+");
		fwrite( $logfile,"************************************************\n");
		$vars = Context::getRequestVars();
		foreach ($vars as $key=>$val) {
			fwrite( $logfile,$key." : ".$val."\n");
		}
		fwrite( $logfile,"************************************************\n\n");
		fclose( $logfile );


		$output = new Object();
		$output->order_srl = Context::get('ordr_idxx');
		$output->amount = Context::get('totl_mnyx');
		if ($output->amount == $transaction->payment_amount)
		{
			$output->setError(0);
			$output->state = '2';
		}
		else
		{
			$output->setError(-1);
			$output->setMessage('amount not match');
			$output->state = '1';
		}
		return $output;
	}

	function getReceipt($pg_tid)
	{
		Context::set('tid', $pg_tid);
		$oTemplate = &TemplateHandler::getInstance();
		$tpl_path = _XE_PATH_."modules/epay/plugins/payplus6/tpl";
		$tpl_file = 'receipt.html';
		$tpl = $oTemplate->compile($tpl_path, $tpl_file);
		return $tpl;
	}

	// MUST return order_srl
	function getReport() {
		$output = new Object();
		$output->order_srl = Context::get('ordr_idxx');
		$output->amount = Context::get('totl_mnyx');
		return $output;
	}

	function getPaymethod($paymethod) {
		switch ($paymethod) {
			case '001000000000':
				return 'VA';
			case '100000000000':
				return 'CC';
			case '000010000000':
				return 'MP';
			case '010000000000':
				return 'IB';
			default:
				return '  ';
		}
	}
}
/* End of file payplus6.plugin.php */
/* Location: ./modules/epay/plugins/payplus6/payplus6.plugin.php */
