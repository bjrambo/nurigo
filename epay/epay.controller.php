<?php
/**
 * vi:set ts=4 sw=4 noexpandtab fileencoding=utf-8:
 * @class  Epay System Controller
 * @author NURIGO(contact@nurgio.net)
 * @brief  Epay Page Controller
 **/
class epayController extends epay
{
	/**
	 * @brief initialize this module.
	 */
	function init()
	{
	}

	/**
	 * @brief create a transaction.
	 */
	function reviewOrder()
	{
		$oEpayModel = &getModel('epay');
		$oModuleModel = &getModel('module');

		$order_srl = getNextSequence();
		$transaction_srl = $order_srl;
		$review_args = Context::getRequestVars();
		$review_args->transaction_srl = $transaction_srl;
		$review_args->order_srl = $order_srl;

		// gerRequestVars에 plugin_srl 안넘어오는 불상사가 있음
		if(!$review_args->plugin_srl) $review_args->plugin_srl = Context::get('plugin_srl');

		if (!$review_args->module_srl) return new Object(-1, 'no module_srl');
		if (!$review_args->epay_module_srl) return new Object(-1, 'no epay_module_srl');

		// before trigger
		// returns review_args->price, review_args->item_name
		$output = ModuleHandler::triggerCall('epay.processReview', 'before', $review_args);
		if (!$output->toBool()) return $output;

		// save transaction info.
		$args = new stdClass();
		$args->target_module = $review_args->target_module;
		$args->transaction_srl = $review_args->transaction_srl;
		$args->epay_module_srl = $review_args->epay_module_srl;
		$args->module_srl = $review_args->module_srl;
		$args->plugin_srl = $review_args->plugin_srl;
		$args->order_srl = $review_args->order_srl;
		$args->order_title = $review_args->item_name;
		$args->payment_method = $review_args->payment_method;
		$args->payment_amount = $review_args->price;

		$logged_info = Context::get('logged_info');
		if($logged_info)
		{
			$args->p_member_srl = $logged_info->member_srl;
			$args->p_user_id = $logged_info->user_id;
			$args->p_name = $logged_info->nick_name;
			$args->p_email_address = $logged_info->email_address;
		}
		if($manorder_pid)
		{
			$args->user_id = $manorder_pid;
			$output = executeQuery('member.getMemberInfo', $args);

			$args->p_member_srl = $output->data->member_srl;
			$args->p_user_id = $output->data->user_id;
			$args->p_name = $output->data->nick_name;
			$args->p_email_address = $output->data->email_address;
		}
		if(!$manorder_pid && !$logged_info)
		{
			$args->p_member_srl = 0;
			$args->p_user_id = $p_user_id;
			$args->p_name = $p_name;
			$args->p_email_address = $p_email_address;
		}

		$args->result_code = '';
		$args->result_message = '';
		$args->pg_tid = '';
		$args->state = '';
		$extra_vars = new stdClass();
		$args->extra_vars = serialize($extra_vars);

		$output = executeQuery('epay.insertTransaction',$args);
		if (!$output->toBool()) return $output;

		// after
		$afterOutput = ModuleHandler::triggerCall('epay.processReview', 'after', $review_args);
		if(!$afterOutput->toBool())	return $afterOutput;

		$returnOutput = new Object();
		$returnOutput->review_form = $review_args->review_form;
		$returnOutput->order_srl = $order_srl;
		$returnOutput->transaction_srl = $transaction_srl;
		$returnOutput->target_module = $review_args->target_module;
		$returnOutput->epay_module_srl = $review_args->epay_module_srl;
		$returnOutput->module_srl = $review_args->module_srl;
		$returnOutput->order_srl = $review_args->order_srl;
		$returnOutput->item_name = $review_args->item_name;
		$returnOutput->payment_method = $review_args->payment_method;
		$returnOutput->price = $review_args->price;
		$returnOutput->purchaser_name = $review_args->purchaser_name;
		$returnOutput->purchaser_email = $review_args->purchaser_email;
		$returnOutput->purchaser_telnum = $review_args->purchaser_telnum;

		return $returnOutput;
	}

