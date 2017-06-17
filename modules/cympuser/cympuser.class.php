<?php

/**
 * @class cympuser
 * @author billy(contact@nurigo.net)
 * @brief cympuser
 */
class cympuser extends ModuleObject
{
	protected static $config = NULL;

	protected static function getConfig()
	{
		if(self::$config === NULL)
		{
			$config = getModel('module')->getModuleConfig('cympuer');
			if(!$config)
			{
				$config = new stdClass();
			}
			self::$config = $config;
		}
		return self::$config;
	}

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
	}

	/**
	 * @brief 업데이트(업그레이드)
	 **/
	function moduleUpdate()
	{
	}

	/**
	 * @brief 캐시파일 재생성
	 **/
	function recompileCache()
	{
	}
}
