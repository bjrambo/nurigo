<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  license
 * @author NURIGO(contact@nurigo.net)
 * @brief  license
 */
class license extends ModuleObject
{
	/**
	 * @brief 모듈 설치 실행
	 **/
	function moduleInstall()
	{
	}

	/**
	 * @brief 설치가 이상없는지 체크
	 **/
	function checkUpdate()
	{
		$oModuleModel = &getModel('module');
		$oNproductModel =  &getModel('nproduct');
		$oDB = &DB::getInstance();

		return FALSE;
	}

	/**
	 * @brief 업데이트(업그레이드)
	 **/
	function moduleUpdate()
	{
		$oDB = &DB::getInstance();
		return new Object(0, 'success_updated');
	}

	/**
	 * @brief 캐시파일 재생성
	 **/
	function recompileCache()
	{
	}
}
/* End of file license.class.php */
/* Location: ./modules/license/license.class.php */