	/**
	 * @brief epay.processPayment trigger before will be called in this function.
	 */
	function beforePayment($params)
	{
		$oModuleModel = &getModel('module');
		$oEpayModel = &getModel('epay');

		// get transaction info by transaction_srl
		$transaction_info = $oEpayModel->getTransactionInfo($params->transaction_srl);
		if(!$transaction_info) return new Object(-1, 'could not find transaction info');

		// before trigger
		$args = new stdClass();
		$args->module_srl = $transaction_info->module_srl;
		$args->epay_module_srl = $transaction_info->epay_module_srl;
		$args->order_srl = $transaction_info->order_srl;
		$args->target_module = $transaction_info->target_module;
		$output = ModuleHandler::triggerCall('epay.processPayment', 'before', $args);
		if(!$output->toBool()) return $output;

		return new Object();
	}

	/**
	 * @brief payment complete, after trigger will be called in this function.
	 * order_srl : reviewOrder에서 리턴된 order_srl
	 * state : '1' (not completed), '2' (completed), '3' (failed)
	 * payment_method : VA, CC, MP, IB
	 * payment_amount : price
	 * result_code : PG result code
	 * result_message : PG result message
	 * vact_num : 계좌번호
	 * vact_bankname : 은행코드
	 * vact_bankcode : 은행코드
	 * vact_name : 예금주
	 * vact_inputname : 송금자
	 * vact_regnum : 송금자 주번
	 * vact_date : 송금일자
	 * vact_time : 송금시간
	 * pg_tid : PG TID
	 */
	function afterPayment($params)
	{
		$oModuleModel = &getModel('module');
		$oEpayModel = &getModel('epay');

		// get transaction info by transaction_srl
		$transaction_info = $oEpayModel->getTransactionInfo($params->get('transaction_srl'));
		if(!$transaction_info) return new Object(-1, 'could not find transaction info');

		// update transaction info
		$args = new stdClass();
		$args->transaction_srl = $params->get('transaction_srl');
		$args->result_code = $params->get('result_code');
		$args->result_message = $params->get('result_message');
		$args->pg_tid = $params->get('pg_tid');
		$args->state = $params->get('state');
		$extra_vars = unserialize($transaction_info->extra_vars);
		$variables = $params->getVariables();
		foreach($variables as $key=>$val)
		{
			$extra_vars->{$key} = $val;
		}
		$args->extra_vars = serialize($extra_vars);
		$output = executeQuery('epay.updateTransaction', $args);
		if(!$output->toBool()) return $output;
		unset($args);


		// after trigger
		$args = new stdClass();
		$args->order_srl = $transaction_info->order_srl;
		$args->transaction_srl = $params->get('transaction_srl');
		$args->state = $params->get('state');
		$args->target_module = $transaction_info->target_module;
		$args->module_srl = $transaction_info->module_srl; // target page(module instance)
		$args->payment_method = $params->get('payment_method');
		$args->payment_amount = $params->get('payment_amount');
		$args->result_code = $params->get('result_code');
		$args->result_message = $params->get('result_message');
		$args->vact_num = $params->get('vact_num');
		$args->vact_bankname = $params->get('vact_bankname');
		$args->vact_bankcode = $params->get('vact_bankcode');
		$args->vact_name = $params->get('vact_name');
		$args->vact_inputname = $params->get('vact_inputname');
		$args->vact_regnum = $params->get('vact_regnum');
		$args->vact_date = $params->get('vact_date');
		$args->vact_time = $params->get('vact_time');
		$args->pg_tid = $params->get('pg_tid');
		$output = ModuleHandler::triggerCall('epay.processPayment', 'after', $args);
		debugPrint('after trigger');
		debugPrint($output);
		if(!$output->toBool()) return $output;

		// check state
		if ($args->state=='3') // failure
		{
			$this->setError(-1);
			$this->setMessage($args->result_message);
		}

		$return_url = $args->return_url;
		debugPrint('return_url');
		debugPrint($return_url);
		if (!$return_url) $return_url = Context::get('return_url');
		$output = new Object();
		$output->add('return_url', $return_url);
		return $output;
	}

