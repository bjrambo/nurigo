<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  inipaymobileAdminController
 * @author NURIGO(contact@nurigo.net)
 * @brief  inipaymobileAdminController
 */
class inipaymobileAdminController extends inipaymobile
{
	/**
	 * @brief not used yet
	 */
	function procInipaymobileAdminConfig() 
	{
		$args = Context::getRequestVars();
		
		// save module configuration.
		$oModuleControll = getController('module');
		$output = $oModuleControll->insertModuleConfig('inipaymobile', $args);
		if(!$output->toBool()) return $output;

		$this->setMessage('success_updated');

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) 
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispInipaymobileAdminConfig','module_srl',Context::get('module_srl'));
			$this->setRedirectUrl($returnUrl);
			return;
		}
	}


	/**
	 * @brief writes module instance configuration.
	 */
	function procInipaymobileAdminInsertModInst() 
	{
		// get the instance of the model and controller of the module.
		$oModuleController = &getController('module');
		$oModuleModel = &getModel('module');

		// get all requested vars
		$args = Context::getRequestVars();

		// set module name
		$args->module = 'inipaymobile';

		// check if the module instance already exists
		if($args->module_srl) 
		{
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);
			if($module_info->module_srl != $args->module_srl)
			{
				// unset the module_srl to be reallocated if the module instance already exists
				unset($args->module_srl);
			}
		}

		// insert or update depending on the module_srl existence
		if(!$args->module_srl) 
		{
			$output = $oModuleController->insertModule($args);
			if(!$output->toBool()) return $output;
			$msg_code = 'success_registed';
		}
		else
		{
			$output = $oModuleController->updateModule($args);
			if(!$output->toBool()) return $output;
			$msg_code = 'success_updated';
		}

        // make log directory
        $path = sprintf(_XE_PATH_."files/epay/%s/log",$output->get('module_srl'));
        if(!FileHandler::makeDir($path)) return new Object(-1, 'could not create a directory');

		$this->add('module_srl',$output->get('module_srl'));
		$this->setMessage($msg_code);

		$returnUrl = getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispInipaymobileAdminInsertModInst','module_srl',$output->get('module_srl'));
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * @brief delete a module instance.
	 */
	function procInipaymobileAdminDeleteModInst()
	{
		// get module_srl
		$module_srl = Context::get('module_srl');

		// execute deletion calling the module controller function
		$oModuleController = &getController('module');
		$output = $oModuleController->deleteModule($module_srl);
		if(!$output->toBool()) return $output;

		$this->add('module', 'inipaymobile');
		$this->add('page', Context::get('page'));
		$this->setMessage('success_deleted');

		$returnUrl = getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispInipaymobileModInstList');
		$this->setRedirectUrl($returnUrl);
	}
}
/* End of file inipaymobile.admin.controller.php */
/* Location: ./modules/inipaymobile/inipaymobile.admin.controller.php */
