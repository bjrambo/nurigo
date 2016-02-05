<?php
    class xpay extends EpayPlugin {
		var $plugin_info;

		function pluginInstall($args) 
		{
			// mkdir
			FileHandler::makeDir(sprintf(_XE_PATH_."files/epay/%s/key",$args->plugin_srl));
			FileHandler::makeDir(sprintf(_XE_PATH_."files/epay/%s/log",$args->plugin_srl));
			// copy files
			FileHandler::copyFile(_XE_PATH_.'modules/epay/plugins/xpay/.htaccess',sprintf(_XE_PATH_."files/epay/%s/.htaccess",$args->plugin_srl));
			FileHandler::copyFile(_XE_PATH_.'modules/epay/plugins/xpay/readme.txt',sprintf(_XE_PATH_."files/epay/%s/readme.txt",$args->plugin_srl));
			FileHandler::copyFile(_XE_PATH_.'modules/epay/plugins/xpay/key/pgcert.pem',sprintf(_XE_PATH_."files/epay/%s/key/pgcert.pem",$args->plugin_srl));
		}

		function xpay() {
			parent::EpayPlugin();
		}

		function init(&$args) {
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
		function getFormData($args) {
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

			$LGD_MID = $this->plugin_info->mert_id;
			Context::set('LGD_MID', $LGD_MID);
			$usablepay = array();
			if ($this->plugin_info->paymethod_card=='Y') $usablepay[] = 'SC0010';
			if ($this->plugin_info->paymethod_directbank=='Y') $usablepay[] = 'SC0030';
			if ($this->plugin_info->paymethod_virtualbank=='Y') $usablepay[] = 'SC0040';
			if ($this->plugin_info->paymethod_phone=='Y') $usablepay[] = 'SC0060';
			Context::set('LGD_CUSTOM_USABLEPAY', implode('-',$usablepay));

			$oTemplate = &TemplateHandler::getInstance();
			$tpl_path = _XE_PATH_."modules/epay/plugins/xpay/tpl";
			$tpl_file = 'formdata.html';
			$form_data = $oTemplate->compile($tpl_path, $tpl_file);

			$output = new Object();
			$output->data = $form_data;
			return $output;
		}
		function processReview($args) {
			$xpay_home = sprintf(_XE_PATH_."files/epay/%s", $args->plugin_srl);
			$configPath = _XE_PATH_."modules/epay/plugins/xpay/libs";

			$LGD_MID = $this->plugin_info->mert_id;
			$LGD_PAYKEY = $this->plugin_info->mert_key;
			$LGD_OID = $args->order_srl;
			$LGD_AMOUNT= $args->price;
			$LGD_TIMESTAMP=date('YmdHiS');


			$config = $this->getConfig();

			require_once(_XE_PATH_."modules/epay/plugins/xpay/libs/XPayClient.php");
			$xpay = &new XPayClient($configPath, $config, $this->plugin_info->cst_platform);
			$xpay->config[$LGD_MID] = $LGD_PAYKEY;
			$xpay->config['log_dir'] = $xpay_home . '/log';
			$xpay->Init_TX($LGD_MID);
			$LGD_HASHDATA = md5($LGD_MID.$LGD_OID.$LGD_AMOUNT.$LGD_TIMESTAMP.$LGD_PAYKEY);
			$LGD_CUSTOM_PROCESSTYPE = "TWOTR";

			Context::set('price', $args->price);
			Context::set('order_srl', $args->order_srl);
			Context::set('timestamp', $LGD_TIMESTAMP);
			Context::set('hashdata', $LGD_HASHDATA);
			Context::set('processtype', $LGD_CUSTOM_PROCESSTYPE);

			$oTemplate = &TemplateHandler::getInstance();
			$tpl_path = _XE_PATH_."modules/epay/plugins/xpay/tpl";
			$tpl_file = 'review.html';
			$tpl_data = $oTemplate->compile($tpl_path, $tpl_file);

			$output = new Object();
			$output->add('tpl_data', $tpl_data);
			return $output;
		}

		function getConfig()
		{
			$xpay_home = sprintf(_XE_PATH_."files/epay/%s", $this->plugin_info->plugin_srl);

			$config = array();
			$config['server_id'] = '01';
			$config['timeout'] = '60';
			$config['log_level'] = '4';
			$config['verify_cert'] = '1';
			$config['verify_host'] = '1';
			$config['report_error'] = '1';
			$config['output_UTF8'] = '1';
			$config['auto_rollback'] = '1';
			$config['log_dir'] = $xpay_home . '/log';
			$config[$this->plugin_info->mert_id] = $this->plugin_info->mert_key;
			$config['url'] = 'https://xpayclient.lgdacom.net/xpay/Gateway.do';
			$config['test_url'] = 'https://xpayclient.lgdacom.net:7443/xpay/Gateway.do';
			$config['aux_url'] = 'http://xpayclient.lgdacom.net:7080/xpay/Gateway.do';
			return $config;
		}


		function processPayment($args) {
			$configPath = _XE_PATH_."modules/epay/plugins/xpay/libs";
			$xpay_home = sprintf(_XE_PATH_."files/epay/%s", $args->plugin_srl);

			$CST_PLATFORM               = Context::get('cst_platform');
			$CST_MID                    = Context::get('cst_mid');
			$LGD_MID                    = Context::get('lgd_mid');
			$LGD_PAYKEY                 = Context::get('lgd_paykey');

			$config = $this->getConfig();

			require_once(_XE_PATH_."modules/epay/plugins/xpay/libs/XPayClient.php");
			$xpay = &new XPayClient($configPath, $config, $CST_PLATFORM);
			$xpay->config[$this->plugin_info->mert_id] = $this->plugin_info->mert_key;
			$xpay->Init_TX($LGD_MID);    
			
			$xpay->Set("LGD_TXNAME", "PaymentByKey");
			$xpay->Set("LGD_PAYKEY", $LGD_PAYKEY);

			$vars = Context::getRequestVars();
			debugPrint($vars);

			/*
			 * 2. 최종결제 요청 결과처리
			 *
			 * 최종 결제요청 결과 리턴 파라미터는 연동메뉴얼을 참고하시기 바랍니다.
			 */
			$utf8VACTName = '';
			$utf8VACTInputName = '';
			if ($xpay->TX()) {
				$utf8ResultMsg = $xpay->Response_Msg();
				$utf8VACTName = $xpay->Response('LGD_SAOWNER');
				$utf8VACTInputName = $xpay->Response('LGD_PAYER');

				// error check
				if ($xpay->Response_Code() != '0000') 
				{
					$output = new Object(-1, $utf8ResultMsg);
					$output->add('state', '3'); // failure
				}
				else
				{
					$output = new Object(0, $utf8ResultMsg);
					if ($this->getPaymethod($xpay->Response('LGD_PAYTYPE',0))=='VA')
					{
						$output->add('state', '1'); // not completed
					} else {
						$output->add('state', '2'); // completed (success)
					}
				}

				/*
				//1)결제결과 화면처리(성공,실패 결과 처리를 하시기 바랍니다.)
				echo "결제요청이 완료되었습니다.  <br>";
				echo "TX Response_code = " . $xpay->Response_Code() . "<br>";
				echo "TX Response_msg = " . iconv('EUC-KR','UTF-8',$xpay->Response_Msg()) . "<p>";
					
				echo "거래번호 : " . $xpay->Response("LGD_TID",0) . "<br>";
				echo "상점아이디 : " . $xpay->Response("LGD_MID",0) . "<br>";
				echo "상점주문번호 : " . $xpay->Response("LGD_OID",0) . "<br>";
				echo "결제금액 : " . $xpay->Response("LGD_AMOUNT",0) . "<br>";
				echo "결과코드 : " . $xpay->Response("LGD_RESPCODE",0) . "<br>";
				echo "결과메세지 : " . $xpay->Response("LGD_RESPMSG",0) . "<p>";
					
				$keys = $xpay->Response_Names();
				foreach($keys as $name) {
					echo $name . " = " . $xpay->Response($name, 0) . "<br>";
				}
				  
				echo "<p>";
				 */
				   
				/*
				if( "0000" == $xpay->Response_Code() ) {
					//최종결제요청 결과 성공 DB처리
					echo "최종결제요청 결과 성공 DB처리하시기 바랍니다.<br>";

					//최종결제요청 결과 성공 DB처리 실패시 Rollback 처리
					$isDBOK = true; //DB처리 실패시 false로 변경해 주세요.
					if( !$isDBOK ) {
						echo "<p>";
						$xpay->Rollback("상점 DB처리 실패로 인하여 Rollback 처리 [TID:" . $xpay->Response("LGD_TID",0) . ",MID:" . $xpay->Response("LGD_MID",0) . ",OID:" . $xpay->Response("LGD_OID",0) . "]");            		            		
							
						echo "TX Rollback Response_code = " . $xpay->Response_Code() . "<br>";
						echo "TX Rollback Response_msg = " . iconv('EUC-KR','UTF-8',$xpay->Response_Msg()) . "<p>";
							
						if( "0000" == $xpay->Response_Code() ) {
							echo "자동취소가 정상적으로 완료 되었습니다.<br>";
						}else{
							echo "자동취소가 정상적으로 처리되지 않았습니다.<br>";
						}
					}            	
				}else{
					//최종결제요청 결과 실패 DB처리
					echo "최종결제요청 결과 실패 DB처리하시기 바랍니다.<br>";            	            
				}
				 */
			}else {

				/*
				//2)API 요청실패 화면처리
				echo "결제요청이 실패하였습니다.  <br>";
				echo "TX Response_code = " . $xpay->Response_Code() . "<br>";
				echo "TX Response_msg = " . iconv('EUC-KR','UTF-8',$xpay->Response_Msg()) . "<p>";
					
				//최종결제요청 결과 실패 DB처리
				echo "최종결제요청 결과 실패 DB처리하시기 바랍니다.<br>";            	                        
				 */
				$utf8ResultMsg = "결제요청이 실패하였습니다.";
				$output = new Object(-1, $utf8ResultMsg);
				$output->add('state', '3'); // failure
			}


			$output->add('payment_method', $this->getPaymethod($xpay->Response('LGD_PAYTYPE',0)));
			$output->add('payment_amount', $xpay->Response('LGD_AMOUNT',0));
			$output->add('result_code', $xpay->Response_Code());
			$output->add('result_message', $utf8ResultMsg);
			$output->add('vact_num', $xpay->Response('LGD_ACCOUNTNUM',0)); // 계좌번호
			$output->add('vact_bankname', $xpay->Response('LGD_FINANCENAME',0)); //은행코드
			$output->add('vact_bankcode', $xpay->Response('LGD_FINANCECODE',0)); //은행코드
			$output->add('vact_name', $utf8VACTName); // 예금주
			$output->add('vact_inputname', $utf8VACTInputName); // 송금자
			$output->add('vact_regnum', ''); //송금자 주번
			$output->add('vact_date', ''); // 송금일자
			$output->add('vact_time', ''); // 송금시간
			$output->add('pg_tid', $xpay->Response('LGD_TID',0));

			$original = array();
			$keys = $xpay->Response_Names();
			foreach($keys as $name) {
				$original[] = $name . " = " . $xpay->Response($name, 0) . "\n";
			}
			$output->add('ORIGINAL', $original);
			return $output;
		}

		function processReport(&$transaction) {
			$vars = Context::getRequestVars();

			$tid = Context::get('LGD_TID');
			$casflag = Context::get('LGD_CASFLAG');

			$output = new Object();

			// check for TID
			if ($transaction->pg_tid != $tid)
			{
				echo "TID mismatch";
				$output->setError(-1);
				$output->setMessage('TID mismatch');
				$output->state = '1'; // not completed
				return $output;
			}
			/// LGD_CASFLAG가 'I' 이면 '입금'
 			if ($casflag != 'I')
			{
				echo "CASFLAG mismatch : " . $casflag;
				$output->setError(-1);
				$output->setMessage('CASFLAG mismatch');
				$output->state = '1'; // not completed
				return $output;
			}

			$output->order_srl = Context::get('LGD_OID');
			$output->amount = Context::get('LGD_CASTAMOUNT');
			if ($output->amount == $transaction->payment_amount)
			{
				echo "OK";
				$output->setError(0);
				$output->state = '2'; // successfully completed
			}
			else
			{
				echo "Amount mismatch";
				$output->setError(-1);
				$output->setMessage('amount mismatch');
				$output->state = '1'; // not completed
			}
			return $output;
		}

		function getReceipt($pg_tid)
		{
			$authdata = md5($this->plugin_info->mert_id.$pg_tid.$this->plugin_info->mert_key);
			Context::set('tid', $pg_tid);
			Context::set('authdata', $authdata);
			$oTemplate = &TemplateHandler::getInstance();
			$tpl_path = _XE_PATH_."modules/epay/plugins/xpay/tpl";
			$tpl_file = 'receipt.html';
			$tpl = $oTemplate->compile($tpl_path, $tpl_file);
			return $tpl;
		}

		function getReport() 
		{
			$vars = Context::getRequestVars();
			debugPrint($vars);

			$output = new Object();
			$output->order_srl = Context::get('LGD_OID');
			$output->amount = Context::get('LGD_CASTAMOUNT');
			return $output;
		}

		function getPaymethod($paymethod) {
			switch ($paymethod) {
				case 'SC0010':
					return 'CC';
				case 'SC0030':
					return 'IB';
				case 'SC0040':
					return 'VA';
				case 'SC0060':
					return 'MP';
				default:
					return '  ';
			}
		}

		function getBankName($code) {
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
/* End of file xpay.plugin.php */
/* Location: ./modules/epay/plugins/xpay/xpay.plugin.php */
