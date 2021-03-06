<?php

class cympusadminAdminModel extends cympusadmin
{
	function getCympusadminAdminDeleteModInst()
	{
		$oModuleModel = getModel('module');

		$module_srl = Context::get('module_srl');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
		Context::set('module_info', $module_info);

		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path . 'tpl', 'form_delete_modinst');
		$this->add('tpl', str_replace("\n", " ", $tpl));
	}
}