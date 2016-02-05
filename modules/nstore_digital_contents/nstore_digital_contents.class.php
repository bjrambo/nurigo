<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nstore_digital_contents
 * @author hosy(hosy@nurigo.net)
 * @brief  nstore_digital_contents
 */
class nstore_digital_contents extends ModuleObject
{
	/**
	 * @brief 모듈 설치 실행
	 **/
	function moduleInstall()
	{
		$oModuleModel = &getModel('module');
		$oModuleController = &getController('module');
		
		return new Object();
	}

	/**
	 * @brief 설치가 이상없는지 체크
	 **/
	function checkUpdate()
	{
		$oModuleModel = &getModel('module');
		$oDB = &DB::getInstance();

		return false;
	}

	/**
	 * @brief 업데이트(업그레이드)
	 **/
	function moduleUpdate()
	{
		$oDB = &DB::getInstance();
		$oModuleModel = &getModel('module');
		$oModuleController = &getController('module');
	}

	function moduleUninstall()
	{
		$oModuleController = &getController('module');
	}

	/**
	 * @brief 캐시파일 재생성
	 **/
	function recompileCache()
	{
	}
}
/* End of file nstore_digital_contents.class.php */
/* Location: ./modules/nstore_digital_contents/nstore_digital_contents.class.php */
