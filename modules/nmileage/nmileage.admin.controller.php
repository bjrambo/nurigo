<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nmileageAdminController
 * @author NURIGO(contact@nurigo.net)
 * @brief  nmileageAdminController
 */
class nmileageAdminController extends nmileage
{

	function procNmileageAdminConfig() 
	{

		$args = Context::getRequestVars();
		
		// save module configuration.
		$oModuleControll = getController('module');
		$output = $oModuleControll->insertModuleConfig('nmileage', $args);

		$this->setMessage('success_updated');

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) 
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispNmileageAdminConfig','module_srl',Context::get('module_srl'));
			$this->setRedirectUrl($returnUrl);
			return;
		}

	}

	/**
	 * @brief 모듈 환경설정값 쓰기
	 **/
	function procNmileageAdminInsertModInst() 
	{
		// module 모듈의 model/controller 객체 생성
		$oModuleController = &getController('module');
		$oModuleModel = &getModel('module');

		// 게시판 모듈의 정보 설정
		$args = Context::getRequestVars();
		$args->module = 'nmileage';
		debugPrint('procNmileageADminInsertModInst');
		debugPrint($args);

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
			$output = $oModuleController->insertModule($args);
			$msg_code = 'success_registed';
		}
		else
		{
			$output = $oModuleController->updateModule($args);
			$msg_code = 'success_updated';
		}

		if(!$output->toBool()) return $output;

		$this->add('module_srl',$output->get('module_srl'));
		$this->setMessage($msg_code);

		$returnUrl = getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispNmileageAdminInsertModInst','module_srl',$this->get('module_srl'));
		$this->setRedirectUrl($returnUrl);
	}

	function procNmileageAdminDeleteModInst() 
	{
		$module_srl = Context::get('module_srl');

		$oModuleController = &getController('module');
		$output = $oModuleController->deleteModule($module_srl);
		if(!$output->toBool())
		{
			return $output;
		}

		$this->add('module','nmileage');
		$this->add('page',Context::get('page'));
		$this->setMessage('success_deleted');

		$returnUrl = getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispNmileageAdminModInstList');
		$this->setRedirectUrl($returnUrl);
	}


	function insertMileage($member_srl, $amount) 
	{
		$args->member_srl = $member_srl;
		$args->mileage = $amount;
		return executeQuery('nmileage.insertMileage', $args);
	}

	/*
		$args->member_srl = $member_srl;
		$args->amount = $amount;
		$args->action = $action; // 1: plus, 2: minus
		$args->title = $title;
		$args->balance = $balance;
	*/
	function insertMileageHistory($args, $order_srl=0) 
	{
		$args->history_srl = getNextSequence();
		$args->order_srl = $order_srl;
		return executeQuery('nmileage.insertMileageHistory', $args);
	}


	function procNmileageAdminPlusMileage() 
	{
		$oMemberModel = &getModel('member');
		$oNmileageController = &getController('nmileage');

		$user_id = Context::get('user_id');
		$amount = (int)Context::get('mileage');
		$title = Context::get('memo');
		
		$member_info = $oMemberModel->getMemberInfoByUserID($user_id);
		if(!$member_info)
		{
			return new Object(-1, 'Could not find member.');
		}

		$output = $oNmileageController->plusMileage($member_info->member_srl, $amount, $title);
		if(!$output->toBool()) return $output;

		$returnUrl = getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispNmileageAdminMileageHistory','member_srl',$member_info->member_srl,'page',Context::get('page'),'search_target',Context::get('search_target'), 'search_keyword', Context::get('search_keyword'));
		$this->setRedirectUrl($returnUrl);
	}

	function procNmileageAdminMinusMileage() 
	{
		$oMemberModel = &getModel('member');
		$oNmileageController = &getController('nmileage');

		$user_id = Context::get('user_id');
		$amount = (int)Context::get('mileage');
		$title = Context::get('memo');
		
		$member_info = $oMemberModel->getMemberInfoByUserID($user_id);
		if(!$member_info)
		{
			return new Object(-1, 'Could not find member.');
		}

		$output = $oNmileageController->minusMileage($member_info->member_srl, $amount, $title);
		if(!$output->toBool()) return $output;

		$returnUrl = getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispNmileageAdminMileageHistory','member_srl',$member_info->member_srl,'page',Context::get('page'),'search_target',Context::get('search_target'), 'search_keyword', Context::get('search_keyword'));
		$this->setRedirectUrl($returnUrl);
	}
}

/* End of file nmileage.admin.controller.php */
/* Location: ./modules/nmileage/nmileage.admin.controller.php */
