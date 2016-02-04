<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  inipaymobileView
 * @author NURIGO(contact@nurigo.net)
 * @brief  inipaymobileView
 */
class inipaymobileView extends inipaymobile
{
	/**
	 * @brief initialize this class
	 */
	function init()
	{
		// set template path
		if ($this->module_info->module != 'inipaymobile') $this->module_info->skin = 'default';
		if (!$this->module_info->skin) $this->module_info->skin = 'default';
		$this->setTemplatePath($this->module_path."skins/{$this->module_info->skin}");
		Context::set('module_info',$this->module_info);
	}

	/**
	 * @brief epay.getPaymentForm 에서 호출됨, 이니시스 모바일 결제폼 출력
	 */
	function dispInipaymobileForm() 
	{
		$oEpayController = &getController('epay');
		// get products info using cartnos
		$reviewOutput = $oEpayController->reviewOrder();
		if(!$reviewOutput->toBool()) return $reviewOutput;

		Context::set('review_form', $reviewOutput->review_form);
		Context::set('item_name', $reviewOutput->item_name);
		Context::set('price', $reviewOutput->price);
		Context::set('transaction_srl', $reviewOutput->transaction_srl);
		Context::set('order_srl', $reviewOutput->order_srl);
		Context::set('purchaser_name', $reviewOutput->purchaser_name);
		Context::set('purchaser_email', $reviewOutput->purchaser_email);
		Context::set('purchaser_telnum', $reviewOutput->purchaser_telnum);


		/**
		 * next_url 및 return_url 에 url 구성은 http://domain/directory?var=val 형식으로 ?var1=val1&var2=val2 처럼 &은 허용되지 않는다. 그래서 부득이하게 n_page 에 transaction_srl을 담아오면 next_url로, r_page에 transaction_srl을 담아오면 return_url로 처리한다.
		 */

		$transaction_srl = $reviewOutput->transaction_srl;

		// 가상계좌, 안심클릭시 (n_page)
		Context::set('next_url', getNotEncodedFullUrl('') . $this->module_info->mid . '?n_page=' . $transaction_srl);
		// ISP 결제시 (r_page), 결제처리는 noti_url이 호출되어 처리되므로 여기서는 그냥 결과만 보여줌
		Context::set('return_url', getNotEncodedFullUrl('') . $this->module_info->mid . '?r_page=' . $transaction_srl);
		// ISP 결제시 처리 URL 지정
		Context::set('noti_url', getNotEncodedFullUrl('') . $this->module_info->mid . '?noti_page=' . $transaction_srl);

		$this->setTemplateFile('formdata');
	}
}
/* End of file inipaymobile.view.php */
/* Location: ./modules/inipaymobile/inipaymobile.view.php */
