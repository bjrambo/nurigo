<?php

/**
 *
 *   Copyright (C) 2008-2015 NURIGO
 *   http://www.coolsms.co.kr
 *
 **/
class coolsms
{
	private static $api_key;
	private static $api_secret;
	private static $coolsms_user;
	private static $host = "http://api.coolsms.co.kr/";
	private static $resource;
	private static $version = "1.6";
	private static $sdk_version = "1.1";
	private static $path;
	private static $method;
	private static $timestamp;
	private static $salt;
	private static $result;
	private static $basecamp;
	private static $user_agent;
	private static $content;
	public static $error_flag = false;

	/**
	 * @brief construct
	 */
	public function __construct($api_key, $api_secret, $basecamp = false)
	{
		if($basecamp)
		{
			self::$coolsms_user = $api_key;
			self::$basecamp = true;
		}
		else
		{
			self::$api_key = $api_key;
		}

		self::$api_secret = $api_secret;
		self::$user_agent = $_SERVER['HTTP_USER_AGENT'];
	}

	/**
	 * @brief process curl
	 */
	public static function curlProcess()
	{
		$ch = curl_init();
		// Set host. 1 = POST , 0 = GET
		if(self::$method == 1)
		{
			$host = sprintf("%s%s/%s/%s", self::$host, self::$resource, self::$version, self::$path);
		}
		else
		{
			$host = sprintf("%s%s/%s/%s?%s", self::$host, self::$resource, self::$version, self::$path, self::$content);
		}

		curl_setopt($ch, CURLOPT_URL, $host);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSLVERSION, 3); // SSL 버젼 (https 접속시에 필요)
		curl_setopt($ch, CURLOPT_HEADER, 0); // 헤더 출력 여부
		curl_setopt($ch, CURLOPT_POST, self::$method); // Post Get 접속 여부

		// Set POST DATA
		if(self::$method)
		{
			$header = array("Content-Type:multipart/form-data");

			// route가 있으면 header에 붙여준다.
			if(self::$content['route'])
			{
				$header[] = "User-Agent:" . self::$content['route'];
			}

			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			curl_setopt($ch, CURLOPT_POSTFIELDS, self::$content);
		}
		curl_setopt($ch, CURLOPT_TIMEOUT, 10); // TimeOut 값
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 결과값을 받을것인지

		self::$result = json_decode(curl_exec($ch));

		// unless http status code is 200. throw exception.
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if($http_code != 200)
		{
			self::$error_flag = true;
		}

		// Check connect errors
		if(curl_errno($ch))
		{
			self::$error_flag = true;
			self::$result = curl_error($ch);
		}

