<?php

class couponsms extends ModuleObject
{
	function moduleInstall()
	{
		return new Object();
	}

	function checkUpdate()
	{
		$config = getModel('couponsms')->getConfig();

		$member_config = getModel('member')->getMemberConfig();
		$variable_name = array();
		foreach($member_config->signupForm as $val)
		{
			if($val->type == 'tel')
			{
				$variable_name = $val->name;
			}
		}
		if(!$config->variable_name && count($variable_name))
		{
			return true;
		}
		return false;
	}

	function moduleUpdate()
	{
		$oModuleController = getController('module');

		$config = getModel('couponsms')->getConfig();
		if(!$config)
		{
			$config = new stdClass();
		}

		if(!$config->variable_name)
		{
			$member_config = getModel('member')->getMemberConfig();
			$variable_name = array();
			foreach($member_config->signupForm as $value)
			{
				if($value->type == 'tel')
				{
					$variable_name[] = $value->name;
				}
			}

			if(count($variable_name) === 1)
			{
				foreach($variable_name as $item)
				{
					$config->variable_name = $item;
				}
			}
			$output = $oModuleController->insertModuleConfig('couponsms', $config);
			if(!$output->toBool())
			{
				return new Object();
			}
		}
		return new Object();
	}
}
