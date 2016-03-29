<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  cashpayMobile
 * @author NURIGO(contact@nurigo.net)
 * @brief  cashpayMobile class
 */
require_once(_XE_PATH_ . 'modules/cashpay/cashpay.view.php');

class cashpayMobile extends cashpayView
{
	function init()
	{
		$template_path = sprintf("%sm.skins/%s/", $this->module_path, $this->module_info->mskin);
		if(!is_dir($template_path) || !$this->module_info->mskin)
		{
			$this->module_info->mskin = 'default';
			$template_path = sprintf("%sm.skins/%s/", $this->module_path, $this->module_info->mskin);
		}
		$this->setTemplatePath($template_path);

		Context::addJsFile('common/js/jquery.min.js');
		Context::addJsFile('common/js/xe.min.js');
	}
}
/* End of file cashpay.item.php */
/* Location: ./modules/cashpay/cashpay.mobile.php */
