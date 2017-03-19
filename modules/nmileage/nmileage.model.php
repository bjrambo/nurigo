<?php

/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nmileageModel
 * @author NURIGO(contact@nurigo.net)
 * @brief  nmileageModel
 */
class nmileageModel extends nmileage
{
	function init()
	{
		if(!$this->module_info->thumbnail_width)
		{
			$this->module_info->thumbnail_width = 150;
		}
		if(!$this->module_info->thumbnail_height)
		{
			$this->module_info->thumbnail_height = 150;
		}
	}

	function getModuleConfig()
	{
		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('nmileage');
		if(!$config)
		{
			$config = new stdClass();
		}
		if(!$config->mileage_method)
		{
			$config->mileage_method = 'nmileage';
		}
		return $config;
	}


	function getMileage($member_srl)
	{
		$config = $this->getModuleConfig();
		switch($config->mileage_method)
		{
			case 'nmileage':
				$args = new stdClass();
				$args->member_srl = $member_srl;
				$output = executeQuery('nmileage.getMileageInfo', $args);
				if(!$output->toBool() || !$output->data)
				{
					return 0;
				}
				return $output->data->mileage;
			case 'point':
				$oPointModel = getModel('point');
				$point = $oPointModel->getPoint($member_srl, TRUE);
				return $point;
		}
		return 0;
	}

	function getMileageInfo($member_srl)
	{
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$output = executeQuery('nmileage.getMileageInfo', $args);
		if(!$output->toBool())
		{
			return $output;
		}
		if(!$output->data)
		{
			return new Object(-2, 'No mileage record');
		}
		$output->mileage = $output->data->mileage;
		return $output;
	}

	function getModInstList()
	{
		$output = executeQueryArray('nmileage.getModInstList', new stdClass());
		return $output->data;
	}

	/**
	 * @brief return module name in sitemap
	 **/
	function triggerModuleListInSitemap(&$obj)
	{
		array_push($obj, 'nmileage');
	}

	function getMileageByProduct()
	{
		$price = (int)Context::get('price');

		$oNcartModel = getModel('ncart');
		$config = $oNcartModel->getModuleConfig();
		$mileage = round($price * ((float)$config->mileage_percent / 100));
		$this->add('price', $price);
		$this->add('mileage', $mileage);
	}
}
/* End of file nmileage.model.php */
/* Location: ./modules/nmileage/nmileage.model.php */
