<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  inipayAdminController
 * @author NURIGO(contact@nurigo.net)
 * @brief  inipayAdminController
 */
class inipayAdminController extends inipay
{
	/**
	 * @brief inserts virtual account numbers into the inipay DB table, called by dispInipayAdminInsert
	 */
	function procInipayAdminInsert()
	{
		$count = 0; // count for inserting records
		$bank = Context::get('bank');
		$van_list = explode("\n", Context::get('van_list'));
		foreach($van_list as $van)
		{
			if(!$van) continue; // check if $van is empty
			$args->bank = $bank;
			$args->van = trim($van);
			$output = executeQuery('inipay.insertAccount', $args);
			if(!$output->toBool()) return $output;
			$count++;
		}
		$this->setMessage(sprintf(Context::getLang('msg_regist_count'), $count));
		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) 
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispInipayAdminInsert');
			$this->setRedirectUrl($returnUrl);
		}
	}

	/**
	 * @brief not used yet
	 */
	function procInipayAdminConfig() 
	{
		$args = Context::getRequestVars();
		
		// save module configuration.
		$oModuleControll = getController('module');
		$output = $oModuleControll->insertModuleConfig('inipay', $args);
		if(!$output->toBool()) return $output;

		$this->setMessage('success_updated');

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) 
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispInipayAdminConfig','module_srl',Context::get('module_srl'));
			$this->setRedirectUrl($returnUrl);
			return;
		}
	}


	/**
	 * @brief writes module instance configuration.
	 */
	function procInipayAdminInsertModInst() 
	{
		// get the instance of the model and controller of the module.
		$oModuleController = &getController('module');
		$oModuleModel = &getModel('module');

		// get all requested vars
		$args = Context::getRequestVars();
		unset($args->keypass);
		unset($args->mcert);
		unset($args->mpriv);
		// set module name
		$args->module = 'inipay';

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

		// set key files path
		$key_files = Context::gets('keypass','mcert','mpriv');
		$path = sprintf(_XE_PATH_."files/epay/%s/key/%s/",$output->get('module_srl'), $args->inicis_id);
		if(!FileHandler::makeDir($path)) return new Object(-1, 'could not create a directory');
		foreach($key_files as $key=>$file)
		{
			if(!$file) continue;
			$filename = $path.$file['name'];
			$args->{$key} = $filename;
			if(!move_uploaded_file($file['tmp_name'], $filename)) return new Object(-1, 'could not move the file uploaded');
		}
		// pgcert
		$pgcert_src = sprintf("%s/modules/inipay/key/pgcert.pem",_XE_PATH_);
		$pgcert_path = sprintf(_XE_PATH_."files/epay/%s/key/pgcert.pem",$output->get('module_srl'));
		copy($pgcert_src, $pgcert_path);
		$args->module_srl = $output->get('module_srl');
		// logo image
		$image_obj = Context::get('logo_image');
		if($image_obj)
		{
			$image_path = sprintf("files/epay/%u/", $output->get('module_srl'));
			// 정상적으로 업로드된 파일이 아니면 무시
			if($image_obj['tmp_name'] && is_uploaded_file($image_obj['tmp_name']) && preg_match("/\.(jpg|jpeg|gif)$/i", $image_obj['name']))
			{
				$filename = $image_path.$image_obj['name'];
				if(!move_uploaded_file($image_obj['tmp_name'], $filename))
				{
					 return new Object(-1, 'move_uploaded_file error');
				}
				$args->logo_image = $filename;
			}
			else
			{
				return new Object(-1, 'Not supported image type');
			}
		}
		// update module info.
		$output = $oModuleController->updateModule($args);
		if(!$output->toBool()) return $output;

		// make log directory
		$path = sprintf(_XE_PATH_."files/epay/%s/log",$output->get('module_srl'));
		if(!FileHandler::makeDir($path)) return new Object(-1, 'could not create a directory');


   		$this->add('module_srl',$output->get('module_srl'));
		$this->setMessage($msg_code);

		$returnUrl = getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispInipayAdminInsertModInst','module_srl',$output->get('module_srl'));
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * @brief delete a module instance.
	 */
	function procInipayAdminDeleteModInst()
	{
		// get module_srl
		$module_srl = Context::get('module_srl');

		// execute deletion calling the module controller function
		$oModuleController = &getController('module');
		$output = $oModuleController->deleteModule($module_srl);
		if(!$output->toBool()) return $output;

		$this->add('module', 'inipay');
		$this->add('page', Context::get('page'));
		$this->setMessage('success_deleted');

		$returnUrl = getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispInipayModInstList');
		$this->setRedirectUrl($returnUrl);
	}
}
/* End of file inipay.admin.controller.php */
/* Location: ./modules/inipay/inipay.admin.controller.php */
