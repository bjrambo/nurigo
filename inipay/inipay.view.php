<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  inipayView
 * @author NURIGO(contact@nurigo.net)
 * @brief  inipayView
 */
class inipayView extends inipay
{
	/**
	 * @brief initialize this class
	 */
	function init()
	{
		// set template path
		if ($this->module_info->module != 'inipay') $this->module_info->skin = 'default';
		if (!$this->module_info->skin) $this->module_info->skin = 'default';
		$this->setTemplatePath($this->module_path."skins/{$this->module_info->skin}");
		// default values
		if(!$this->module_info->va_receipt) $this->module_info->va_receipt = 'Y';
		Context::set('module_info',$this->module_info);
	}

	/**
	 * @brief epay.getPaymentForm 에서 호출됨
	 */
	function dispInipayForm() 
	{
		$oEpayController = &getController('epay');

		$inipayhome = sprintf(_XE_PATH_."files/epay/%s", $this->module_info->module_srl);

		// get products info using cartnos
		$reviewOutput = $oEpayController->reviewOrder();
		if(!$reviewOutput->toBool()) return $reviewOutput;

		Context::set('transaction_srl', $reviewOutput->transaction_srl);
		Context::set('order_srl', $reviewOutput->order_srl);
		Context::set('review_form', $reviewOutput->review_form);
		Context::set('item_name', $reviewOutput->item_name);
		Context::set('price', $reviewOutput->price);
		Context::set('purchaser_name', $reviewOutput->purchaser_name);
		Context::set('purchaser_email', $reviewOutput->purchaser_email);
		Context::set('purchaser_telnum', $reviewOutput->purchaser_telnum);

		// set acceptmethod options
		if($this->module_info->method_mobilephone=='Y') $HPP='1';
		if($this->module_info->method_mobilephone=='M') $HPP='2';
		$acceptmethod = sprintf("SKIN(%s):HPP(%s):Card(0):OCB:receipt:cardpoint", $this->module_info->paywin_skin, $HPP);
		if($this->module_info->va_receipt=='Y') $acceptmethod .= ':va_receipt';
		Context::set('acceptmethod', $acceptmethod);

		require("libs/INILib.php");
		$inipay = new INIpay50;
		$inipay->SetField("inipayhome", $inipayhome);
		$inipay->SetField("type", "chkfake");
		$inipay->SetField("debug", "true");
		$inipay->SetField("enctype","asym");
		$inipay->SetField("admin", $this->module_info->inicis_pass);
		$inipay->SetField("checkopt", "false");
		$inipay->SetField("mid", $this->module_info->inicis_id);
		$inipay->SetField("price", $reviewOutput->price);
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
		$_SESSION['INI_MID'] = $this->module_info->inicis_id;	//상점ID
		$_SESSION['INI_ADMIN'] = $this->module_info->inicis_pass;	// 키패스워드(키발급시 생성, 상점관리자 패스워드와 상관없음)
		$_SESSION['INI_PRICE'] = $reviewOutput->price;   //가격 
		$_SESSION['INI_RN'] = $inipay->GetResult("rn"); //고정 (절대 수정 불가)
		$_SESSION['INI_ENCTYPE'] = $inipay->GetResult("enctype"); //고정 (절대 수정 불가)

		Context::set('encfield', $inipay->GetResult('encfield'));
		Context::set('certid', $inipay->GetResult('certid'));
		Context::set('inicis_id', $this->module_info->inicis_id);

		// payment method 변환 <-- CC, IB, VA, MP 를 결제모듈에서 정의된 것으로 대체할 수 있으면 좋겠음.
		$payment_method = Context::get('payment_method');
		switch($payment_method)
		{
			case "CC":
				$payment_method = "Card";
				break;
			case "IB":
				$payment_method = "DirectBank";
				break;
			case "VA":
				$payment_method = "VBank";
				break;
			case "MP":
				$payment_method = "HPP";
				break;
			default:
				$payment_method = "Card";
		}
		Context::set('payment_method', $payment_method);
		$this->setTemplateFile('formdata');
	}
}
/* End of file inipay.view.php */
/* Location: ./modules/inipay/inipay.view.php */
