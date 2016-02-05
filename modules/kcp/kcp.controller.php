<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  kcpController
 * @author NURIGO(contact@nurigo.net)
 * @brief  kcpController
 */
class kcpController extends kcp
{
	function init()
	{
		// set template path
		if ($this->module_info->module != 'kcp') $this->module_info->skin = 'default';
		if (!$this->module_info->skin) $this->module_info->skin = 'default';
		$this->setTemplatePath($this->module_path."skins/{$this->module_info->skin}");
		if ($this->module_info->service_mode == 'test')
		{
			$this->module_info->site_cd = "T0000" ;
			$this->module_info->site_key = "3grptw1.zW0GSo4PQdaGvsF__";
			if(!$this->module_info->site_name) $this->module_info->site_name = "TEST";
		}
		Context::set('module_info',$this->module_info);
	}

	/**
	 * default action
	 * 가상계좌 입금시 호출될 URL을 이니시스 관리자페이지에서 http://domain/mid 형식으로 설정한다.
	 */
	function procKcp()
	{
		$vars = Context::getRequestVars();
	
        // 가상계좌 입금시 inicis 서버에서 호출
        if(Context::get('ordr_idxx'))
        {
            return $this->processReport(Context::get('ordr_idxx'));
        }
	}

	/**
	 * @brief pay
	 */
	function procKcpDoIt()
	{
		$oEpayController = &getController('epay');
		$oKcpModel = &getModel('kcp');

		$vars = Context::getRequestVars();

		$output = $oEpayController->beforePayment($vars);
		if(!$output->toBool()) return $output;

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
			if ($this->module_info->service_mode == 'test')
			{
				$g_conf_gw_url    = "testpaygw.kcp.co.kr";
			}
			else
			{
				$g_conf_gw_url    = "paygw.kcp.co.kr"; // real service
			}
			$g_conf_site_cd = $this->module_info->site_cd;
			$g_conf_site_key = $this->module_info->site_key;
			$g_conf_site_name = $this->module_info->site_name;
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
			$payArgs = new Object(-1, $res_msg);
			$payArgs->add('state', constant('STATE_FAILURE')); // failure
		}
		else
		{
			$payArgs = new Object(0, $utf8ResultMsg);
			if ($oKcpModel->getEpayCode($use_pay_method)=='VA')
			{
				$payArgs->add('state', constant('STATE_NOTCOMPLETED')); // not completed
			} else {
				$payArgs->add('state', constant('STATE_COMPLETED')); // completed (success)
			}
		}

		$payArgs->add('transaction_srl', $vars->transaction_srl);
		$payArgs->add('payment_method', $oKcpModel->getEpayCode($use_pay_method));
		$payArgs->add('payment_amount', $amount);
		$payArgs->add('result_code', $res_cd);
		$payArgs->add('result_message', $res_msg);

		$payArgs->add('vact_bankname', $bankname); // 은행명
		$payArgs->add('vact_num', $account); // 계좌번호
		$payArgs->add('vact_name', $depositor); // 예금주
		$payArgs->add('vact_date', $va_date); // 송금일자

		$payArgs->add('pg_tid', $tno);


		// afterPayment will call an after trigger
		$output = $oEpayController->afterPayment($payArgs);
		if(!$output->toBool()) return $output;
		$return_url = $output->get('return_url');
		if(!$return_url) $return_url = getNotEncodedUrl('','module','epay','act','dispEpayError','transaction_srl',$vars->transaction_srl);
		if($payArgs->get('state')==constant('STATE_FAILURE')) $return_url = getNotEncodedUrl('','module','epay','act','dispEpayError','transaction_srl',$vars->transaction_srl);
		$this->setRedirectUrl($return_url);
	}

	/**
	 * @brief 가상계좌 입금시 처리
	 */
	function processReport($order_srl)
	{
        $oEpayModel = &getModel('epay');
        $transaction_info = $oEpayModel->getTransactionByOrderSrl($order_srl);
        if(!$transaction_info) return new Object(-1, 'could not find transaction');
	
		$payplushome = sprintf(_XE_PATH_."files/epay/kcp");
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
		$afterOutput = $oEpayController->afterPayment($output);
		if(!$afterOutput->toBool()) return $afterOutput;
		// OK를 출력하고 끝내야 한다.
		echo "OK";
		exit(0);
	}
}
/* End of file kcp.controller.php */
/* Location: ./modules/kcp/kcp.controller.php */
