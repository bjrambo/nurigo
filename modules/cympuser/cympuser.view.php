<?php

/**
 * @class  cympuserView
 * @author billy(contact@nurigo.net)
 * @brief  cympuserView
 */
class cympuserView extends cympuser
{
	function init()
	{
		$config = self::getConfig();
		$template_path = sprintf('%sskins/%s/', $this->module_path, $config->skin);
		if(!is_dir($template_path) || !$config->skin)
		{
			$config->skin = 'default';
			$template_path = sprintf('%sskins/%s/', $this->module_path, $config->skin);
		}
		$this->setTemplatePath($template_path);
		$oLayoutModel = getModel('layout');
		$layout_info = $oLayoutModel->getLayout($config->layout_srl);
		if($layout_info)
		{
			$this->module_info->layout_srl = $config->layout_srl;
			$this->setLayoutPath($layout_info->path);
		}
	}

	function dispCympuserMemberInfo()
	{
		$oMemberModel = getModel('member');
		$oNstoreModel = getModel('nstore');

		$logged_info = Context::get('logged_info');

		// Don't display member info to non-logged user
		$is_logged = Context::get('is_logged');
		if(!$is_logged) return new Object(-1, 'msg_not_permitted');

		$member_srl = Context::get('member_srl');
		if(!$member_srl && $is_logged)
		{
			$member_srl = $logged_info->member_srl;
		}

		$site_module_info = Context::get('site_module_info');
		$columnList = array('member_srl', 'user_id', 'email_address', 'user_name', 'nick_name', 'homepage', 'blog', 'birthday', 'regdate', 'last_login', 'extra_vars');
		$member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl, $site_module_info->site_srl, $columnList);
		unset($member_info->password);
		unset($member_info->email_id);
		unset($member_info->email_host);

		if($logged_info->is_admin != 'Y' && ($member_info->member_srl != $logged_info->member_srl))
		{
			list($email_id, $email_host) = explode('@', $member_info->email_address);
			$protect_id = substr($email_id, 0, 2) . str_repeat('*', strlen($email_id)-2);
			$member_info->email_address = sprintf('%s@%s', $protect_id, $email_host);
		}

		$total_info = $oNstoreModel->getMemberTotalPriceByMemberSrl($member_info->member_srl);
		Context::set('total_info', $total_info);

		$coupons = getModel('couponsms')->getCouponUserListByMemberSrl($member_srl, 'N');
		if($coupons === false)
		{
			$couponsCount = 0;
		}
		else
		{
			$couponsCount = count($coupons);
		}

		Context::set('couponCount', $couponsCount);

		$thisMonth = $oNstoreModel->getMemberTotalInfo($member_info->member_srl, date('Ym01000000'), date('Ymt235959'));
		$thisMonthTotalPrice = 0;
		if(is_array($thisMonth))
		{
			foreach($thisMonth as $val)
			{
				$thisMonthTotalPrice = $thisMonthTotalPrice + $val->discounted_price;
			}
		}

		Context::set('thisMonthTotalPrice', $thisMonthTotalPrice);

		$today = mktime(0,0,0, date("m"), 15, date("Y"));;
		$prev_month = strtotime('-1 month', $today);
		$startLastDay = date('Ym01000000', $prev_month);
		$endLastDay = date('Ymt235959', $prev_month);
		$lastMonth = $oNstoreModel->getMemberTotalInfo($member_info->member_srl, $startLastDay, $endLastDay);
		$lastMonthTotalPrice = 0;
		if(is_array($lastMonth))
		{
			foreach($lastMonth as $val)
			{
				$lastMonthTotalPrice = $lastMonthTotalPrice + $val->discounted_price;
			}
		}

		Context::set('lastMonthTotalPrice', $lastMonthTotalPrice);

		$startday = date('YmdHis', strtotime('-30 day', time()));
		$endDay = date('YmdHis', time());
		$lastDay = $oNstoreModel->getMemberTotalInfo($member_info->member_srl, $startday, $endDay);
		$lastDayTotalPrice = 0;
		if(is_array($lastDay))
		{
			foreach($lastDay as $val)
			{
				$lastDayTotalPrice = $lastDayTotalPrice + $val->discounted_price;
			}
		}

		Context::set('lastDayTotalPrice', $lastDayTotalPrice);
		Context::set('memberInfo', get_object_vars($member_info));

		$extendForm = $oMemberModel->getCombineJoinForm($member_info);
		unset($extendForm->find_member_account);
		unset($extendForm->find_member_answer);
		Context::set('extend_form_list', $extendForm);

		$memberConfig = $oMemberModel->getMemberConfig();

		getView('member')->_getDisplayedMemberInfo($member_info, $extendForm, $memberConfig);

		$this->setTemplateFile('member_info');
	}
}
