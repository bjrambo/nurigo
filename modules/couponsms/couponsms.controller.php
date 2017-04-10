<?php

class couponsmsController extends couponsms
{
	function init()
	{
	}

	function procCouponsmsSendMessage()
	{
		$oMemberModel = getModel('member');
		$oCouponsmsModel = getModel('couponsms');
		$logged_info = Context::get('logged_info');
		$couponsms_srl = Context::get('couponsms_srl');

		$config = $oCouponsmsModel->getConfig();

		if($config->variable_name)
		{
			$phone_number = $logged_info->{$config->variable_name}[0] . '-' . $logged_info->{$config->variable_name}[1] . '-' . $logged_info->{$config->variable_name}[2];
			if(!$phone_number)
			{
				return new Object(-1, '회원정보에서 휴대전화번호를 설정하지 않으셨습니다.');
			}
		}
		else
		{
			return new Object(-1, '설정에서 전화번호변수를 설정해야 합니다. 관리자에게 문의해주시기 바랍니다.');
		}

		$output = $oCouponsmsModel->getCouponConfig($couponsms_srl);
		$couponsms = $output->data;
		$c_group_srl = unserialize($couponsms->group_srl);

		if(is_array($c_group_srl) && count($c_group_srl) > 0)
		{
			$isGroup = FALSE;
			$group_list = $oMemberModel->getMemberGroups($logged_info->member_srl);

			foreach($group_list as $group_srl => &$group_title)
			{
				if(in_array($group_srl, $c_group_srl))
				{
					$isGroup = TRUE;
					break;
				}
			}
		}

		$logged_info = Context::get('logged_info');
		if(!Context::get('is_logged'))
		{
			return new Object(-1, '로그인하지 않은 사용자는 사용할 수 없습니다.');
		}

		if(!$logged_info->phone[1] || !$logged_info->phone[2])
		{
			return new Object(-1, '회원정보에 휴대폰번호를 입력하지 않아 쿠폰을 발급할 수 없습니다.');
		}

		if(!$isGroup)
		{
			return new Object(-1, '요청하신 서비스에 권한이 없습니다.');
		}

		$couponuser_srl = mt_rand(11111111111, 99999999999);
		$randomnum = substr(md5($couponuser_srl . $logged_info->member_srl), 0, 11);

		$args = new stdClass();
		$args->couponuser_srl = $randomnum;
		$args->couponsms_srl = $couponsms_srl;
		$args->member_srl = $logged_info->member_srl;
		$selected_date = date('Ymd');
		$term_regdate = date('Ymd', strtotime($selected_date . '+' . $couponsms->term_regdate . ' day'));
		$args->term_regdate = $term_regdate;
		$args->regdate = date('YmdHis');
		$args->title = $couponsms->title;

		$couponsms_data = $oCouponsmsModel->getTodayCouponByMemberSrl($logged_info->member_srl, $couponsms_srl, $couponsms->term_regdate);
		if(!$couponsms_data->toBool())
		{
			return $couponsms_data;
		}

		if(count($couponsms_data->data) >= 1)
		{
			return new Object(-1, $couponsms->term_regdate.'일 이내 쿠폰을 더 이상 발급할 수 없습니다.');
		}
		$output = executeQuery('couponsms.insertCouponUser', $args);
		if ($output->toBool())
		{
			$content = $logged_info->nick_name.'님이 발급받으신 쿠폰정보입니다.
쿠번번호 : '.$randomnum.'
사용처 : '.$couponsms->use.'
혜택 : '.$couponsms->use_boon.'
유효기간 : '.zdate($term_regdate, 'Y년m월d일 ').'까지';
			$title = Context::getSiteTitle().'에서 보낸 쿠폰입니다.';

			$send_massage = self::sendMessage($phone_number, $couponsms->phone_number, $content, $title);
			if($send_massage == true)
			{
				$setting_args = new stdClass();
				$setting_args->couponuser_srl = $args->couponuser_srl;
				$setting_args->success = 'Y';
				$setting_args->w_false = null;
				$setting_output = executeQuery('couponsms.updateCouponUser', $setting_args);
				if(!$setting_output->toBool())
				{
					return $setting_output;
				}
				$this->setMessage('쿠폰이 발급되었습니다. 문자메세지를 확인하세요.');
			}
			else
			{
				$setting_args = new stdClass();
				$setting_args->couponuser_srl = $args->couponuser_srl;
				$setting_args->success = 'N';
				$setting_args->w_false = $send_massage;
				$setting_output = executeQuery('couponsms.updateCouponUser', $setting_args);
				if(!$setting_output->toBool())
				{
					return $setting_output;
				}
				return new Object(-1, '문자 발송에 실패하였습니다.');
			}
		}
		else
		{
			return new Object(-1, '쿠폰생성실패');
		}

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON')))
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'act', 'dispCouponsmsSendMessageView', 'couponsms_srl', $couponsms_srl);
			header('location: ' . $returnUrl);
			return;
		}
	}

	public static function sendMessage($phone_number, $r_number, $content, $title)
	{
		$oTextmessageController = getController('textmessage');

		$args = new stdClass();
		$args->content = $content;
		$args->sender_no = $phone_number;
		$args->recipient_no = $r_number;
		$args->subject = $title;
		$args = getModel('couponsms')->getFriendTalkSenderKey($args);
		$output = $oTextmessageController->sendMessage($args, FALSE);
		if(!$output->toBool())
		{
			return false;
		}
		return true;
	}
}