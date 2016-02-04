<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nmileageController
 * @author NURIGO(contact@nurigo.net)
 * @brief  nmileageController
 */
require_once(_XE_PATH_.'modules/nmileage/nmileage.view.php');

class nmileageMobile extends nmileageView {
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
/* End of file nmileage.mobile.php */
/* Location: ./modules/nmileage/nmileage.mobile.php */
