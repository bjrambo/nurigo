<?php

class couponsmsModel extends couponsms
{
	private static $config = NULL;

	function getConfig()
	{
		if (self::$config === NULL)
		{
			$oModuleModel = getModel('module');
			$config = $oModuleModel->getModuleConfig('couponsms');
			if(!$config)
			{
				$config = new stdClass();
			}
			if(!isset($config->use_shop_coupon))
			{
				$config->use_shop_coupon = 'no';
			}

			self::$config = $config;
		}
		return self::$config;
	}

	function getCouponConfig($couponsms_srl)
	{
		$args = new stdClass();
		$args->couponsms_srl = $couponsms_srl;

		$output = executeQuery('couponsms.getCouponConfig', $args);

		return $output;
	}

	function getCouponUser($couponuser_srl)
	{
		$args = new stdClass();
		$args->couponuser_srl = $couponuser_srl;
		$output = executeQuery('couponsms.getCouponUser', $args);

		return $output;
	}

	function getCouponList()
	{
		$output = executeQueryArray('couponsms.getCouponList');

		return $output;
	}

	function getCouponLogList()
	{
		$output = executeQueryArray('couponsms.getCouponLogList');

		return $output;
	}

	function getFriendTalkSenderKey($args)
	{
		$config = $this->getConfig();
		if($config->sender_key)
		{
			if(isset($config->sending_method['cta']) || isset($config->sending_method['sms']) && isset($config->sending_method['cta']))
			{
				$args->sender_key = $config->sender_key;
				$args->type = 'cta';
				$json_args = new stdClass();
				$json_args->type = 'cta';
				$json_args->to = $args->recipient_no;
				$json_args->text = $args->content;
				$extension = array($json_args);
				$args->extension = json_encode($extension);
			}
			else
			{
				$args->type = 'lms';
			}
		}
		elseif(isset($config->sending_method['sms']))
		{
			$args->type = 'lms';
		}

		return $args;
	}

	function getTodayCouponByMemberSrl($member_srl, $couponsms_srl, $days = null)
	{
		if(!$couponsms_srl)
		{
			return false;
		}

		$args = new stdClass();
		$args->member_srl = $member_srl;
		$args->couponsms_srl = $couponsms_srl;
		$args->today_regdate = date('Ymd');
		$args->term_regdate = date('Ymd', strtotime($args->today_regdate . '+' . $days . ' day'));

		$output = executeQueryArray('couponsms.getTodayCouponByMemberSrl', $args);

		return $output;
	}

	function getCouponUserListByMemberSrl($member_srl = null, $use_success = null)
	{
		if($member_srl === null && !Context::get('is_logged'))
		{
			return false;
		}

		if($member_srl === null && Context::get('is_logged'))
		{
			$member_srl = Context::get('logged_info')->member_srl;
		}

		$args = new stdClass();
		$args->member_srl = $member_srl;
		$args->use_success = $use_success;
		$output = executeQueryArray('couponsms.getCouponUserListByMemberSrl', $args);
		if(!$output->toBool())
		{
			return $output;
		}

		if(!$output->data)
		{
			return false;
		}
		return $output->data;
	}

	function getCouponInfoByCouponuserSrl($couponuser_srl, $use_success = 'N')
	{
		if(!$couponuser_srl)
		{
			return false;
		}

		$args = new stdClass();
		$args->couponuser_srl = $couponuser_srl;
		$args->use_success = $use_success;
		$output = executeQuery('couponsms.getCouponInfoByCouponSrl', $args);
		if(!$output->toBool())
		{
			return $output;
		}

		if(!$output->data)
		{
			return false;
		}

		return $output->data;
	}
}