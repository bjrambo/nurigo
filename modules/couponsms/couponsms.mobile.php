<?php
require_once(_XE_PATH_ . 'modules/couponsms/couponsms.view.php');
class couponsmsMobile extends couponsmsView
{
	function init()
	{
		$oCouponsmsModel = getModel('couponsms');
		$config = $oCouponsmsModel->getConfig();
		$template_path = sprintf("%sm.skins/%s/",$this->module_path, $config->mskin);
		if(!is_dir($template_path)||!$config->mskin)
		{
			$config->mskin = 'default';
			$template_path = sprintf("%sm.skins/%s/",$this->module_path, $config->mskin);
		}
		$this->setTemplatePath($template_path);
	}
}