	/**
	 * @breif review order (this will be deleted in the future)
	 */
	function procEpayReviewOrder()
	{
		$oEpayModel = &getModel('epay');
		$oModuleModel = &getModel('module');

		$module_srl = Context::get('module_srl');
		if (!$module_srl) return new Object(-1, 'no module_srl');
		$epay_module_srl = Context::get('epay_module_srl');
		if (!$epay_module_srl) return new Object(-1, 'no epay_module_srl');
		$plugin_srl = Context::get('plugin_srl');
		if (!$plugin_srl) return new Object(-1, 'no plugin_srl');

		$order_srl = getNextSequence();

		$module_info = $oModuleModel->getModuleInfoByModuleSrl($epay_module_srl);

		$plugin = $oEpayModel->getPlugin($plugin_srl);

		$args = Context::getRequestVars();

		$args->epay_module_srl = $epay_module_srl;
		$args->plugin_srl = $plugin_srl;
		$args->plugin_name = $module_info->plugin_name;
		$args->order_srl = $order_srl;

		// before
		$output = ModuleHandler::triggerCall('epay.processReview', 'before', $args);
		if (!$output->toBool()) return $output;

		$_SESSION['module_srl'] = $module_srl;
		$_SESSION['epay_module_srl'] = $epay_module_srl;
		$_SESSION['order_srl'] = $order_srl;
		$_SESSION['epay_target_module'] = $args->target_module;
		$_SESSION['xe_mid'] = Context::get('xe_mid');


		$review_output = $plugin->processReview($args);
		if (!$review_output->toBool()) return $review_output;

		if ($review_output->get('return_url')) $this->add('return_url', $review_output->get('return_url'));

		if ($review_output->get('return_url')) $this->setRedirectUrl($review_output->get('return_url'));
		if ($review_output->get('tpl_data')) $this->add('tpl', $review_output->get('tpl_data'));

		// after
		$output = ModuleHandler::triggerCall('epay.processReview', 'after', $args);
		if(!$output->toBool())	return $output;

		$this->add('order_srl', $order_srl);
	}

