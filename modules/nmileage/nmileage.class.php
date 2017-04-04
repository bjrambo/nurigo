<?php

/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nmileage
 * @author NURIGO(contact@nurigo.net)
 * @brief  nmileage
 */
class nmileage extends ModuleObject
{
	protected static $insert_triggers = array(
		array('menu.getModuleListInSitemap', 'nmileage', 'model', 'triggerModuleListInSitemap', 'after'),
		array('member.insertMember', 'nmileage', 'controller', 'triggerMemberInsertAfter', 'after')
	);

	private static $delete_trigger = array();
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
		$oModuleModel = getModel('module');
		foreach (self::$insert_triggers as $trigger)
		{
			if (!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				return true;
			}
		}

		return FALSE;
	}

	/**
	 * @brief 업데이트(업그레이드)
	 **/
	function moduleUpdate()
	{
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');

		foreach (self::$insert_triggers as $trigger)
		{
			if (!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				$oModuleController->insertTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
			}
		}

		return new Object(0, 'success_updated');
	}

	/**
	 * @brief 캐시파일 재생성
	 **/
	function recompileCache()
	{
	}
}

/* End of file nmileage.class.php */
/* Location: ./modules/nmileage/nmileage.class.php */