		curl_close($ch);
	}

	/**
	 * set http body content
	 */
	private static function setContent($options)
	{
		if(self::$method)
		{
			self::$content = array();
			foreach($options as $key => $val)
			{
				if($key != "image")
				{
					self::$content[$key] = sprintf("%s", $val);
				}
				else
				{
					self::$content[$key] = "@" . realpath("./$val");
				}
			}
		}
		else
		{
			foreach($options as $key => $val)
			{
				self::$content .= $key . "=" . urlencode($val) . "&";
			}
		}
	}

	/**
	 * make a signature with hash_hamac then return the signature
	 */
	private static function getSignature()
	{
		return hash_hmac('md5', (string)self::$timestamp . self::$salt, self::$api_secret);
	}

	/**
	 * set authenticate information
	 */
	private static function addInfos($options)
	{
		self::$salt = uniqid();
		self::$timestamp = (string)time();
		if(!$options->User_Agent)
		{
			$options->User_Agent = sprintf("PHP REST API %s", self::$version);
		}
		if(!$options->os_platform)
		{
			$options->os_platform = self::getOS();
		}
		if(!$options->dev_lang)
		{
			$options->dev_lang = sprintf("PHP %s", phpversion());
		}
		if(!$options->sdk_version)
		{
			$options->sdk_version = sprintf("PHP SDK %s", self::$sdk_version);
		}

		$options->salt = self::$salt;
		$options->timestamp = self::$timestamp;
		if(self::$basecamp)
		{
			$options->coolsms_user = self::$coolsms_user;
		}
		else
		{
			$options->api_key = self::$api_key;
		}
		$options->signature = self::getSignature();

		if(in_array($options->type, array('ata', 'cta')) && isset($options->messages))
		{
			self::sendATA($options);
		}
		else
		{
			self::setContent($options);
			self::curlProcess();
		}
	}

	/**
	 * $resource
	 * 'sms', 'senderid', 'alimtalk'
	 * $method
	 * GET = 0, POST, 1
	 * $path
	 * 'send' 'sent' 'cancel' 'balance'
	 */
	private static function setMethod($resource, $path, $method, $version = "1.6")
	{
		self::$resource = $resource;
		self::$path = $path;
		self::$method = $method;
		self::$version = $version;
	}

	/**
	 * @brief return result
	 */
	public static function getResult()
	{
		return self::$result;
	}

	/**
	 * @POST send method
	 * @param $options (options must contain api_key, salt, signature, to, from, text)
	 * @type, image, refname, country, datetime, mid, gid, subject, charset (optional)
	 * @returns object(recipient_number, group_id, message_id, result_code, result_message)
	 */
	public static function send($options)
	{
		if(in_array($options->type, array('ata', 'cta')))
		{
			self::setMethod('sms', 'send', 1, '2');
			if(isset($options->extension))
			{
				$options = self::setATAData($options);
			}
		}
		else
		{
			self::setMethod('sms', 'send', 1);
		}
		self::addInfos($options);
		return self::$result;
	}

	/**
	 * @GET sent method
	 * @param $options (options can be optional)
	 * @count,  page, s_rcpt, s_start, s_end, mid, gid (optional)
	 * @returns object(total count, list_count, page, data['type', 'accepted_time', 'recipient_number', 'group_id', 'message_id', 'status', 'result_code', 'result_message', 'sent_time', 'text'])
	 */
	public static function sent($options = null)
	{
		if(!$options)
		{
			$options = new stdClass();
		}
		self::setMethod('sms', 'sent', 0);
		self::addInfos($options);
		return self::$result;
	}

	/**
	 * @POST cancel method
	 * @options must contain api_key, salt, signature
	 * @mid, gid (either one must be entered.)
	 */
	public static function cancel($options)
	{
		self::setMethod('sms', 'cancel', 1);
		self::addInfos($options);
		return self::$result;
	}

	/**
	 * @GET balance method
	 * @options must contain api_key, salt, signature
	 * @return object(cash, point)
	 */
	public static function balance()
	{
		self::setMethod('sms', 'balance', 0);
		self::addInfos($options = new stdClass());
		return self::$result;
	}

	/**
	 * @GET status method
	 * @options must contain api_key, salt, signature
	 * @return object(registdate, sms_average, sms_sk_average, sms_kt_average, sms_lg_average, mms_average, mms_sk_average, mms_kt_average, mms_lg_average)
	 *   this method is made for Coolsms inc. internal use
	 */
	public static function status($options)
	{
		self::setMethod('sms', 'status', 0);
		self::addInfos($options);
		return self::$result;
	}

	/**
	 * @POST register method
	 * @options must contains api_key, salt, signature, phone, site_user(optional)
	 * @return object(handle_key, ars_number)
	 */
	public static function register($options)
	{
		self::setMethod('senderid', 'register', 1, "1.1");
		self::addInfos($options);
		return self::$result;
	}

	/**
	 * @POST verify method
	 * @options must contains api_key, salt, signature, handle_key
	 * return nothing
	 */
	public static function verify($options)
	{
		self::setMethod('senderid', 'verify', 1, "1.1");
		self::addInfos($options);
		return self::$result;
	}

	/**
	 * POST delete method
	 * $options must contains api_key, salt, signature, handle_key
	 * return nothing
	 */
	public static function delete($options)
	{
		self::setMethod('senderid', 'delete', 1, "1.1");
		self::addInfos($options);
		return self::$result;
	}

	/**
	 * GET list method
	 * $options must conatins api_key, salt, signature, site_user(optional)
	 * return json object(idno, phone_number, flag_default, updatetime, regdate)
	 */
	public static function get_senderid_list($options = null)
	{
		self::setMethod('senderid', 'list', 0, "1.1");
		self::addInfos($options);
		return self::$result;
	}

	/**
	 * POST set_default
	 * $options must contains api_key, salt, signature, handle_key, site_user(optional)
	 * return nothing
	 */
	public static function set_default($options)
	{
		self::setMethod('senderid', 'set_default', 1, "1.1");
		self::addInfos($options);
		return self::$result;
	}

	/**
	 * GET get_default
	 * $options must conatins api_key, salt, signature, site_user(optional)
	 * return json object(handle_key, phone_number)
	 */
	public static function get_default($options)
	{
		self::setMethod('senderid', 'get_default', 0, "1.1");
		self::addInfos($options);
		return self::$result;
	}

	/**
	 * POST register alimtalk
	 * options must contain api_key, salt, signature, yellow_id, templates
	 * return json array(request template list)
	 */
	public static function register_alimtalk($options)
	{
		self::setMethod('alimtalk', 'register', 1, '1');
		self::addInfos($options);
		return self::$result;
	}

	/**
	 * POST get alimtalk templates
	 * options must contain api_key, salt, signature, yellow_id
	 * return json array(request template list)
	 */
	public static function get_alimtalk_templates($options)
	{
		self::setMethod('alimtalk', "templates/{$options->yellow_id}", 0, '1');
		self::addInfos($options);
		return self::$result;
	}

	/**
	 * return user's current OS
	 */
	public static function getOS()
	{
		$user_agent = self::$user_agent;
		$os_platform = "Unknown OS Platform";
		$os_array = array(
			'/windows nt 10/i' => 'Windows 10',
			'/windows nt 6.3/i' => 'Windows 8.1',
			'/windows nt 6.2/i' => 'Windows 8',
			'/windows nt 6.1/i' => 'Windows 7',
			'/windows nt 6.0/i' => 'Windows Vista',
			'/windows nt 5.2/i' => 'Windows Server 2003/XP x64',
			'/windows nt 5.1/i' => 'Windows XP',
			'/windows xp/i' => 'Windows XP',
			'/windows nt 5.0/i' => 'Windows 2000',
			'/windows me/i' => 'Windows ME',
			'/win98/i' => 'Windows 98',
			'/win95/i' => 'Windows 95',
			'/win16/i' => 'Windows 3.11',
			'/macintosh|mac os x/i' => 'Mac OS X',
			'/mac_powerpc/i' => 'Mac OS 9',
			'/linux/i' => 'Linux',
			'/ubuntu/i' => 'Ubuntu',
			'/iphone/i' => 'iPhone',
			'/ipod/i' => 'iPod',
			'/ipad/i' => 'iPad',
			'/android/i' => 'Android',
			'/blackberry/i' => 'BlackBerry',
			'/webos/i' => 'Mobile'
		);

		foreach($os_array as $regex => $value)
		{
			if(preg_match($regex, $user_agent))
			{
				$os_platform = $value;
			}
		}
		return $os_platform;
	}

	/**
	 * return user's current browser
	 */
	public static function getBrowser()
	{
		$user_agent = self::$user_agent;
		$browser = "Unknown Browser";
		$browser_array = array(
			'/msie/i' => 'Internet Explorer',
			'/firefox/i' => 'Firefox',
			'/safari/i' => 'Safari',
			'/chrome/i' => 'Chrome',
			'/opera/i' => 'Opera',
			'/netscape/i' => 'Netscape',
			'/maxthon/i' => 'Maxthon',
			'/konqueror/i' => 'Konqueror',
			'/mobile/i' => 'Handheld Browser'
		);

		foreach($browser_array as $regex => $value)
		{
			if(preg_match($regex, $user_agent))
			{
				$browser = $value;
			}
		}
		return $browser;
	}

	/**
	 * 알림톡의 경우 SMS_API v2 로 보내기 위해 새로 데이터를 정렬 해준다. (임시)
	 */
	public static function setATAData($options)
	{
		$options->extension = json_decode($options->extension);
		$json_data = array();
		foreach($options->extension as $k => $v)
		{
			if(!$v->to)
			{
				continue;
			}
			$obj = new stdClass();
			$obj->type = $options->type;
			$obj->to = $v->to;
			$obj->text = $v->text;
			$obj->from = $options->from;
			if($options->type === 'ata')
			{
				$obj->template_code = $options->template_code;
			}
			$obj->sender_key = $options->sender_key;
			if($options->datetime)
			{
				$obj->datetime = $options->datetime;
			}
			if($options->subject)
			{
				$obj->subject = $options->subject;
			}
			if($options->country)
			{
				$obj->country = $options->country;
			}
			if($options->refname)
			{
				$obj->refname = $options->refname;
			}
			$json_data[] = $obj;
		}
		$options->messages = json_encode($json_data);
		unset($options->extension);

		return $options;
	}

	/**
	 * 알림톡 발송
	 */
	public static function sendATA($options)
	{
		// 인증정보만 가진 Object를 따로 생성
		$authentication_obj = new stdClass();
		$authentication_obj->api_key = $options->api_key;
		$authentication_obj->coolsms_user = $options->coolsms_user;
		$authentication_obj->timestamp = $options->timestamp;
		$authentication_obj->salt = $options->salt;
		$authentication_obj->signature = $options->signature;

		// create group
		self::$method = 0;
		self::setContent($authentication_obj);
		$host = sprintf("%s%s/%s/%s?%s", self::$host, self::$resource, self::$version, "new_group", self::$content);
		$result = self::requestGet($host);
		if(self::$error_flag == true)
		{
			self::$result->code = $result;
			return;
		}
		$group_id = $result->group_id;

		// add messages
		self::$method = 1;
		self::setContent($options);
		$host = sprintf("%s%s/%s/groups/%s/%s", self::$host, self::$resource, self::$version, $group_id, "add_messages.json");
		$result = self::requestPOST($host);
		if(self::$error_flag == true)
		{
			self::$result->code = $result;
			return;
		}

		// success, error count 구하기
		$success_count = 0;
		$error_count = 0;
		foreach($result as $k => $v)
		{
			$success_count = $success_count + $v->success_count;
			$error_count = $error_count + $v->error_count;
		}
		self::$result->success_count = $success_count;
		self::$result->error_count = $error_count;

		// send messages
		self::$method = 1;
		self::setContent($authentication_obj);
		$host = sprintf("%s%s/%s/groups/%s/%s", self::$host, self::$resource, self::$version, $group_id, "send");
		$result = self::requestPOST($host);
		if(self::$error_flag == true)
		{
			self::$result->code = $result;
			return;
		}
	}

	// http request GET
	protected static function requestGet($host)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $host);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSLVERSION, 3); // SSL 버젼 (https 접속시에 필요)
		curl_setopt($ch, CURLOPT_HEADER, 0); // 헤더 출력 여부
		curl_setopt($ch, CURLOPT_POST, self::$method); // Post Get 접속 여부
		curl_setopt($ch, CURLOPT_TIMEOUT, 10); // TimeOut 값
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 결과값을 받을것인지

		$result = json_decode(curl_exec($ch));

		// Check connect errors
		if(curl_errno($ch))
		{
			self::$error_flag = true;
			$result = curl_error($ch);
		}

		curl_close($ch);
		return $result;
	}

	// http request POST
	protected static function requestPOST($host)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $host);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSLVERSION, 3); // SSL 버젼 (https 접속시에 필요)
		curl_setopt($ch, CURLOPT_HEADER, 0); // 헤더 출력 여부
		curl_setopt($ch, CURLOPT_POST, self::$method); // Post Get 접속 여부
		curl_setopt($ch, CURLOPT_TIMEOUT, 10); // TimeOut 값
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 결과값을 받을것인지
		$header = array("Content-Type:multipart/form-data");

		// route가 있으면 header에 붙여준다.
		if(self::$content['route'])
		{
			$header[] = "User-Agent:" . self::$content['route'];
		}

		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_POSTFIELDS, self::$content);

		$result = json_decode(curl_exec($ch));

		// Check connect errors
		if(curl_errno($ch))
		{
			self::$error_flag = true;
			$result = curl_error($ch);
		}

		curl_close($ch);
		return $result;
	}
}
