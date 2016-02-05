<?php
/**
 * vi:set ts=4 sw=4 noexpandtab fileencoding=utf-8:
 * @class epayView
 * @author NURIGO(contact@nurigo.net)
 * @brief epay view
 **/
class epayView extends epay
{
	function init()
	{
		Context::set('admin_bar', 'false');
		Context::set('hide_trolley', 'true');

		if (!$this->module_info->skin) $this->module_info->skin = 'default';
		$this->setTemplatePath($this->module_path."skins/{$this->module_info->skin}");
	}

	/**
	 * @brief 주문번호 생성
	 **/
	function makeOrderKey()
	{
		$randval = rand(100000, 999999);
		$usec = explode(" ", microtime());
		$str_usec = str_replace(".", "", strval($usec[0]));
		$str_usec = substr($str_usec, 0, 6);
		return date("YmdHis") . $str_usec . $randval;
	}

	/**
	 * $in_args->epay_module_srl
	 * $in_args->module_srl
	 * $in_args->item_name
	 * $in_args->price
	 * $in_args->target_module
	 * $in_args->join_form
	 * $in_args->purchaser_name
	 * $in_args->purchaser_email
	 * $in_args->purchaser_telnum
	 */
	function getPaymentForm($in_args)
	{
		$oModuleModel = &getModel('module');
		$oEpayModel = &getModel('epay');

		if (!$in_args->epay_module_srl)
		{
			return new Object(-1, 'msg_invalid_epay_module');
		}

		Context::set('epay_module_srl', $in_args->epay_module_srl);
		Context::set('module_srl', $in_args->module_srl);
		Context::set('item_name', $in_args->item_name);
		Context::set('price', $in_args->price);
		Context::set('target_module', $in_args->target_module);
		Context::set('join_form', $in_args->join_form);
		$logged_info = Context::get('logged_info');
        if($logged_info)
        {
            if(!$in_args->purchaser_name) $in_args->purchaser_name = $logged_info->nick_name;
            if(!$in_args->purchaser_email) $in_args->purchaser_email = $logged_info->email_address;
            if(!$in_args->purchaser_telnum) $in_args->purchaser_telnum = "010-0000-0000";
        }
        else if(!$logged_info)
        {
            if(!$in_args->purchaser_name) $in_args->purchaser_name = 'GUEST';
            if(!$in_args->purchaser_email) $in_args->purchaser_email = '';
            if(!$in_args->purchaser_telnum) $in_args->purchaser_telnum = "010-0000-0000";
        }
		Context::set('purchaser_name', $in_args->purchaser_name);
		Context::set('purchaser_email', $in_args->purchaser_email);
		Context::set('purchaser_telnum', $in_args->purchaser_telnum);

		$_SESSION['epay_module_srl'] = $in_args->epay_module_srl;
		$_SESSION['order_srl'] = $in_args->order_srl;

		$module_info = $oModuleModel->getModuleInfoByModuleSrl($in_args->epay_module_srl);
		$oModuleModel->syncSkinInfoToModuleInfo($module_info);
		if(!$module_info) return new Object(-1, 'msg_invalid_epay_module');
		if(!$module_info->skin) $module_info->skin = 'default';
		Context::set('epay_module_info', $module_info);

		$form_data = '';
		if($_COOKIE['mobile'] != "true")
		{
			if ($module_info->plugin_srl)
			{
				$plugin = $oEpayModel->getPlugin($module_info->plugin_srl);
				$output = $plugin->getFormData($in_args);
				if (!$output->toBool()) return $output;
				$form_data = $output->data;
			}

			if ($module_info->plugin2_srl)
			{
				$plugin2 = $oEpayModel->getPlugin($module_info->plugin2_srl);
				$output = $plugin2->getFormData($in_args);
				if (!$output->toBool()) return $output;
				$form_data .= $output->data;
			}

			if ($module_info->plugin3_srl)
			{
				$plugin3 = $oEpayModel->getPlugin($module_info->plugin3_srl);
				$output = $plugin3->getFormData($in_args);
				if (!$output->toBool()) return $output;
				$form_data .= $output->data;
			}

			if ($module_info->plugin4_srl)
			{
				$plugin4 = $oEpayModel->getPlugin($module_info->plugin4_srl);
				$output = $plugin4->getFormData($in_args);
				if (!$output->toBool()) return $output;
				$form_data .= $output->data;
			}

			if ($module_info->plugin5_srl)
			{
				$plugin5 = $oEpayModel->getPlugin($module_info->plugin5_srl);
				$output = $plugin5->getFormData($in_args);
				if (!$output->toBool()) return $output;
				$form_data .= $output->data;
			}

			$payment_methods = array();
			if($module_info->pg1_module_srl) $payment_methods = array_merge($payment_methods, $oEpayModel->getPaymentMethods($module_info->pg1_module_srl));
			if($module_info->pg2_module_srl) $payment_methods = array_merge($payment_methods, $oEpayModel->getPaymentMethods($module_info->pg2_module_srl));
			if($module_info->pg3_module_srl) $payment_methods = array_merge($payment_methods, $oEpayModel->getPaymentMethods($module_info->pg3_module_srl));
			if($module_info->pg4_module_srl) $payment_methods = array_merge($payment_methods, $oEpayModel->getPaymentMethods($module_info->pg4_module_srl));
			Context::set('payment_methods', $payment_methods);
		}

		if($_COOKIE['mobile'] == "true")
		{
			if ($module_info->plugin_srl_mobile1)
			{
				$plugin = $oEpayModel->getPlugin($module_info->plugin_srl_mobile1);
				$output = $plugin->getFormData($in_args);
				if (!$output->toBool()) return $output;
				$form_data .= $output->data;
			}
			if ($module_info->plugin_srl_mobile2)
			{
				$plugin = $oEpayModel->getPlugin($module_info->plugin_srl_mobile2);
				$output = $plugin->getFormData($in_args);
				if (!$output->toBool()) return $output;
				$form_data .= $output->data;
			}
			$payment_methods = array();
			if($module_info->m_pg1_module_srl) $payment_methods = array_merge($payment_methods, $oEpayModel->getPaymentMethods($module_info->m_pg1_module_srl));
			if($module_info->m_pg2_module_srl) $payment_methods = array_merge($payment_methods, $oEpayModel->getPaymentMethods($module_info->m_pg2_module_srl));
			if($module_info->m_pg3_module_srl) $payment_methods = array_merge($payment_methods, $oEpayModel->getPaymentMethods($module_info->m_pg3_module_srl));

			Context::set('payment_methods', $payment_methods);
		}


		/*
		// before
		$output = ModuleHandler::triggerCall('epay.getFormData', 'before', $plugin);
		if(!$output->toBool()) return $output;
		 */

		/*
		// after
		$output = ModuleHandler::triggerCall('epay.getFormData', 'after', $form_data);
		if(!$output->toBool()) return $output;
		 */

		Context::set('form_data', $form_data);
		Context::set('order_srl', $in_args->order_srl);

		if($_COOKIE['mobile'] == "true")
		{
			$template_path = sprintf("%sm.skins/%s/",$this->module_path, $this->module_info->mskin);
			if(!is_dir($template_path)||!$this->module_info->mskin) {
					$this->module_info->mskin = 'default';
					$template_path = sprintf("%sm.skins/%s/",$this->module_path, $this->module_info->mskin);
			}
		}
		else
		{
			$template_path = $this->module_path."skins/{$module_info->skin}";
			if(!is_dir($template_path)||!$this->module_info->skin) {
				$this->module_info->skin = 'default';
				$template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
			}
		}

		$oTemplate = &TemplateHandler::getInstance();
		$payment_form = $oTemplate->compile($template_path, 'paymentform.html');
		$output = new Object();
		$output->data = $payment_form;
		return $output;

	}

