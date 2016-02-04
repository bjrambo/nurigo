<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nproductMobile
 * @author NURIGO(contact@nurigo.net)
 * @brief  nproductMobile class
 */
require_once(_XE_PATH_.'modules/nproduct/nproduct.view.php');
class nproductMobile extends nproductView
{
	function init()
	{
		$template_path = sprintf("%sm.skins/%s/",$this->module_path, $this->module_info->mskin);
		if(!is_dir($template_path)||!$this->module_info->mskin) 
		{
			$this->module_info->mskin = 'default';
			$template_path = sprintf("%sm.skins/%s/",$this->module_path, $this->module_info->mskin);
		}
		$this->setTemplatePath($template_path);

		Context::addJsFile('common/js/jquery.min.js');
		Context::addJsFile('common/js/xe.min.js');
	}
}
/* End of file nproduct.item.php */
/* Location: ./modules/nproduct/nproduct.mobile.php */
