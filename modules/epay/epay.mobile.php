<?php
/**
 * vi:set ts=4 sw=4 noexpandtab fileencoding=utf-8:
 * @class epay
 * @author NURIGO(contact@nurigo.net)
 * @brief epay class
 **/
require_once(_XE_PATH_.'modules/epay/epay.view.php');
class epayMobile extends epayView {
	function init()
	{
		Context::set('admin_bar', 'false');
		Context::set('hide_trolley', 'true');

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
/* End of file epay.mobile.php */
/* Location: ./modules/epay/epay.mobile.php */
