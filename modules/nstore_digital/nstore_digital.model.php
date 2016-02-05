<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nstore_digitalModel
 * @author NURIGO(contact@nurigo.net)
 * @brief  nstore_digitalModel
 */
class nstore_digitalModel extends nstore_digital
{

	function getModuleConfig()
	{
		$oModuleModel = &getModel('module');
		$config = $oModuleModel->getModuleConfig('nstore_digital');
		if (!$config->cart_thumbnail_width) $config->cart_thumbnail_width = 100;
		if (!$config->cart_thumbnail_height) $config->cart_thumbnail_height = 100;
		if (!$config->favorite_thumbnail_width) $config->favorite_thumbnail_width = 100;
		if (!$config->favorite_thumbnail_height) $config->favorite_thumbnail_height = 100;
		if (!$config->order_thumbnail_width) $config->order_thumbnail_width = 100;
		if (!$config->order_thumbnail_height) $config->order_thumbnail_height = 100;
		if (!$config->address_input) $config->address_input = 'krzip';

		$oCurrencyModel = &getModel('currency');
		$currency = $oCurrencyModel->getModuleConfig();
		if (!$currency->currency) $config->currency = 'KRW';
		else $config->currency = $currency->currency;
		if (!$currency->as_sign) $config->as_sign = 'Y';
		else $config->as_sign = $currency->as_sign;
		if (!$currency->decimals) $config->decimals = 0;
		else $config->as_sign = $currency->as_sign;

		return $config;
	}

	function getPurchasedItem($member_srl, $cart_srl)
	{
		$args->member_srl = $member_srl;
		$args->cart_srl = $cart_srl;
		$output = executeQuery('nstore_digital.getPurchasedItem', $args);
		if (!$output->toBool()) return;
		return new nproductItem($output->data);
	}

	function getOrderInfo($order_srl) 
	{
		$config = $this->getModuleConfig();

		// order info.
		$args->order_srl = $order_srl;
		$output = executeQuery('nstore_digital.getOrderInfo', $args);
		$order_info = $output->data;

		// ordered items
		$args->order_srl = $order_srl;
		$output = executeQueryArray('nstore_digital.getPurchasedItems', $args);
		$item_list = $output->data;
		if(!is_array($item_list)) $item_list = array($item_list);
		foreach ($item_list as $key=>$val) {
			$item = new nproductItem($val, $config->currency, $config->as_sign, $config->decimals);
			debugprint($item);
			if ($item->option_srl)
			{
				$item->price += ($item->option_price);
			}
			$item_list[$key] = $item;
		}

		$order_info->item_list = $item_list;

		return $order_info;
	}

	function getPeriodInfo($period_srl) 
	{
		$config = $this->getModuleConfig();
		$oMemberModel = &getModel('member');
		$oNproductModel = &getModel('nproduct');

		if(!$period_srl) return new Object(-1, 'no period_srl');

		// order info.
		$args->period_srl = $period_srl;
		$output = executeQuery('nstore_digital.getPeriod', $args);
		$period_info = $output->data;
		$period_info->member_info = $oMemberModel->getMemberinfoByMemberSrl($period_info->member_srl);
		$period_info->item_info = $oNproductModel->getItemInfo($period_info->item_srl);

		return $period_info;
	}

	function getOrdersInfo($order_srls)
	{
		$order_srls_arr = explode(',',$order_srls);
		$order_info_arr = array();
		foreach ($order_srls_arr as $order_srl)
		{
			$order_info_arr[] = $this->getOrderInfo($order_srl);
		}
		return $order_info_arr;
	}

	function getPeriodsInfo($period_srls)
	{
		$period_srls_arr = explode(',',$period_srls);
		$period_info_arr = array();
		foreach ($period_srls_arr as $period_srl)
		{
			$period_info_arr[] = $this->getPeriodInfo($period_srl);
		}
		return $period_info_arr;
	}


	function triggerGetProcModules(&$module_list)
	{
		$modinfo = new StdClass();
		$modinfo->module = $this->module;
		$modinfo->title = '디지털콘텐츠';
		$module_list[$this->module] = $modinfo;
	}

	function getNproductExtraVars()
	{
	}

	function checkNproductExtraName($string)
	{
		if($string == "content_file") return true;
		else return false;
	}

	function getItemInfo($item_srl) 
	{
		$config = $this->getModuleConfig();
		$args->item_srl = $item_srl;
		$output = executeQuery('nstore_digital.getItemInfo', $args);
		if (!$output->toBool()) return;
		$item = new nproductItem($output->data, $config->currency, $config->as_sign, $config->decimals);
		return $item;
	}

	function getPurchaseCount($member_srl, $item_srl)
	{
		$args->member_srl = $member_srl;
		$args->item_srl = $item_srl;
		$args->more_status = 2;
		$args->less_status = 3;
		$output = executeQuery('nstore_digital.getPurchaseCount', $args);
		if(!$output->toBool()) return 0;
		return $output->data->count;
	}

}
/* End of file nstore_digital.model.php */
/* Location: ./modules/nstore_digital/nstore_digital.model.php */
