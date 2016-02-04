<?php
	/**
	 * @class  inipaystandard
     * @author CONORY (http://www.conory.com)
	 * @brief The parent class of the inipaystandard module
	 */
	class inipaystandard extends ModuleObject
	{
		/**
		 * @brief 모듈 설치
		 */
		function moduleInstall()
		{
            $oModuleModel = getModel('module');
            $oModuleController = getController('module');
			
			return new Object();
		}

		/**
		 * @brief 업데이트 체크
		 */
		function checkUpdate()
		{
            $oDB = DB::getInstance();
            $oModuleModel = getModel('module');	
			
			if(!$oModuleModel->getTrigger('moduleHandler.init', 'inipaystandard', 'controller', 'triggerModuleHandler', 'before')) return true;
			if(!$oModuleModel->getTrigger('epay.getPgModules', 'inipaystandard', 'model', 'triggerGetPgModules', 'before')) return true;
			
			return false;
		}

		/**
		 * @brief 업데이트
		 */
		function moduleUpdate()
		{
            $oDB = DB::getInstance();
            $oModuleModel = getModel('module');
            $oModuleController = getController('module');		
			
			if(!$oModuleModel->getTrigger('epay.getPgModules', 'inipaystandard', 'model', 'triggerGetPgModules', 'before'))
				$oModuleController->insertTrigger('epay.getPgModules', 'inipaystandard', 'model', 'triggerGetPgModules', 'before');
			
			if(!$oModuleModel->getTrigger('moduleHandler.init', 'inipaystandard', 'controller', 'triggerModuleHandler', 'before'))
				$oModuleController->insertTrigger('moduleHandler.init', 'inipaystandard', 'controller', 'triggerModuleHandler', 'before');
			
			return new Object(0, 'success_updated');
		}
		
		/**
		 * @brief 모듈삭제
		 */
		function moduleUninstall()
		{
			return new Object();
		}
		
		/**
		 * @brief 캐시파일 재생성
		 */
		function recompileCache()
		{
			
		}
	}