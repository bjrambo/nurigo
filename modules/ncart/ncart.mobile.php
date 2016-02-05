<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  ncartController
 * @author NURIGO(contact@nurigo.net)
 * @brief  ncartController
 */
require_once(_XE_PATH_.'modules/ncart/ncart.view.php');
class ncartMobile extends ncartView {
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

		$logged_info = Context::get('logged_info');

		if($logged_info) Context::set('login_chk','Y');
		else if(!$logged_info) Context::set('login_chk','N');

		Context::set('hide_trolley', 'true');
	}
}
/* End of file ncart.mobile.php */
/* Location: ./modules/ncart/ncart.mobile.php */