	function dispEpayExtra1()
	{
		$oEpayModel = &getModel('epay');
		$plugin = $oEpayModel->getPlugin(Context::get('plugin_srl'));
		$output = $plugin->dispExtra1($this);
		Context::set('content', $output);

		//$this->setLayoutFile('default_layout');
		$this->setTemplatePath($this->module_path."tpl");
		$this->setTemplateFile('extra');
	}
	function dispEpayExtra2()
	{
		$oEpayModel = &getModel('epay');
		$plugin = $oEpayModel->getPlugin(Context::get('plugin_srl'));
		$output = $plugin->dispExtra2($this);
		Context::set('content', $output);

		//$this->setLayoutFile('default_layout');
		$this->setTemplatePath($this->module_path."tpl");
		$this->setTemplateFile('extra');
	}
	function dispEpayExtra3()
	{
		$oEpayModel = &getModel('epay');
		$plugin = $oEpayModel->getPlugin(Context::get('plugin_srl'));
		$output = $plugin->dispExtra3($this);
		Context::set('content', $output);

		//$this->setLayoutFile('default_layout');
		$this->setTemplatePath($this->module_path."tpl");
		$this->setTemplateFile('extra');
	}
	function dispEpayExtra4()
	{
		$oEpayModel = &getModel('epay');
		$plugin = $oEpayModel->getPlugin(Context::get('plugin_srl'));
		$output = $plugin->dispExtra4($this);
		Context::set('content', $output);

		//$this->setLayoutFile('default_layout');
		$this->setTemplatePath($this->module_path."tpl");
		$this->setTemplateFile('extra');
	}

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
						debugPrint('key : ' . $key);
						debugPrint('val : ' . $val);
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

