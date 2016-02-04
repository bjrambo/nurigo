<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  inipaymobileMobile
 * @author NURIGO(contact@nurigo.net)
 * @brief  inipaymobileMobile class
 */
require_once(_XE_PATH_.'modules/inipaymobile/inipaymobile.view.php');
class inipaymobileMobile extends inipaymobileView
{
	function init()
	{
		$template_path = sprintf("%sm.skins/%s/",$this->module_path, $this->module_info->mskin);
		if(!is_dir($template_path)||!$this->module_info->mskin) {
			$this->module_info->mskin = 'default';
			$template_path = sprintf("%sm.skins/%s/",$this->module_path, $this->module_info->mskin);
		}
		$this->setTemplatePath($template_path);

		Context::addJsFile('common/js/jquery.min.js');
		Context::addJsFile('common/js/xe.min.js');
	}
}
/* End of file inipaymobile.item.php */
/* Location: ./modules/inipaymobile/inipaymobile.mobile.php */