	/**
	 * @breif payment(this will be deleted in the future)
	 */
	function procEpayDoPayment()
	{
		$oModuleModel = &getModel('module');
		$oEpayModel = &getModel('epay');

		$p_user_id = Context::get('purchaser_name');
		$p_name = Context::get('purchaser_name');
		$p_email_address = Context::get('email_address');

		$module_srl = $_SESSION['module_srl'];
		$epay_module_srl = $_SESSION['epay_module_srl'];
		$order_srl = $_SESSION['order_srl'];
		$target_module = $_SESSION['epay_target_module'];

		if(!$module_srl) $module_srl = Context::get('module_srl');
		if(!$epay_module_srl) $epay_module_srl = Context::get('epay_module_srl');
		if(!$order_srl) $order_srl = Context::get('order_srl');
		if(!$target_module) $target_module = Context::get('epay_target_module');

		if (!$epay_module_srl) return new Object(-1, 'msg_invalid_request');
		if (!$order_srl) return new Object(-1, 'msg_invalid_request');
		$plugin_srl = Context::get('plugin_srl');

		$mid = $_SESSION['xe_mid'];

		/* 
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($epay_module_srl);
		$plugin = $oEpayModel->getPlugin($module_info->plugin_srl);
		 */
		$plugin = $oEpayModel->getPlugin($plugin_srl);

		$obj = Context::getRequestVars();

		if($obj->manorder_pid) $manorder_pid = $obj->manorder_pid; // 결제대행 유저 아이디.

		$obj->module_srl = $module_srl;
		$obj->epay_module_srl = $epay_module_srl;
		/*
		$obj->plugin_srl = $module_info->plugin_srl;
		$obj->plugin_name = $module_info->plugin_name;
		 */
		$obj->plugin_srl = $plugin->plugin_info->plugin_srl;
		$obj->plugin_name = $plugin->plugin_info->plugin_title;
		$obj->order_srl = $order_srl;
		$obj->xe_mid = $mid;

		// before
		$output = ModuleHandler::triggerCall('epay.processPayment', 'before', $obj);
		if(!$output->toBool()) return $output;

		// call
		$pp_ret = $plugin->processPayment($obj);
		if(is_object($pp_ret) && method_exists($pp_ret, 'toBool') && !$pp_ret->toBool()) {
			Context::set('content', $pp_ret->data);
			$this->setTemplatePath($this->module_path . 'tpl');
			$this->setTemplateFile('error');
			if(in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				return $pp_ret;
			}
			return;
		}

		// save transaction info.
		$args->xe_mid = $mid;
		$args->target_module = $target_module;
		$args->transaction_srl = getNextSequence();
		$args->epay_module_srl = $epay_module_srl;
		$args->module_srl = $module_srl;
		$args->plugin_srl = $plugin->plugin_info->plugin_srl;
		$args->order_srl = $order_srl;
		$args->order_title = $obj->epay_order_title;
		$args->payment_method = $pp_ret->get('payment_method');
		$args->payment_amount = $pp_ret->get('payment_amount');

		$logged_info = Context::get('logged_info');
		if($logged_info)
		{
			$args->p_member_srl = $logged_info->member_srl;
			$args->p_user_id = $logged_info->user_id;
			$args->p_name = $logged_info->nick_name;
			$args->p_email_address = $logged_info->email_address;
		}
		if($manorder_pid)
		{
			$args->user_id = $manorder_pid;
			$output = executeQuery('member.getMemberInfo', $args);

			$args->p_member_srl = $output->data->member_srl;
			$args->p_user_id = $output->data->user_id;
			$args->p_name = $output->data->nick_name;
			$args->p_email_address = $output->data->email_address;
		}
		if(!$manorder_pid && !$logged_info)
		{
			$args->p_member_srl = 0;
			$args->p_user_id = $p_user_id;
			$args->p_name = $p_name;
			$args->p_email_address = $p_email_address;
		}

		$args->result_code = $pp_ret->get('result_code');
		$args->result_message = $pp_ret->get('result_message');
		$args->pg_tid = $pp_ret->get('pg_tid');
		$args->state = $pp_ret->get('state');
		$extra_vars = $pp_ret->getVariables();
		unset($extra_vars['state']);
		unset($extra_vars['payment_method']);
		unset($extra_vars['payment_amount']);
		$args->extra_vars = serialize($extra_vars);

		$output = executeQuery('epay.insertTransaction',$args);
		if (!$output->toBool()) return $output;

		// after
		$args->extra_vars = $extra_vars;
		$output = ModuleHandler::triggerCall('epay.processPayment', 'after', $args);
		if(!$output->toBool()) return $output;

		// check state
		if ($args->state=='3') // failure
		{
			$this->setError(-1);
			$this->setMessage($args->result_message);
		}

		$return_url = $args->return_url;
		if (!$return_url) $return_url = Context::get('return_url');

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) 
		{
			$returnUrl = $return_url ? $return_url : getNotEncodedUrl('','mid',$mid,'act','dispStoreOrderComplete','order_srl',$order_srl);
			$this->setRedirectUrl($returnUrl);
		}

