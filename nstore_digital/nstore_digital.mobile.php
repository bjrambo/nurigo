<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nstore_digitalModel
 * @author NURIGO(contact@nurigo.net)
 * @brief  nstore_digitalModel
 */
require_once(_XE_PATH_.'modules/nstore_digital/nstore_digital.view.php');
class nstore_digitalMobile extends nstore_digitalView {
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
/* End of file nstore_digital.mobile.php */
/* Location: ./modules/nstore_digital/nstore_digital.mobile.php */
