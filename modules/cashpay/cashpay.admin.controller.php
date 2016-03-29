<?php

/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  cashpayAdminController
 * @author NURIGO(contact@nurigo.net)
 * @brief  cashpayAdminController
 */
class cashpayAdminController extends cashpay
{
	/**
	 * @brief not used yet
	 */
	function procCashpayAdminConfig()
	{
		$args = Context::getRequestVars();

		// save module configuration.
		$oModuleControll = getController('module');
		$output = $oModuleControll->insertModuleConfig('cashpay', $args);
		if(!$output->toBool())
		{
			return $output;
		}

		$this->setMessage('success_updated');

		if(!in_array(Context::getRequestMethod(), array('XMLRPC', 'JSON')))
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispCashpayAdminConfig', 'module_srl', Context::get('module_srl'));
			$this->setRedirectUrl($returnUrl);
			return;
		}
	}


	/**
	 * @brief writes module instance configuration.
	 */
	function procCashpayAdminInsertModInst()
	{
		// get the instance of the model and controller of the module.
		$oModuleController = getController('module');
		$oModuleModel = getModel('module');

		// get all requested vars
		$args = Context::getRequestVars();
		unset($args->keypass);
		unset($args->mcert);
		unset($args->mpriv);
		// set module name
		$args->module = 'cashpay';

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
		$args->keypass = $module_info->keypass;
		$args->mcert = $module_info->mcert;
		$args->mpriv = $module_info->mpriv;

		// save inicis key files
		$path = sprintf("./files/epay/%s/key/%s/", $args->module_srl, $args->inicis_id);
		if(!FileHandler::makeDir($path))
		{
			return new Object(-1, 'could not create a directory');
		}
		$key_files = Context::gets('keypass', 'mcert', 'mpriv');
		foreach($key_files as $key => $file)
		{
			if(!$file)
			{
				continue;
			}
			$filename = $path . $file['name'];
			$args->{$key} = $filename;
			if(!move_uploaded_file($file['tmp_name'], $filename))
			{
				return new Object(-1, 'could not move the file uploaded');
			}
		}
		// pgcert
		$pgcert_src = sprintf("%s/modules/cashpay/key/pgcert.pem", _XE_PATH_);
		$pgcert_path = sprintf("./files/epay/%s/key/pgcert.pem", $args->module_srl);
		copy($pgcert_src, $pgcert_path);

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

		$this->add('module_srl', $output->get('module_srl'));
		$this->setMessage($msg_code);

		$returnUrl = getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispCashpayAdminInsertModInst', 'module_srl', $output->get('module_srl'));
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * @brief delete a module instance.
	 */
	function procCashpayAdminDeleteModInst()
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

		$this->add('module', 'cashpay');
		$this->add('page', Context::get('page'));
		$this->setMessage('success_deleted');

		$returnUrl = getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispCashpayModInstList');
		$this->setRedirectUrl($returnUrl);
	}
}
/* End of file cashpay.admin.controller.php */
/* Location: ./modules/cashpay/cashpay.admin.controller.php */
