<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  textmessageController
 * @author diver(diver@coolsms.co.kr)
 * @brief  textmessageController
 */
class textmessageController extends textmessage 
{
	function init() { }

	/**
	 * @brief 메시지 전송 
	 * @param[in] $args
	 *  $args->type = 'SMS' or 'LMS' or 'MMS' // default = 'SMS'
	 *  $args->recipient_no = '수신번호'
	 *  $args->sender_no = '발신번호'
	 *  $args->content = '메시지 내용'
	 *  $args->reservdate = 'YYYYMMDDHHMISS'
	 *  $args->subject = 'LMS제목'
	 *  $args->country_code = '국가번호'
	 *  $args->country_iso_code = '국가ISO코드'
	 *  $args->attachment = 첨부파일
	 *  $args->encode_utf16 = true or false
	 * @param[in] $user_id true means auto, false means none, otherwise, use in userid
	 * @return Object(error, message)
	 **/
	function sendMessage($args, $basecamp=FALSE) 
	{
		$oTextmessageModel = getModel('textmessage');
		$sms = &$oTextmessageModel->getCoolSMS($basecamp);
		$options = new stdClass();
		if($oTextmessageModel->getSlnRegKey() && !$args->srk)
		{
			$options->srk = $oTextmessageModel->getSlnRegKey();
		}

		// 기존 Textmessage 와 다른 args 옵션으로 인한 동기화하기 
		if($args->recipient_no)
		{
			if(is_array($args->recipient_no))
				$options->to = implode(',' , $args->recipient_no);
			else
				$options->to = $args->recipient_no;
		}
		elseif($args->to) 		$options->to = $args->to;

		if($args->sender_no) 	$options->from = $args->sender_no;
		elseif($args->from)		$options->from = $args->from;

		if($args->type)			$options->type = $args->type;
		if($args->attachment) 	$options->image = $args->attachment;
		if($args->image)		$options->image = $args->image;
		if($args->content)		$options->text = $args->content;
		if($args->refname)		$options->refname = $args->refname;
		if($args->country)		$options->country = $args->country;
		if($args->subject)		$options->subject = $args->subject;
		if($args->srk)			$options->srk = $args->srk;
		if($args->extension) 	$options->extension = $args->extension;
		if($args->reservdate) 	$options->datetime = $args->reservdate;
		if($args->route) 		$options->route = $args->route;
		if($args->app_version)  $options->app_version = $args->app_version;
		if($args->sender_key)	$options->sender_key = $args->sender_key;
		if($args->template_code) $options->template_code = $args->template_code;

		//$options->mode = "test";
		$result = new stdClass();

		// 문자 전송
		$result = $sms->send($options);

		$output = new Object();
		if($result->code)
		{
			$result->error_count = count(explode(',', $options->to));
			$result->success_count = 0;
			$output->add('error_code', $result->code);
		}
		
		$output->add('success_count', $result->success_count);
		$output->add('failure_count', $result->error_count);
		if($result->group_id) $output->add('group_id', $result->group_id);

		return $output;
	}

	/* 
	 * @brief 예약전송 취소하기
	 */
	function cancelMessage($msgid, $basecamp=FALSE)
	{
		$oTextmessageModel = getModel('textmessage');
		$sms = &$oTextmessageModel->getCoolSMS($basecamp);
		$options = new stdClass();
		$options->mid = $msgid;
		$result = $sms->cancel($options);
		if($result->code)	
			return new Object(-1, $result->code);
		else
			return new Object();
	}

	/**
	 * @brief 문자취소(그룹)
	 **/
	function cancelGroupMessages($grpid, $basecamp=FALSE)
	{
		$oTextmessageModel = getModel('textmessage');
		$sms = &$oTextmessageModel->getCoolSMS($basecamp);
		$options = new stdClass();
		$options->gid = $grpid;
		$result = $sms->cancel($options);
		if($result->code)
			return new Object(-1, $result->code);
		return new Object();
	}
}
/* End of file textmessage.controller.php */
/* Location: ./modules/textmessage.controller.php */
