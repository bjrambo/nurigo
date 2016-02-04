<?php
/**
 * vi:set ts=4 sw=4 noexpandtab fileencoding=utf-8:
 * @class  epayAdminModel
 * @author NURIGO(contact@nurigo.net)
 * @brief  epay admin model 
 **/
class epayAdminModel extends epay
{
	function init()
	{
	}

	function getEpayAdminDeletePlugin()
	{
		$args->plugin_srl = Context::get('plugin_srl');
		$output = executeQuery('epay.getPluginInfo', $args);
		if($output->toBool() && $output->data){
			$plugin_info = $output->data;
			Context::set('plugin_info', $output->data);
		}
		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path.'tpl', 'form_delete_plugin');

		$this->add('tpl', str_replace("\n"," ",$tpl));
	}

	function getEpayAdminDeleteModInst()
	{
		$oModuleModel = &getModel('module');

		$module_srl = Context::get('module_srl');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
		Context::set('module_info', $module_info);

		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path.'tpl', 'form_delete_modinst');
		$this->add('tpl', str_replace("\n"," ",$tpl));
	}
}
/* End of file epay.admin.model.php */
/* Location: ./modules/epay/epay.admin.model.php */
