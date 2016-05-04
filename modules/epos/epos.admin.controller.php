<?php

/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  eposAdminController
 * @author NURIGO(contact@nurigo.net)
 * @brief  eposAdminController
 */
class eposAdminController extends epos
{
	/**
	 * @brief inserts virtual account numbers into the epos DB table, called by dispEposAdminInsert
	 */
	function procEposAdminInsert()
	{
		$count = 0; // count for inserting records
		$bank = Context::get('bank');
		$van_list = explode("\n", Context::get('van_list'));
		foreach($van_list as $van)
		{
			if(!$van)
			{
				continue;
			} // check if $van is empty
			$args = new stdClass();
			$args->bank = $bank;
			$args->van = trim($van);
			$output = executeQuery('epos.insertAccount', $args);
			if(!$output->toBool())
			{
				return $output;
			}
			$count++;
		}
		$this->setMessage(sprintf(Context::getLang('msg_regist_count'), $count));
		if(!in_array(Context::getRequestMethod(), array('XMLRPC', 'JSON')))
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispEposAdminInsert');
			$this->setRedirectUrl($returnUrl);
		}
	}

	/**
	 * @brief writes module instance configuration.
	 */
	function procEposAdminInsertModInst()
	{
		// get the instance of the model and controller of the module.
		$oModuleController = getController('module');
		$oModuleModel = getModel('module');

		// get all requested vars
		$args = Context::getRequestVars();
		$output = $oModuleController->insertModuleConfig('epos', $args);
		if(!$output->toBool())
		{
			return $output;
		}

		// set module name
		$args->module = 'epos';
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
			if(!$output->toBool())
			{
				return $output;
			}
			$msg_code = 'success_registed';
		}
		else
		{
			$output = $oModuleController->updateModule($args);
			if(!$output->toBool())
			{
				return $output;
			}
			$msg_code = 'success_updated';
		}

		$args->module_srl = $output->get('module_srl');
		$output = $oModuleController->updateModule($args);
		if(!$output->toBool())
		{
			return $output;
		}

		/*
				// make log directory
				$path = sprintf(_XE_PATH_."files/epay/%s/log",$output->get('module_srl'));
				if(!FileHandler::makeDir($path)) return new Object(-1, 'could not create a directory');
		*/

		$this->add('module_srl', $output->get('module_srl'));
		$this->setMessage($msg_code);

		$returnUrl = getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispEposAdminInsertModInst', 'module_srl', $output->get('module_srl'));
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * @brief delete a module instance.
	 */
	function procEposAdminDeleteModInst()
	{
		// get module_srl
		$module_srl = Context::get('module_srl');

		// execute deletion calling the module controller function
		$oModuleController = getController('module');
		$output = $oModuleController->deleteModule($module_srl);
		if(!$output->toBool())
		{
			return $output;
		}

		$this->add('module', 'epos');
		$this->add('page', Context::get('page'));
		$this->setMessage('success_deleted');

		$returnUrl = getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispEposModInstList');
		$this->setRedirectUrl($returnUrl);
	}
}
/* End of file epos.admin.controller.php */
/* Location: ./modules/epos/epos.admin.controller.php */
