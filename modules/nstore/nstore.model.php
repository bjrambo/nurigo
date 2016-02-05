<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nstoreModel
 * @author NURIGO(contact@nurigo.net)
 * @brief  nstoreModel
 */
class nstoreModel extends nstore
{
	function getModuleConfig()
	{
		$oModuleModel = &getModel('module');
		$config = $oModuleModel->getModuleConfig('nstore');
		if (!$config->address_input) $config->address_input = 'krzip';

		$oCurrencyModel = &getModel('currency');
		$currency = $oCurrencyModel->getModuleConfig();
		if (!$currency->currency) $config->currency = 'KRW';
		else	$config->currency = $currency->currency;
		if (!$currency->as_sign) $config->as_sign = 'Y';
		else	$config->as_sign = $currency->as_sign;
		if (!$currency->decimals) $config->decimals = 0;
		else	$config->decimals = $currency->decimals;

		return $config;
	}

	function getDefaultAddress($member_srl) {

		$args->member_srl = $member_srl;
		$args->default = 'Y';
		$output = executeQuery('nstore.getAddressList', $args);
		if (!$output->toBool()) return $output;
		$default_address = $output->data;
		if (is_array($default_address)) $default_address = $default_address[0];
		if ($default_address) return $default_address;

		$args->member_srl = $member_srl;
		$args->default = 'N';
		$output = executeQuery('nstore.getAddressList', $args);
		if (!$output->toBool()) return $output;
		$default_address = $output->data;
		if (is_array($default_address)) $default_address = $default_address[0];
		return $default_address;
	}

	function getNstoreEscrowInfo()
	{
		$logged_info = Context::get('logged_info');

		$args->order_srl = Context::get('order_srl');
		$args->member_srl = $logged_info->member_srl;
		$output = executeQuery('nstore.getEscrowInfo', $args);
		$this->add('data', $output->data);
	}

	function triggerGetProcModules(&$module_list)
	{
		$modinfo = new StdClass();
		$modinfo->module = $this->module;
		$modinfo->title = Context::getLang('shoppingmall_product');
		$module_list[$this->module] = $modinfo;
	}

	function getOrderTitle(&$item_list)
	{
		$item_count = 0;
		$max_unit_price = -1;
		$title = '';
		foreach ($item_list as $key=>$val) {
			if($val->module != 'nstore') continue;
			$sum = $val->price * $val->quantity;
			if ($val->price > $max_unit_price) {
				$max_unit_price = $val->price;
				$title = $val->item_name;
			}
			$item_count++;
		}
		if ($item_count > 1) $title = sprintf(Context::getLang('order_title'), $title, ($item_count-1));
		return $title;
	}


