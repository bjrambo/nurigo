<?php

class couponsmsAdminController extends couponsms
{
	function init()
	{
	}

	function procCouponsmsAdminCouponInsert()
	{
		$couponsms_srl = Context::get('couponsms_srl');

		$obj = Context::getRequestVars();
		$args = new stdClass();
		$args->title = $obj->title;
		if(is_numeric($obj->term_regdate) != TRUE)
		{
			return new Object(-1, '유효기간은 숫자로 표기해야합니다.');
		}
		$args->term_regdate = $obj->term_regdate;
		$args->phone_number = $obj->phone_number;
		$args->use = $obj->use;
		$args->use_boon = $obj->use_boon;
		$args->discount_type = $obj->discount_type;
		$args->discount = $obj->discount;
		$args->free_delivery = $obj->free_delivery;
		$args->maximum_count = $obj->maximum_count;
		$args->condition_type = $obj->condition_type;
		$args->price_condition = $obj->price_condition;
		if($obj->group_srl)
		{
			$args->group_srl = serialize($obj->group_srl);
		}
		else
		{
			$args->group_srl = NULL;
		}

		if($couponsms_srl)
		{
			$args->couponsms_srl = $couponsms_srl;
			$output = executeQuery('couponsms.updateCoupon', $args);
			if(!$output->toBool())
			{
				return $output;
			}
		}
		else
		{
			$args->couponsms_srl = getNextSequence();
			$output = executeQuery('couponsms.insertCoupon', $args);
			if(!$output->toBool())
			{
				return $output;
			}
		}

		$this->setMessage('success_saved');

		if (Context::get('success_return_url'))
		{
			$this->setRedirectUrl(Context::get('success_return_url'));
		}
		else
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispCouponsmsAdminCouponUser', 'couponsms_srl', $couponsms_srl));
		}
	}

	function procCouponsmsAdminSetting()
	{
		$oModuleController = getController('module');
		$obj = Context::getRequestVars();

		$config = new stdClass();
		$config->layout_srl = $obj->layout_srl;
		$config->mlayout_srl = $obj->mlayout_srl;
		$config->skin = $obj->skin;
		$config->mskin = $obj->mskin;
		$config->sending_method = $obj->sending_method;
		$config->sender_key = $obj->sender_key;
		$config->variable_name = $obj->variable_name;
		$config->use_shop_coupon = $obj->use_shop_coupon;
		$this->setMessage('success_updated');

		$oModuleController->updateModuleConfig('couponsms', $config);
		if (Context::get('success_return_url'))
		{
			$this->setRedirectUrl(Context::get('success_return_url'));
		}
		else
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispCouponsmsAdminCouponUser', 'couponsms_srl', $couponsms_srl));
		}
	}

	function procCouponsmsAdminCouponRecall()
	{
		$output = executeQuery('couponsms.deleteCouponUse');
		if(!$output->toBool())
		{
			return $output;
		}

		$this->setMessage('1달 이전의 쿠폰을 회수하였습니다.');

		if (Context::get('success_return_url'))
		{
			$this->setRedirectUrl(Context::get('success_return_url'));
		}
		else
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispCouponsmsAdminCouponUser', 'couponsms_srl', $couponsms_srl));
		}
	}

	function procCouponsmsAdminCouponDelete()
	{
		$couponsms_srl = Context::get('couponsms_srl');
		if(!$couponsms_srl)
		{
			return new Object(-1, '쿠폰 정보를 가져올 수 없습니다.');
		}

		$args = new stdClass();
		$args->couponsms_srl = $couponsms_srl;
		$output = executeQuery('couponsms.deleteCoupon', $args);
		if(!$output->toBool())
		{
			return $output;
		}

		$this->setMessage('쿠폰을 삭제하였습니다.');

		if (Context::get('success_return_url'))
		{
			$this->setRedirectUrl(Context::get('success_return_url'));
		}
		else
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispCouponsmsAdminCouponUser', 'couponsms_srl', $couponsms_srl));
		}
	}

	function procCouponsmsAdminCouponUser()
	{
		$couponsms_srl = Context::get('couponsms_srl');
		if(!$couponsms_srl)
		{
			return new Object(-1, '쿠폰 정보를 가져올 수 없습니다.');
		}

		$email_address = Context::get('email_address');

		$oMemberModel = getModel('member');
		$oCouponsmsModel = getModel('couponsms');
		$member_info = getModel('member')->getMemberInfoByEmailAddress($email_address);

		if(!$member_info)
		{
			return new Object(-1, '회원이 존재하지 않습니다.');
		}
		$couponsms_srl = Context::get('couponsms_srl');

		$config = $oCouponsmsModel->getConfig();

		if($config->variable_name)
		{
			$phone_number = $member_info->{$config->variable_name}[0] . '-' . $member_info->{$config->variable_name}[1] . '-' . $member_info->{$config->variable_name}[2];
		}

		$output = $oCouponsmsModel->getCouponConfig($couponsms_srl);
		$couponsms = $output->data;
		$c_group_srl = unserialize($couponsms->group_srl);
		$isGroup = FALSE;
		if(is_array($c_group_srl) && count($c_group_srl) > 0)
		{
			$group_list = $oMemberModel->getMemberGroups($member_info->member_srl);

			foreach($group_list as $group_srl => &$group_title)
			{
				if(in_array($group_srl, $c_group_srl))
				{
					$isGroup = TRUE;
					break;
				}
			}
		}

		$is_return = self::insertCouponsmsUser($couponsms_srl, $couponsms, $member_info, $phone_number);

		if(!$isGroup)
		{
			return new Object(-1, '이 회원은 요청하신 서비스에 권한이 없습니다.');
		}

		$this->setMessage('success_registed');

		if (Context::get('success_return_url'))
		{
			$this->setRedirectUrl(Context::get('success_return_url'));
		}
		else
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispCouponsmsAdminCouponUser', 'couponsms_srl', $couponsms_srl));
		}
	}

	/**
	 * @param $couponsms_srl
	 * @param $couponsms
	 * @param $member_info
	 * @param null $phone_number
	 * @return bool|object
	 */
	public static function insertCouponsmsUser($couponsms_srl, $couponsms, $member_info, $phone_number = null)
	{
		$oCouponsmsModel = getModel('couponsms');
		$config = $oCouponsmsModel->getConfig();

		$args = new stdClass();
		$args->couponsms_srl = $couponsms_srl;
		$args->member_srl = $member_info->member_srl;
		$selected_date = date('Ymd');
		$term_regdate = date('Ymd', strtotime($selected_date . '+' . $couponsms->term_regdate . ' day'));
		$args->term_regdate = $term_regdate;
		$args->regdate = date('YmdHis');
		$args->title = $couponsms->title;

		$is_return = false;
		$count = 0;
		for($i=1; $i <= $couponsms->maximum_count; $i++)
		{
			$couponuser_srl = getNextSequence();
			$randomnum = substr(md5($couponuser_srl . $member_info->member_srl), 0, 11);
			$args->couponuser_srl = $randomnum;
			$couponsms_data = $oCouponsmsModel->getTodayCouponByMemberSrl($member_info->member_srl, $couponsms_srl, $couponsms->term_regdate);
			if(!$couponsms_data->toBool())
			{
				return $couponsms_data;
			}
			if(count($couponsms_data->data) >= $couponsms->maximum_count)
			{
				$is_return = true;
				break;
			}
			$count++;
			$output = executeQuery('couponsms.insertCouponUser', $args);
			if ($output->toBool())
			{
				$content = $member_info->nick_name.'님이 발급받으신 쿠폰정보입니다.
쿠번번호 : '.$randomnum.'
사용처 : '.$couponsms->use.'
혜택 : '.$couponsms->use_boon.'
유효기간 : '.zdate($term_regdate, 'Y년m월d일 ').'까지';
				$title = Context::getSiteTitle().'에서 보낸 쿠폰입니다.';

				if(isset($config->sending_method) && $phone_number != null)
				{
					$send_massage = self::sendMessage($phone_number, $couponsms->phone_number, $content, $title);
					if($send_massage == true)
					{
						$setting_args = new stdClass();
						$setting_args->couponuser_srl = $args->couponuser_srl;
						$setting_args->sms_success = 'Y';
						$setting_args->use_success = 'N';
						$setting_args->w_false = null;
						$setting_output = executeQuery('couponsms.updateCouponUser', $setting_args);
						if(!$setting_output->toBool())
						{
							return $setting_output;
						}
						self::setMessage('쿠폰이 발급되었습니다. 문자메세지를 확인하세요.');
					}
					else
					{
						$setting_args = new stdClass();
						$setting_args->couponuser_srl = $args->couponuser_srl;
						$setting_args->sms_success = 'N';
						$setting_args->use_success = 'N';
						$setting_args->w_false = $send_massage;
						$setting_output = executeQuery('couponsms.updateCouponUser', $setting_args);
						if(!$setting_output->toBool())
						{
							return $setting_output;
						}
					}
				}
				else
				{
					$setting_args = new stdClass();
					$setting_args->couponuser_srl = $args->couponuser_srl;
					$setting_args->sms_success = 'N';
					$setting_args->use_success = 'N';
					$setting_args->w_false = '문자 전송을 사용하지않고 발급';
					$setting_output = executeQuery('couponsms.updateCouponUser', $setting_args);
					if(!$setting_output->toBool())
					{
						return $setting_output;
					}
				}
			}
		}
		$history_args = new stdClass();
		$history_args->couponuser_srl = $args->couponuser_srl;
		$history_args->member_srl = $member_info->member_srl;
		$history_args->log_text = '관리자가 회원에게 쿠폰 '.$count.'장을 발급';
		$history_args->sms_success = $setting_args->success;
		$history_args->use_success = 'N';
		$history_output = getController('couponsms')->insertHistory($history_args);
		return $is_return;
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

	function procCouponsmsAdminCouponUserAll()
	{
		$oCouponsmsModel = getModel('couponsms');
		$args = new stdClass();
		$output = executeQueryArray('couponsms.getCouponListAll', $args);
		if(!$output->toBool())
		{
			return $output;
		}
		if(!empty($output->data))
		{
			foreach($output->data as $val)
			{

				if($val->condition_type == 'price')
				{
					$price_args = new stdClass();
					$price_args->min_price = $val->price_condition;
					$price_output = executeQueryArray('couponsms.getCouponCondUsers', $price_args);
					if(!$price_output->toBool())
					{
						return $price_output;
					}

					if(!empty($price_output->data))
					{
						foreach($price_output->data as $price_val)
						{
							$output = $oCouponsmsModel->getCouponConfig($val->couponsms_srl);
							$couponsms = $output->data;
							$member_info = getModel('member')->getMemberInfoByMemberSrl($price_val->member_srl);
							$output = self::insertCouponsmsUser($val->couponsms_srl, $couponsms, $member_info);
						}
					}
				}
			}
		}
		if(Context::get('success_return_url'))
		{
			$this->setRedirectUrl(Context::get('success_return_url'));
		}
		else
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispCouponsmsAdminCouponList'));
		}
	}
}
/* End of file */
