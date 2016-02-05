<?php
    /**
     * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
     * @class  nmileageAdminModel
     * @author NURIGO(contact@nurigo.net)
     * @brief  nmileageAdminModel
     */ 
class nmileageAdminModel extends nmileage
{
	function getNmileageAdminDeleteModInst() {
		$oModuleModel = &getModel('module');

		$module_srl = Context::get('module_srl');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
		Context::set('module_info', $module_info);

		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path.'tpl', 'form_delete_modinst');
		$this->add('tpl', str_replace("\n"," ",$tpl));
	}

	function getNmileageAdminPlusMileage() {
		$oModuleModel = &getModel('module');

		$module_srl = Context::get('module_srl');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
		Context::set('module_info', $module_info);

		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path.'tpl', 'form_plus_mileage');
		$this->add('tpl', str_replace("\n"," ",$tpl));
	}

	function getNmileageAdminMinusMileage() {
		$oModuleModel = &getModel('module');

		$module_srl = Context::get('module_srl');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
		Context::set('module_info', $module_info);

		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path.'tpl', 'form_minus_mileage');
		$this->add('tpl', str_replace("\n"," ",$tpl));
	}

	function getNmileageAdminCheckUserId()
	{
		$oMemberModel = &getModel('member');

		$logged_info = Context::get('logged_info');
		if($logged_info->is_admin != 'Y') return new Object(-1, 'msg_invalid_request');
		if(!Context::get('user_id')) return;

		$columnList = array('email_address', 'user_id', 'nick_name');
		$member_info = $oMemberModel->getMemberInfoByUserID(Context::get('user_id'), $columnList);
		if(!$member_info)
		{
	   		$this->add('alert_message', Context::getLang('invalid_user_id'));
			return;
		}

		$this->add('data', $member_info);
	}
}
/* End of file nmileage.admin.model.php */
/* Location: ./modules/nmileage/nmileage.admin.model.php */
