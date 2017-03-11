<?php

/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  cympusAdminController
 * @author NURIGO(contact@nurigo.net)
 * @brief  cympusAdminController
 */
class cympusadminAdminController extends cympusadmin
{
	/**
	 * @brief 모듈 환경설정값 쓰기
	 **/
	function procCympusadminAdminInsertModInst()
	{
		// module 모듈의 model/controller 객체 생성
		$oModuleController = getController('module');
		$oModuleModel = getModel('module');

		$args = new stdClass();
		$args->module = 'cympusadmin';

		// 게시판 모듈의 정보 설정
		$args = Context::getRequestVars();
		$args->module = 'cympusadmin';

		// module_srl이 넘어오면 원 모듈이 있는지 확인
		if($args->module_srl)
		{
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);
			if($module_info->module_srl != $args->module_srl)
			{
				unset($args->module_srl);
			}
		}

		// module_srl의 값에 따라 insert/update
		if(!$args->module_srl)
		{
			$module_list = getModel('module')->getModuleSrlList($args);
			if(count($module_list) > 0)
			{
				return new Object(-1, 'msg_dont_insert_module_inst');
			}
			$output = $oModuleController->insertModule($args);
			$msg_code = 'success_registed';
		}
		else
		{
			$output = $oModuleController->updateModule($args);
			$msg_code = 'success_updated';
		}

		if(!$output->toBool())
		{
			return $output;
		}


		$this->add('module_srl', $output->get('module_srl'));
		$this->setMessage($msg_code);

		$returnUrl = getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispCympusadminAdminInsertModInst', 'module_srl', $output->get('module_srl'));
		$this->setRedirectUrl($returnUrl);
	}

	function procCympusadminAdminDeleteModInst()
	{
		$module_srl = Context::get('module_srl');

		$oModuleController = getController('module');
		$output = $oModuleController->deleteModule($module_srl);
		if(!$output->toBool())
		{
			return $output;
		}

		$this->add('module', 'cympus');
		$this->add('page', Context::get('page'));
		$this->setMessage('success_deleted');

		$returnUrl = getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispCympusadminAdminModInstList');
		$this->setRedirectUrl($returnUrl);
	}

	function procCympusadminAdminConfig()
	{
		$oModuleController = getController('module');
		$obj = Context::getRequestVars();
		$output = $oModuleController->updateModuleConfig('cympusadmin', $obj);
		if(!$output->toBool())
		{
			return new Object(-1, 'ncenterlite_msg_setting_error');
		}

		$this->setMessage('success_updated');

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON')))
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispCympusadminAdminConfig');
			header('location: ' . $returnUrl);
			return;
		}
	}
}
