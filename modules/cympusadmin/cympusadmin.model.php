<?php

/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nstoreModel
 * @author NURIGO(contact@nurigo.net)
 * @brief  nstoreModel
 */
class cympusadminModel extends cympusadmin
{
	private static $config = NULL;

	function getConfig()
	{
		if(self::$config === NULL)
		{
			$oModuleModel = getModel('module');
			$config = $oModuleModel->getModuleConfig('cympusadmin');

			if(!$config)
			{
				$config = new stdClass();
			}
			if(!$config->admin_skins)
			{
				$config->admin_skins = 'default';
			}

			self::$config = $config;
		}

		return self::$config;
	}

	function triggerGetManagerMenu(&$manager_menu)
	{
		$oModuleModel = getModel('module');

		$logged_info = Context::get('logged_info');

		$output = executeQueryArray('cympusadmin.getModInstList');
		if(!$output->toBool())
		{
			return $output;
		}

		$list = $output->data;

		$menu = new stdClass();
		$menu->title = Context::getLang('site_management');
		$menu->icon = 'dashboard';
		$menu->module = 'cympusadmin';
		$menu->submenu = array();

		foreach($list as $key => $val)
		{
			$grant = $oModuleModel->getGrant($val, $logged_info);
			if($grant->manager)
			{
				$submenu = new stdClass();
				$submenu->action = array('dispCympusadminAdminIndex');
				$submenu->mid = $val->mid;
				$submenu->title = Context::getLang('site_status');
				$submenu->module = 'cympusadmin';
				$menu->submenu[] = $submenu;
			}
		}

		if(count($menu->submenu))
		{
			$manager_menu['cympusadmin'] = $menu;
		}
	}
}
