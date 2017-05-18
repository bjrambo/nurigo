<?php

class couponsms extends ModuleObject
{
	function moduleInstall()
	{
		return new Object();
	}

	function checkUpdate()
	{
		$oDB = DB::getInstance();
		$oModuleModel = getModel('module');

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

		if(!$oDB->isColumnExists("couponsms_list", "discount_type"))
		{
			return true;
		}
		if(!$oDB->isColumnExists("couponsms_list", "discount"))
		{
			return true;
		}
		if(!$oDB->isColumnExists("couponsms_list", "free_delivery"))
		{
			return true;
		}
		if(!$oDB->isColumnExists("couponsms_list", "maximum_count"))
		{
			return true;
		}

		if(!$oDB->isColumnExists("couponsms_list", "condition_type"))
		{
			return true;
		}

		if(!$oDB->isColumnExists("couponsms_list", "price_condition"))
		{
			return true;
		}


		if(!$oDB->isColumnExists('couponsms_use_list', 'sms_success'))
		{
			return true;
		}
		if(!$oDB->isColumnExists('couponsms_use_list', 'use_success'))
		{
			return true;
		}
		if($oDB->isColumnExists('couponsms_use_list', 'success'))
		{
			return true;
		}

		return false;
	}

	function moduleUpdate()
	{
		$oDB = DB::getInstance();

		if(!$oDB->isColumnExists("couponsms_list", "discount_type"))
		{
			$oDB->addColumn("couponsms_list", "discount_type", "varchar", "20");
		}
		if(!$oDB->isColumnExists("couponsms_list", "discount"))
		{
			$oDB->addColumn("couponsms_list", "discount", "number", "50");
		}
		if(!$oDB->isColumnExists("couponsms_list", "free_delivery"))
		{
			$oDB->addColumn("couponsms_list", "free_delivery", "varchar", "10");
		}
		if(!$oDB->isColumnExists("couponsms_list", "maximum_count"))
		{
			$oDB->addColumn("couponsms_list", "maximum_count", "number", "10");
		}

		if(!$oDB->isColumnExists("couponsms_list", "condition_type"))
		{
			$oDB->addColumn("couponsms_list", "condition_type", "varchar", "20");
		}

		if(!$oDB->isColumnExists("couponsms_list", "price_condition"))
		{
			$oDB->addColumn("couponsms_list", "price_condition", "varchar", "20");
		}

		if(!$oDB->isColumnExists("couponsms_use_list", "sms_success"))
		{
			$oDB->addColumn("couponsms_use_list", "sms_success", "varchar", "10");
		}
		if(!$oDB->isColumnExists("couponsms_use_list", "use_success"))
		{
			$oDB->addColumn("couponsms_use_list", "use_success", "varchar", "10");
		}
		if($oDB->isColumnExists("couponsms_use_list", "success"))
		{
			$oDB->dropColumn("couponsms_use_list", "success");
		}


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