	function getOrderInfo($order_srl) 
	{
		$config = $this->getModuleConfig();

		// order info.
		$args->order_srl = $order_srl;
		$output = executeQuery('nstore.getOrderInfo', $args);
		$order_info = $output->data;

		// ordered items
		$args->order_srl = $order_srl;
		$output = executeQueryArray('nstore.getOrderItems', $args);
		$item_list = $output->data;
		if(!is_array($item_list)) $item_list = array($item_list);
		foreach ($item_list as $key=>$val) {
			$item = new nproductItem($val, $config->currency, $config->as_sign, $config->decimals);
			/*
			if ($item->option_srl)
			{
				$item->price += ($item->option_price);
			}
			 */
			$item_list[$key] = $item;
		}

		$order_info->item_list = $item_list;
		return $order_info;
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

	/**
	 * @brief my order items
	 */
	function getMyOrderItems($member_srl, $startdate = NULL, $enddate = NULL)
	{
		if(!$startdate) $startdate = date('Ymd', time() - (60*60*24*30));
		if(!$enddate) $enddate = date('Ymd');

		$args->member_srl = $logged_info->member_srl;
		$args->startdate = $startdate . '000000';
		$args->enddate = $enddate . '235959';
		$output = executeQueryArray('nstore.getOrderItems', $args);
		$item_list = $output->data;
		if(!$item_list) return array();

		$order_list = array();
		foreach($item_list as $key=>$val)
		{
			$item = new nproductItem($val, $config->currency, $config->as_sign, $config->decimals);
			if ($item->option_srl)
			{
				$item->price += ($item->option_price);
			}
			$item_list[$key] = $item;

			if (!isset($order_list[$val->order_srl])) $order_list[$val->order_srl] = array();

			$order_list[$val->order_srl][] = $item;
		}
		return $order_list;
	}

	function getDeliveryCompanies()
	{
		return $this->delivery_companies;
	}

	function getNproductExtraVars()
	{
		if(Context::get('extra_values')) $extra_values = Context::get('extra_values');

		$extra_var->column_type = "checkbox";
		$extra_var->column_name = "item_delivery_free";
		$extra_var->column_title = Context::getLang('cmd_delivery_fee');
		$extra_var->default_value = Context::getLang('freebie');
		$extra_var->required = "N";
		if($extra_values["nstore_extra_1"]) $extra_var->value = $extra_values["nstore_extra_1"];
		$extra_var->description = Context::getLang('about_item_delivery_fee');
		$extra_vars[] = $extra_var;

		unset($extra_var);

		$extra_var->column_type = "text";
		$extra_var->column_name = "stock";
		$extra_var->column_title = Context::getLang('cmd_stock');
		if($extra_values["nstore_extra_2"]) $extra_var->value = $extra_values["nstore_extra_2"];
		$extra_var->description = Context::getLang('about_stock');
		$extra_var->required = "N";
		$extra_vars[] = $extra_var;

		return $extra_vars;

	}

	function checkNproductExtraName($string)
	{
		if($string == "item_delivery_free" || $string == "stock")  return true;
		else return false;
	}

	function triggerMemberMenu($in_args)
	{
		$logged_info = Context::get('logged_info');
		if($logged_info && $logged_info->is_admin=='Y')
		{
			$url = getUrl('','module','nstore','act','dispNstoreAdminPurchaserInfo','member_srl',Context::get('target_srl'));
			$oMemberController = &getController('member');
			$oMemberController->addMemberPopupMenu($url, Context::getLang('cmd_purchaser_info'), '', 'popup');

 			if(Context::get('cympusadmin_menu')) $url = getUrl('','module','cympusadmin','act','dispNstoreAdminOrderManagement','search_key','member_srl','search_value',Context::get('target_srl'));
			else $url = getUrl('','module','admin','act','dispNstoreAdminOrderManagement','search_key','member_srl','search_value',Context::get('target_srl'));
			$oMemberController = &getController('member');
			$oMemberController->addMemberPopupMenu($url, '주문관리');
		}
	}

	/**
	 * @brief return module name in sitemap
	 **/
	function triggerModuleListInSitemap(&$obj)
	{
		array_push($obj, 'nstore');
	}


	function triggerGetManagerMenu(&$manager_menu)
	{
		$oModuleModel = &getModel('module');

		$logged_info = Context::get('logged_info');

		$output = executeQueryArray('nstore.getModInstList');
		if(!$output->toBool()) return $output;

		$list = $output->data;

		$menu = new stdClass();
		$menu->title = Context::getLang('shoppingmall');
		$menu->icon = 'cart';
		$menu->module = 'nstore';
		$menu->submenu = array();

		foreach($list as $key => $val)
		{
			$grant = $oModuleModel->getGrant($val, $logged_info);
			if($grant->manager)
			{
				$submenu1 = new stdClass();
				$submenu1->action = array('dispNstoreAdminOrderManagement');
				$submenu1->mid = $val->mid;
				$submenu1->title = Context::getLang('order_management');
				$submenu1->module = 'nstore';
				$menu->submenu[] = $submenu1;
			}
		}

		if(count($menu->submenu)) $manager_menu['nstore'] = $menu;
	}
}
/* End of file nstore.model.php */
/* Location: ./modules/nstore/nstore.model.php */
