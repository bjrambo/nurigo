<?php

/**
 * @class  inipaystandardAdminController
 * @author CONORY (https://www.conory.com)
 * @brief The admin controller class of the inipaystandard module
 */
class inipaystandardAdminController extends inipaystandard
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	/**
	 * @brief insert Module
	 **/
	function procInipaystandardAdminInsertModule()
	{
		$oModuleController = getController('module');
		$oModuleModel = getModel('module');

		$args = Context::getRequestVars();
		$args->module = 'inipaystandard';

		if($args->module_srl)
		{
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);
			if($module_info->module_srl != $args->module_srl)
			{
				unset($args->module_srl);
			}
		}

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

		$returnUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispInipaystandardAdminInsertModule', 'module_srl', $output->get('module_srl'));
		$this->setRedirectUrl($returnUrl);
		$this->setMessage($msg_code);
	}

	/**
	 * @brief delete module
	 **/
	function procInipaystandardAdminDeleteModule()
	{
		$module_srl = Context::get('module_srl');

		$oModuleController = getController('module');
		$output = $oModuleController->deleteModule($module_srl);
		if(!$output->toBool())
		{
			return $output;
		}

		$this->setMessage('success_deleted');

		$returnUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispInipaystandardAdminModuleList');
		$this->setRedirectUrl($returnUrl);
	}
}