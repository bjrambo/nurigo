<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  kcpAdminController
 * @author NURIGO(contact@nurigo.net)
 * @brief  kcpAdminController
 */
class kcpAdminController extends kcp
{
	/**
	 * @brief inserts virtual account numbers into the kcp DB table, called by dispKcpAdminInsert
	 */
	function procKcpAdminInsert()
	{
		$count = 0; // count for inserting records
		$bank = Context::get('bank');
		$van_list = explode("\n", Context::get('van_list'));
		foreach($van_list as $van)
		{
			if(!$van) continue; // check if $van is empty
			$args->bank = $bank;
			$args->van = trim($van);
			$output = executeQuery('kcp.insertAccount', $args);
			if(!$output->toBool()) return $output;
			$count++;
		}
		$this->setMessage(sprintf(Context::getLang('msg_regist_count'), $count));
		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) 
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispKcpAdminInsert');
			$this->setRedirectUrl($returnUrl);
		}
	}

	/**
	 * @brief not used yet
	 */
	function procKcpAdminConfig() 
	{
		$args = Context::getRequestVars();
		
		// save module configuration.
		$oModuleControll = getController('module');
		$output = $oModuleControll->insertModuleConfig('kcp', $args);
		if(!$output->toBool()) return $output;

		$this->setMessage('success_updated');

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) 
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispKcpAdminConfig','module_srl',Context::get('module_srl'));
			$this->setRedirectUrl($returnUrl);
			return;
		}
	}


	/**
	 * @brief writes module instance configuration.
	 */
	function procKcpAdminInsertModInst() 
	{
		// get the instance of the model and controller of the module.
		$oModuleController = &getController('module');
		$oModuleModel = &getModel('module');

		// get all requested vars
		$args = Context::getRequestVars();
		// set module name
		$args->module = 'kcp';

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

		$image_obj = Context::get('site_logo');


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
		$path = sprintf(_XE_PATH_."files/epay/kcp/log");
		if(!FileHandler::makeDir($path)) return new Object(-1, 'could not create a directory');
		$path = sprintf(_XE_PATH_."files/epay/kcp/%u", $output->get('module_srl'));
		if(!FileHandler::makeDir($path)) return new Object(-1, 'could not create a directory');

		$image_path = sprintf("files/epay/kcp/%u/", $output->get('module_srl'));
		// 정상적으로 업로드된 파일이 아니면 무시
		if($image_obj['tmp_name'] && is_uploaded_file($image_obj['tmp_name']) && preg_match("/\.(jpg|jpeg|gif)$/i", $image_obj['name']))
		{
			$filename = $image_path.$image_obj['name'];
			if(!move_uploaded_file($image_obj['tmp_name'], $filename)) 
			{
				 return new Object(-1, 'move_uploaded_file error');
			}
			$args->site_logo = $filename;
			$output = $oModuleController->updateModule($args);
			if(!$output->toBool()) return $output;
		}


		$this->add('module_srl',$output->get('module_srl'));
		$this->setMessage($msg_code);

		$returnUrl = getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispKcpAdminInsertModInst','module_srl',$output->get('module_srl'));
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * @brief delete a module instance.
	 */
	function procKcpAdminDeleteModInst()
	{
		// get module_srl
		$module_srl = Context::get('module_srl');

		// execute deletion calling the module controller function
		$oModuleController = &getController('module');
		$output = $oModuleController->deleteModule($module_srl);
		if(!$output->toBool()) return $output;

		$this->add('module', 'kcp');
		$this->add('page', Context::get('page'));
		$this->setMessage('success_deleted');

		$returnUrl = getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispKcpModInstList');
		$this->setRedirectUrl($returnUrl);
	}
}
/* End of file kcp.admin.controller.php */
/* Location: ./modules/kcp/kcp.admin.controller.php */
