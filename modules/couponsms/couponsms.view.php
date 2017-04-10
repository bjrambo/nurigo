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
		else
		{
			$this->module_info->layout_srl = '0';
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
}