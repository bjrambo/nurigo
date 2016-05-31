<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  textmessageModel
 * @author wiley(wiley@nurigo.net)
 * @brief  textmessageModel
 */
class textmessageModel extends textmessage 
{

	function init() { }

	/**
	 * @brief 모듈 환경설정값 가져오기
	 */
	function getModuleConfig() 
	{
		if (!$GLOBALS['__textmessage_config__']) 
		{
			$oModuleModel = &getModel('module');
			$config = $oModuleModel->getModuleConfig('textmessage');

			// get logged_info
			$oMemberModel = &getModel('member');
			$logged_info = Context::get('logged_info');
			// 회원정보 보기 페이지에서 $logged_info->password를 unset시키기 때문에 새로 가져와야 한다
			if(!$logged_info->password) $logged_info = $oMemberModel->getMemberInfoByMemberSrl($logged_info->member_srl);

			if($logged_info)
			{
				$config->cs_user_id = $logged_info->user_id;
				$config->cs_password = $logged_info->password;
			}

			// country code
			if (!$config->default_country) $config->default_country = '82';
			if ($config->default_country == '82') $config->limit_bytes = 80;
			else $config->limit_bytes = 160;

			// callback
			$callback = explode("|@|", $config->callback); // source
			$config->a_callback = $callback;        // array
			$config->s_callback = join($callback);  // string

			// admin_phone
			if (!is_array($config->admin_phones))
				$config->admin_phones = explode("|@|", $config->admin_phones);

			$config->crypt = 'MD5';

			$GLOBALS['__textmessage_config__'] = $config;
		}
		return $GLOBALS['__textmessage_config__'];
	}

	/**
	 *  @brief Sln Reg Key 가져오기
	 */
	function getSlnRegKey() 
	{
		if (!file_exists($this->module_path.'resale.info.php')) return false;
		require_once($this->module_path.'resale.info.php');
		return __SOLUTION_REGISTRATION_KEY__;
	}

	/**
	 *  @brief CoolSMS class 객체 가져오기
	 */
	function &getCoolSMS($basecamp=false) 
	{
		$config = $this->getModuleConfig();
		if (!class_exists('coolsms')) require_once('coolsms.php');
		
		if($basecamp)
			$sms = new coolsms($config->cs_user_id, $config->cs_password, TRUE);
		else
			$sms = new coolsms($config->api_key, $config->api_secret);

		return $sms;
	}

	/**
	 * @brief 환경값 읽어오기
	 */
	function getConfig() 
	{
		$config = $this->getModuleConfig('textmessage');
		if (!$config->api_key || !$config->api_secret) 
		{
			return false;
		}

		$config->cs_cash=0;
		$config->cs_point=0;
		$config->cs_mdrop=0;

		$sms = &$this->getCoolSMS();
		if ($sms->balance()) 
		{
			$remain = $sms->balance();
			$config->cs_cash = $remain->cash;
			$config->cs_point = $remain->point;
			$config->sms_price = 20;
			$config->lms_price = 50;
			$config->mms_price = 200;
			
			$config->sms_volume = ((int)$config->cs_cash / (int)$config->sms_price) + ((int)$config->cs_point / (int)$config->sms_price) + (int)$cs_mdrop;
			$config->lms_volume = ((int)$config->cs_cash / (int)$config->lms_price) + ((int)$config->cs_point / (int)$config->lms_price) + ((int)$cs_mdrop / 3);
			$config->mms_volume = ((int)$config->cs_cash / (int)$config->mms_price) + ((int)$config->cs_point / (int)$config->mms_price) + ((int)$cs_mdrop / 10);

			if ($remain->code)
			{
				Context::set('cs_is_logged', false);
				switch ($remain->code)
				{
					case '20':
						Context::set('cs_error_message', '<font color="red">존재하지 않는 아이디이거나 패스워드가 틀립니다.</font><br /><a href="' . getUrl('act','dispTextmessageAdminConfig') . '">설정변경</a>');
						break;
					case '30':
						Context::set('cs_error_message', '<font color="red">사용가능한 SMS 건수가 없습니다.</font>');
						break;
					default:
						Context::set('cs_error_message', '<font color="red">오류코드:'.$remain->code.'</font>');
				}
			}
			else
			{
				Context::set('cs_is_logged', true);
			}
		} 
		else 
		{
			Context::set('cs_is_logged', false);
			Context::set('cs_error_message', '<font color="red">서비스 서버에 연결할 수 없습니다.<br />일부 웹호스팅에서 외부로 나가는 포트 접속을 허용하지 않고 있습니다.<br /></font>');
		}
		Context::set('cs_cash', $config->cs_cash);
		Context::set('cs_point', $config->cs_point);
		Context::set('cs_mdrop', $config->cs_mdrop);
		Context::set('sms_price', $config->sms_price);
		Context::set('lms_price', $config->lms_price);
		Context::set('mms_price', $config->mms_price);
		Context::set('sms_volume', $config->sms_volume);

		return $config;
	}

	/**
	 * @brief Config 에서 원하는 값 가져오기
	 */
	function getConfigValue(&$obj, $key, $type=null) 
	{
		$return_value = null;
		$config = $this->getModuleConfig();
		$fieldname = $config->{$key};
		if (!$fieldname) return null;

		// 기본필드에서 확인
		if ($obj->{$fieldname}) 
		{
			$return_value = $obj->{$fieldname};
		}

		// 확장필드에서 확인
		if ($obj->extra_vars) 
		{
			$extra_vars = unserialize($obj->extra_vars);
			if ($extra_vars->{$fieldname}) 
			{
				$return_value = $extra_vars->{$fieldname};
			}
		}
		if ($type=='tel' && is_array($return_value)) 
		{
			$return_value = implode($return_value);
		}

		return $return_value;
	}

	/**
	 * @brief CashInfo 가져오기
	 **/
	function getCashInfo($basecamp=false) 
	{
		$config = $this->getModuleConfig();
		$sms = &$this->getCoolSMS($basecamp);
		
		// get cash info
		$result = $sms->balance();
		
		$obj = new Object();
		$obj->add('cash', $result->cash);
		$obj->add('point', $result->point);
		$obj->add('deferred_payment', $result->deferred_payment);
		$obj->add('sms_price', '20');
		$obj->add('lms_price', '50');
		$obj->add('mms_price', '200');
		return $obj;
	}
	
	/*
	 * @brief 전송결과값 가져오기
	 */
	function getResult($args=null)
	{
		$sms = &$this->getCoolSMS();
		$result = $sms->sent($args);
		return $result;
	}

	/**
	 * @brief 발신번호 리스트 가져오기
	 */
	function getSenderNumbers()
	{
		$sms = &$this->getCoolSMS();
		$result = $sms->get_senderid_list();
		return $result;
	}
}
/* End of file textmessage.model.php */
/* Location: ./modules/textmessage.model.php */
