<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  cympuserAdminController
 * @author billy(contact@nurigo.net)
 * @brief  cympuserAdminController
 */
class cympuserAdminController extends cympuser 
{
	/**
	 * @brief constructor
	 */
	function init() 
	{
	}
		
	function procCympuserAdminModInsert()
	{
		$oModuleController = &getController('module');
		$oModuleModel = &getModel('module');

		$args = Context::getRequestVars();
		$args->module = 'cympuser';

		// 모듈 정보 가져오기
		if($args->module_srl) 
		{
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);
			if($module_info->module_srl != $args->module_srl) 
				unset($args->module_srl);
		}

		// module_srl의 값에 따라 insert/update
		if(!$args->module_srl) {
			$output = $oModuleController->insertModule($args);
			$msg_code = 'success_registed';
		} else {
			$output = $oModuleController->updateModule($args);
			$msg_code = 'success_updated';
		}
		if(!$output->toBool()) return $output;

		$this->add('module_srl',$output->get('module_srl'));
		$this->setMessage($msg_code);	

	}

	function procCympuserAdminModDelete()
	{
		$oModuleController = &getController('module');

		$module_srl = Context::get('module_srl');
		if(!$module_srl) 
			return new Object(-1, 'module_srl 이 비었습니다.');

		$output = $oModuleController->deleteModule($module_srl);
		if(!$output->toBool()) return $output;
	
		$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispCympuserAdminList');
		$this->setRedirectUrl($redirectUrl);
	}
}
?>
