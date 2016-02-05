<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  paynoty
 * @author NURIGO(contact@nurigo.net)
 * @brief  paynoty
 */
class paynoty extends ModuleObject 
{
	/**
	 * @brief Object를 텍스트의 %...% 와 치환.
	 **/
	function mergeKeywords($text, &$obj) 
	{
		if (!is_object($obj)) return $text;

		foreach ($obj as $key => $val)
		{
			if (is_array($val)) $val = join($val);
			if (is_numeric($val)) $val = (string)$val;
			if (is_string($key) && is_string($val)) {
				if($key == 'state')
				{
					switch ($val)
					{
						case '1' :
							$val = '결제 진행중';
							break;
						case '2' :
							$val = '결제 완료';
							break;
						case '3' :
							$val = '결제 오류';
							break;
					}
				}
				if($key == 'payment_method')
				{
					switch ($val)
					{
						case 'CC' :
							$val = '신용 카드';
							break;
						case 'BT' :
							$val = '무통장 입금';
							break;
						case 'IB' :
							$val = '실시간 계좌 이체';
							break;
						case 'VA' :
							$val = '가상 계좌';
							break;
						case 'MP' :
							$val = '휴대폰 소액결제';
							break;
						case 'PP' :
							$val = '페이팔';
							break;
					}
				}
				if (substr($key,0,10)=='extra_vars') $val = str_replace('|@|', '-', $val);
				$text = preg_replace("/%" . preg_quote($key) . "%/", $val, $text);
			}
		}
		return $text;
	}

	/**
	 * @brief 모듈 설치 실행
	 **/
	function moduleInstall() 
	{
		$oModuleController = &getController('module');
		$oModuleModel = &getModel('module');

		// Document Registration Trigger
		$oModuleController->insertTrigger('epay.processPayment', 'paynoty', 'controller', 'triggerCompletePayment', 'after');
	}

	/**
	 * @brief 설치가 이상없는지 체크
	 **/
	function checkUpdate() 
	{
		$oDB = &DB::getInstance();
		$oModuleModel = &getModel('module');
		$oModuleController = &getController('module');

		// Document Registration Trigger
		if (!$oModuleModel->getTrigger('epay.processPayment', 'paynoty', 'controller', 'triggerCompletePayment', 'after'))
		{
			return true;
		}
		return false;
	}

	/**
	 * @brief 업데이트(업그레이드)
	 **/
	function moduleUpdate() 
	{
		$oDB = &DB::getInstance();
		$oModuleModel = &getModel('module');
		$oModuleController = &getController('module');

		// Document Registration Trigger
		if (!$oModuleModel->getTrigger('epay.processPayment', 'paynoty', 'controller', 'triggerCompletePayment', 'after'))
		{
			$oModuleController->insertTrigger('epay.processPayment', 'paynoty', 'controller', 'triggerCompletePayment', 'after');
		}
	}

	/**
	 * @brief 캐시파일 재생성
	 **/
	function recompileCache() 
	{
	}
}
?>
