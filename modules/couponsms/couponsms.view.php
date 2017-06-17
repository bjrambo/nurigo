<?php

class couponsmsView extends couponsms
{
	function init()
	{
		$oCouponsmsModel = getModel('couponsms');
		$config = $oCouponsmsModel->getConfig();
		$template_path = sprintf("%sskins/%s/",$this->module_path, $config->skin);
		if(!is_dir($template_path)||!$config->skin)
		{
			$config->skin = 'default';
			$template_path = sprintf("%sskins/%s/",$this->module_path, $config->skin);
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

	function dispCouponsmsSendMessageView()
	{
		$member_srl = Context::get('member_srl');
		$couponsms_srl = Context::get('couponsms_srl');
		if(!$couponsms_srl)
		{
			return new Object(-1, '쿠폰번호는 필수입니다.');
		}
		$oConponsmsModel = getModel('couponsms');
		$output = $oConponsmsModel->getCouponConfig($couponsms_srl);
		$couponsms = $output->data;

		if(!$couponsms)
		{
			return new Object(-1, '데이터가 없어 접근이 불가능합니다.');
		}

		if(!$member_srl)
		{
			$member_srl = Context::get('logged_info')->member_srl;
		}
		Context::set('member_srl', $member_srl);
		Context::set('couponsms', $couponsms);
		$this->setTemplateFile('skin');
	}

	function dispCouponsmsList()
	{
		if(!Context::get('member_srl') && Context::get('is_logged'))
		{
			$member_srl = Context::get('logged_info')->member_srl;
		}
		else
		{
			return new Object(-1, '로그인사용자만 조회가능합니다.');
		}

		$args = new stdClass();
		$args->page = Context::get('page');
		$args->member_srl = $member_srl;
		$args->sort_index = 'regdate';
		$args->page = Context::get('page');
		$args->list_count = 20;
		$args->page_count = 10;
		$output = executeQuery('couponsms.getCouponUserListByMemberSrlInPage', $args);

		Context::set('coupon_list', $output->data);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);

		$this->setTemplateFile('coupon_list');
	}
}