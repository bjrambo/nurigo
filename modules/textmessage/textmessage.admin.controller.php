<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  textmessageAdminController
 * @author wiley (wiley@xnurigo.net)
 * @brief  textmessage controller class of textmessage module
 **/
class textmessageAdminController extends textmessage 
{
	/**
	 * @brief initialization
	 * @return none
	 **/
	function init() { }

	/**
	 * @brief 기본설정 module config 에 저장
	 **/
	function procTextmessageAdminInsertConfig() 
	{
		$oTextmessageModel = &getModel('textmessage');
		$args = Context::gets('api_key', 'api_secret', 'callback_url', 'encode_utf16');

		// save module configuration.
		$oModuleControll = getController('module');
		$output = $oModuleControll->insertModuleConfig('textmessage', $args);

		$this->setMessage('success_saved');
		$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispTextmessageAdminConfig');
		$this->setRedirectUrl($redirectUrl);
	}

	/**
	 * @brief 예약취소 
	 **/
	function procTextmessageAdminCancelReserv() 
	{
		$target_message_ids = Context::get('cart');
		if(!$target_message_ids) return new Object(-1, 'msg_invalid_request');

		$oTextmessageController = &getController('textmessage');
		foreach($target_message_ids as $id => $val)
		{
			$output = $oTextmessageController->cancelMessage($val);
			if(!$output->toBool()) return $output;
		}

		$this->setMessage('success_requested');
		$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispTextmessageAdminUsageStatement','group_id',Context::get('group_id'),'stats_date',Context::get('stats_date'));
		$this->setRedirectUrl($redirectUrl);
	}

	/**
	 * @brif 예약 단체 취소
	 **/
	function procTextmessageAdminCancelGroup() 
	{
		$target_group_ids = Context::get('target_group_ids');
		if(!$target_group_ids) return new Object(-1, 'msg_invalid_request');

		$group_ids = explode(',', $target_group_ids);
		$oTextmessageController = &getController('textmessage');

		$output = $oTextmessageController->cancelGroupMessages($group_ids);
		if(!$output->toBool()) return $output;

		$this->setMessage('success_requested');
		$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispTextmessageAdminUsageStatement','stats_date',Context::get('stats_date'));
		$this->setRedirectUrl($redirectUrl);
	}
}
/* End of file textmessage.admin.controller.php */
/* Location: ./modules/textmessage.admin.controller.php */
