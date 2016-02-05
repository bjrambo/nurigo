<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  licenseAdminController
 * @author NURIGO(contact@nurigo.net)
 * @brief  licenseAdminController
 */
class licenseAdminController extends license
{
	/**
	 * @brief 모듈 환경설정값 쓰기
	 **/
	function procLicenseAdminConfig() 
	{
		$args = Context::getRequestVars();
		debugPrint($args);
		
		// save module configuration.
		$oModuleControll = getController('module');
		$output = $oModuleControll->insertModuleConfig('license', $args);

		$oLicenseModel = &getModel('license');
		$oLicenseModel->checkLicense('nstore', $args->user_id, $args->serial_number, TRUE);
		$oLicenseModel->checkLicense('nstore_digital', $args->d_user_id, $args->d_serial_number, TRUE);

		$this->setMessage('success_updated');

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) 
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispLicenseAdminConfig','module_srl',Context::get('module_srl'));
			$this->setRedirectUrl($returnUrl);
		}
	}
}

/* End of file license.admin.controller.php */
/* Location: ./modules/license/license.admin.controller.php */