		$this->add('return_url', $return_url);
		$this->add('order_srl', $order_srl);
	}

	/**
	 * @brief this will be called by PG server for virtual account payment
	 * (this function will be removed in the future)
	 */
	function procEpayReport()
	{
		/**
		 * Reporting URL
		 * http://mydomain.name/?module=epay&act=procEpayReport&pg=inipay5
		 */
		$oEpayModel = &getModel('epay');

		$plugin = $oEpayModel->getPluginByName(Context::get('pg'));

		$report = $plugin->getReport();
		$transaction = $oEpayModel->getTransactionByOrderSrl($report->order_srl);
		$pr_ret = $plugin->processReport($transaction);
		$transaction->state = $pr_ret->state;
		$output = executeQuery('epay.updateTransaction',$transaction);
		if (!$output->toBool()) return $output;

		$output = ModuleHandler::triggerCall('epay.processPayment', 'after', $transaction);
		if(!$output->toBool()) return $output;

		exit(0);
	}

	/**
	 * @brief not used for now
	 */
	function sendTaxinvoice($module_srl, $history_srl, $member_srl=false)
	{
		$oModuleModel = &getModel('module');
		if($module_srl)
		{
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
			if($module_info->module_srl != $module_srl) unset($module_srl);
		}

		// get history info
		/*
		$args->history_srl = $history_srl;
		$output = executeQuery('recharge.getRechargeHistoryInfo', $args);
		if (!$output->toBool()) return $output;
		$taxinvoice = $output->data;
		 */

		// check valid member_srl
		if($member_srl)
		{
			if ($taxinvoice->member_srl != $member_srl) return new Object(-1,'msg_invliad_request');
		}

		// compare date
		$prevmonth = $this->get_previous_month();
		$thismonth = date('Ym');
		if(!in_array(zdate($taxinvoice->regdate, 'Ym'), array($prevmonth,$thismonth)))
		{
			return new Object(-1, 'msg_tax_expired');
		}
		// if previous month, it must be within 10
		if(zdate($taxinvoice->regdate, 'Ym')==$prevmonth && intval(date('d')) > 5)
		{
			return new Object(-1, 'msg_tax_expired');
		}

		// get company info.
		/*
		$args->member_srl = $taxinvoice->member_srl;
		$output = executeQuery('recharge.getRechargeCompanyInfo', $args);
		if (!$output->toBool() || !$output->data) return $output;
		$company_info = $output->data;
		if (!$company_info->official) $company_info->official = "none";
		if (!$company_info->telnum) $company_info->telnum = "02-000-0000";
		 */

		require_once($this->module_path . 'lib/nusoap.php');
		require_once($this->module_path . 'lib/XML.class.php');
		require_once($this->module_path . 'lib/Hiworks_Bill.class.php');

		$cfg = array();
		$cfg['license_no'] = $module_info->api_license_no;    //  발급받으신 번호를 입력해주세요
		$cfg['license_id'] = $module_info->api_license_id;    //  하이웍스 아이디를 입력해주세요
		$cfg['domain'] = $module_info->api_domain;    //  하이웍스 도메인을 입력해주세요
		$cfg['partner_id'] = $module_info->api_partner_id;    //  하이웍스 회사 코드를 입력해주세요

		//include("bill_cfg.php");

		/* **************************************** */
		/* define 정의                                */
		/* **************************************** */
		define( HB_DOCUMENTTYPE_TAX , 'A' );    // 세금계산서
		define( HB_DOCUMENTTYPE_BILL , 'B' );   // 계산서
		define( HB_DOCUMENTTYPE_DETAIL , 'D' ); // 거래명세서

		define( HB_TAXTYPE_TAX, 'A' );		// 과세
		define( HB_TAXTYPE_NOTAX, 'B' );	// 영세
		define( HB_TAXTYPE_MANUAL, 'D' );	// 수동

		define( HB_SENDTYPE_SEND, 'S' );	// 매출
		define( HB_SENDTYPE_RECV, 'R' );	// 매입

		define( HB_PTYPE_RECEIPT, 'R' );	// 영수
		define( HB_PTYPE_CALL, 'C' );		// 청구

		define( HB_COMPANYPREFIX_SUPPLIER, 's' );	// 매출처 접두어
		define( HB_COMPANYPREFIX_CONSUMER, 'r' );	// 매입처 접두어

		define( HB_SOAPSERVER_URL, 'http://billapi.hiworks.co.kr/server.php?wsdl' );  // SOAP Server URL

		/* **************************************** */
		/* 타입 정의                                */
		/* **************************************** */
		$document_status = array();
		$document_status['W'] = '미발송';
		$document_status['T'] = '미열람';
		$document_status['R'] = '열람';
		$document_status['S'] = '승인';
		$document_status['B'] = '반려';
		$document_status['C'] = '승인취소요청';
		$document_status['A'] = '승인최소완료';


		//  hiworks bill 객체 생성
		$HB = new Hiworks_Bill_V2( $cfg['domain'], $cfg['license_id'], $cfg['license_no'], $cfg['partner_id'] );

		// 기본정보 입력
		$HB->set_basic_info('d_type', HB_DOCUMENTTYPE_TAX); // d_type : 세금계산서(HB_DOCUMENTTYPE_TAX), 계산서(HB_DOCUMENTTYPE_BILL), 거래명세서(HB_DOCUMENTTYPE_DETAIL)
		$HB->set_basic_info('kind', HB_TAXTYPE_TAX);        // kind : 과세(HB_TAXTYPE_TAX), 영세(HB_TAXTYPE_NOTAX), 수동(HB_TAXTYPE_MANUAL)
		$HB->set_basic_info('sendtype', HB_SENDTYPE_SEND);  // sendtype : 매출(HB_SENDTYPE_SEND), 매입(HB_SENDTYPE_RECV)

		$HB->set_basic_info('detail_together_tax', '1'); // 거래명세서 발송 시 세금계산서 동시 발송 여부(거래명세서만 발송할 경우는 주석처리하세요.)

		$HB->set_basic_info('c_name', $company_info->official);             // c_name : 담당자명
		$HB->set_basic_info('c_email', $company_info->email); // c_email : 이메일주소
		/*
		$HB->set_basic_info('c_cell', '010-000-0000');           // c_cell : 휴대폰
		 */
		$HB->set_basic_info('c_phone', $company_info->telnum);           // c_phone : 일반전화

		$HB->set_basic_info('sc_name', $module_info->sc_name);           // sc_name : 담당자명
		$HB->set_basic_info('sc_email', $module_info->sc_email); // sc_email : 이메일주소
		/*
		$HB->set_basic_info('sc_cell', $module_info->sc_cell);           // sc_cell : 휴대폰
		 */
		$HB->set_basic_info('sc_phone', $module_info->sc_phone);           // sc_phone : 일반전화

		$HB->set_basic_info('memo', '메모');                     // memo : 메모
		$HB->set_basic_info('book_no', '');               // book_no : 책번호 X권 X호
		$HB->set_basic_info('serial', '');                // serial : 일련번호

		$HB->set_document_info('issue_date', date('Y-m-d')); // issue_date : 작성일
		$HB->set_document_info('supplyprice', $taxinvoice->amount);     // supplyprice : 공급가액
		$HB->set_document_info('tax', $taxinvoice->tax);              // tax : 세금
		$HB->set_document_info('p_type', HB_PTYPE_RECEIPT);  // ptype : 영수(HB_PTYPE_RECEIPT), 청구(HB_PTYPE_CALL)
		$HB->set_document_info('remark', '');                // remark : 비고
		$HB->set_document_info('money', '');                 // money : 현금
		$HB->set_document_info('moneycheck', '');            // moneycheck : 수표
		$HB->set_document_info('bill', '');                  // bill : 어음
		$HB->set_document_info('uncollect', '');             // uncollect : 외상미수금

		// 공급자 정보
		$HB->set_company_info('s_number', $this->getDashedBizNum($module_info->s_number)); // s_number : 등록번호
		$HB->set_company_info('s_tnumber', $module_info->s_tnumber);        // s_tnumber : 종사업장번호
		$HB->set_company_info('s_name', $module_info->s_name);           // s_name : 상호(법인명)
		$HB->set_company_info('s_master', $module_info->s_master);   // s_master : 성명(대표자)
		$HB->set_company_info('s_address', $module_info->s_address);        // s_address : 주소
		$HB->set_company_info('s_condition', $module_info->s_condition);      // s_condition : 업태
		$HB->set_company_info('s_item', $module_info->s_item);           // s_item : 종목

		// 공급받는자 정보
		$HB->set_company_info('r_number', $this->getDashedBizNum($company_info->bizno)); // r_number : 등록번호
		//$HB->set_company_info('r_tnumber', '1111');        // r_tnumber : 종사업장번호
		$HB->set_company_info('r_name', $company_info->company_name);           // r_name : 상호(법인명)
		$HB->set_company_info('r_master', $company_info->ceo_name);   // r_master : 성명(대표자)
		$HB->set_company_info('r_address', $company_info->addr);        // r_address : 주소
		$HB->set_company_info('r_condition', $company_info->biz_status);      // r_condition : 업태
		$HB->set_company_info('r_item', $company_info->biz_type);           // r_item : 종목

		// 계산정보입력
		// 세금계산서, 계산서는 최대 4개, 거래명세서는 최대 20개까지 입력가능함
		$HB->set_work_info(zdate($taxinvoice->regdate, 'm'), zdate($taxinvoice->regdate, 'd'), '모바일메시징서비스', 'EA', '1', $taxinvoice->amount, $taxinvoice->amount, $taxinvoice->tax, '', $taxinvoice->payamount );

		$rs = $HB->send_document( HB_SOAPSERVER_URL );

		if (!$rs) {
			$message = "";
			$line = $HB->_getError();
			if(strpos($line, '|') !== false) {
				list($code, $msg) = explode('|', $line);
				$message = 'Error Code : ' . $code . ', Error Msg : '.$msg;
			} else {
				$message = 'Error :' . $line;
			}
			return new Object(-1, $message);
		}

		$args->member_srl = $taxinvoice->member_srl;
		$args->history_srl = $history_srl;
		$args->taxinvoice_id = $HB->get_document_id();
		$output = executeQuery('recharge.updateTaxinvoiceId', $args);
		if (!$output->toBool()) return $output;

		unset($HB, $rs);

		return new Object();
	}

	/**
	 * @brief this function will be removed in the future
	 */
	function procEpayExtra1()
	{
		$oEpayModel = &getModel('epay');
		$plugin = $oEpayModel->getPlugin(Context::get('plugin_srl'));
		return $plugin->procExtra1();
	}

	/**
	 * @brief this function will be removed in the future
	 */
	function procEpayExtra2()
	{
		$oEpayModel = &getModel('epay');
		$plugin = $oEpayModel->getPlugin(Context::get('plugin_srl'));
		return $plugin->procExtra2();
	}

	/**
	 * @brief this function will be removed in the future
	 */
	function procEpayExtra3()
	{
		$oEpayModel = &getModel('epay');
		$plugin = $oEpayModel->getPlugin(Context::get('plugin_srl'));
		return $plugin->procExtra3();
	}

	/**
	 * @brief this function will be removed in the future
	 */
	function procEpayEscrowDelivery()
	{
		$oEpayModel = &getModel('epay');
		$plugin = $oEpayModel->getPlugin(Context::get('plugin_srl'));
		$escrow_output = $plugin->procEscrowDelivery();
		$output = ModuleHandler::triggerCall('epay.escrowDelivery', 'after', $escrow_output);
		if(!$escrow_output->toBool()) return $escrow_output;
		return $output;
	}

	/**
	 * @brief this function will be removed in the future
	 */
	function procEpayEscrowConfirm()
	{
		$oEpayModel = &getModel('epay');
		$plugin = $oEpayModel->getPlugin(Context::get('plugin_srl'));
		$escrow_output = $plugin->procEscrowConfirm();
		$output = ModuleHandler::triggerCall('epay.escrowConfirm', 'after', $escrow_output);
		if(!$escrow_output->toBool()) return $escrow_output;
		return $output;
	}

	/**
	 * @brief this function will be removed in the future
	 */
	function procEpayEscrowDenyConfirm()
	{
		$oEpayModel = &getModel('epay');
		$plugin = $oEpayModel->getPlugin(Context::get('plugin_srl'));
		$escrow_output = $plugin->procEscrowDenyConfirm();
		$output = ModuleHandler::triggerCall('epay.escrowDenyConfirm', 'after', $escrow_output);
		if(!$escrow_output->toBool()) return $escrow_output;
		return $output;
	}

	/**
	 * @brief update extra vars
	 */
	function updateExtraVars($transaction_srl, $extra_vars)
	{
		$args->transaction_srl = $transaction_srl;
		$args->extra_vars = $extra_vars;

		$output = executeQuery('epay.updateTransaction', $args);
		if(!$output->toBool()) return $output;
	}
}
/* End of file epay.controller.php */
/* Location: ./modules/epay/epay.controller.php */