	function dispEpayTransaction()
	{
		if($_COOKIE['mobile'] != "true")
		{
			if(!$this->module_info->skin) $this->module_info->skin = 'default';
			$skin = $this->module_info->skin;
			$this->setTemplatePath(sprintf('%sskins/%s', $this->module_path, $skin));
		}

		/**
		 * inipaymobile P_RETURN_URL 페이지 처리를 위한 코드
		 * ISP 결제시 r_page에 order_srl이 담겨져옴, 결제처리는 P_NOTI_URL이 호출되므로 여기서는 그냥 결과만 보여줌
		 */
		if(Context::get('r_page'))
		{
			$vars = Context::getRequestVars();
			$vars->P_RMESG1 = iconv('EUC-KR','UTF-8',$vars->P_RMESG1);
			$mid = $_SESSION['xe_mid'];
			Context::set('order_srl', Context::get('r_page'));
			$return_url = getNotEncodedUrl('','mid',$mid,'act','dispNcartOrderComplete','order_srl',Context::get('order_srl'));
			$this->setRedirectUrl($return_url);
			return;
		}

		/**
		 * inipaymobile P_NEXT_URL 페이지 처리를 위한 코드
		 * 가상계좌, 안심클릭시 n_page에 order_srl이 담겨져옴, P_REQ_URL에 POST로 P_TID와 P_MID를 넘겨줘야 결제요청이 완료됨
		 */
		if(Context::get('n_page'))
		{
			$vars = Context::getRequestVars();
			$vars->P_RMESG1 = iconv('EUC-KR','UTF-8',$vars->P_RMESG1);
			$mid = $_SESSION['xe_mid'];

			// P_TID에 값이 없으면 취소되었음
			if(!$vars->P_TID)
			{
				Context::set('order_srl', Context::get('n_page'));
				$return_url = getNotEncodedUrl('','mid',$mid,'act','dispNcartOrderComplete','order_srl',Context::get('order_srl'));
				$this->setRedirectUrl($return_url);
				return;
			}

			$post_data = array('P_TID'=>$vars->P_TID,'P_MID'=>$vars->P_MID);
			$response = $this->getRemoteResource($vars->P_REQ_URL, null, 3, 'POST', 'application/x-www-form-urlencoded',  array(), array(), $post_data);
			parse_str($response, $output);
			$P_RMESG1 = iconv('EUC-KR','UTF-8',$output['P_RMESG1']);

			foreach($output as $key=>$val)
			{
				Context::set($key, $val);
			}

			// P_NOTI에 plugin_srl, epay_module_srl 등을 담고 있음
			parse_str($vars->P_NOTI, $output);
			foreach($output as $key=>$val)
			{
				Context::set($key, $val);
			}

			// inipaymobile_pass = TRUE로 해주어서 inipaymobile에서 결제처리되도록 함
			$_SESSION['inipaymobile_pass'] = TRUE;

			$oEpayController = &getController('epay');
			$output = $oEpayController->procEpayDoPayment();
			if(is_object($output) && method_exists($output, 'toBool'))
			{
				if(!$output->toBool())
				{
					return $output;
				}
			}
			if($oEpayController->get('return_url')) $this->setRedirectUrl($oEpayController->get('return_url'));

			return;
		}

		$logged_info = Context::get('logged_info');
		if(!$logged_info) return new Object(-1, 'msg_login_required');


		if($logged_info)
		{
			if(Context::get('start_date'))
			{
				$start_date = date("Ymd", mktime(0, 0, 0, date("m") - Context::get('start_date'), date("d"), date("Y")));
				if(Context::get('start_date') == 'a') $start_date = null;
			}

			$args->member_srl = $logged_info->member_srl;
			$args->page = Context::get('page');
			$args->regdate_more = $start_date;

			$output = executeQueryArray('epay.getTransactionByMemberSrl', $args);

			Context::set('total_count', $output->total_count);
			Context::set('total_page', $output->total_page);
			Context::set('page', $output->page);
			Context::set('page_navigation', $output->page_navigation);

			$epay_user_info = $output->data;

			$today = date("Ymd",mktime(0,0,0,date("m"), date("d")-5, date("Y")));
			foreach($epay_user_info as $k => $v)
			{
				if(substr($v->regdate,0,8) < $today && $v->state != 2)
				{
					$v->state = 3;
				}

				if($v->state == 1) $v->result_message = "결제진행중";
				else if($v->state == 2) $v->result_message = "결제성공";
				else $v->result_message = "결제실패";

				if(is_array($v->order_title)) $v->order_title = implode($v->order_title,',');
				if(!$v->order_title) $v->order_title = $v->target_module;

				switch($v->payment_method)
				{
					case "CC":
						$v->payment_method = "신용카드";
						break;
					case "BT":
						$v->payment_method = "무통장 입금";
						break;
					case "IB":
						$v->payment_method = "실시간계좌이체";
						break;
					case "VA":
						$v->payment_method = "가상계좌";
						break;
					case "MP":
						$v->payment_method = "휴대폰 결제";
						break;
					case "PP":
						$v->payment_method = "페이팔";
						break;
				}

				$v->extra_vars = unserialize($v->extra_vars);
			}
			Context::set("epay_user_info", $output->data);
		}

		$this->setTemplateFile('transaction');
	}

	function dispEpayError()
	{
		$oEpayModel = &getModel('epay');
		$transaction_info = $oEpayModel->getTransactionInfo(Context::get('transaction_srl'));
		Context::set('transaction_info', $transaction_info);
		$this->setTemplatePath($this->module_path."tpl");
		$this->setTemplateFile('error');
	}
}
/* End of file epay.view.php */
/* Location: ./modules/epay/epay.view.php */
