<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  ncartModel
 * @author NURIGO(contact@nurigo.net)
 * @brief  ncartModel
 */
class ncartModel extends ncart
{
	function init() 
	{
		if (!$this->module_info->thumbnail_width) $this->module_info->thumbnail_width = 150;
		if (!$this->module_info->thumbnail_height) $this->module_info->thumbnail_height = 150;
	}


	function getModuleConfig()
	{
		$oModuleModel = &getModel('module');
		$config = $oModuleModel->getModuleConfig('ncart');
		if (!$config->cart_thumbnail_width) $config->cart_thumbnail_width = 100;
		if (!$config->cart_thumbnail_height) $config->cart_thumbnail_height = 100;
		if (!$config->favorite_thumbnail_width) $config->favorite_thumbnail_width = 100;
		if (!$config->favorite_thumbnail_height) $config->favorite_thumbnail_height = 100;
		if (!$config->order_thumbnail_width) $config->order_thumbnail_width = 100;
		if (!$config->order_thumbnail_height) $config->order_thumbnail_height = 100;
		if (!$config->address_input) $config->address_input = 'krzip';
		if (!$config->mileage_method) $config->mileage_method = 'ncart';
		
		$oCurrencyModule = &getModel('currency');
		$currency = $oCurrencyModule->getModuleConfig();
		if (!$currency->currency) $config->currency = 'KRW';
		else	$config->currency = $currency->currency;
		if (!$currency->as_sign) $config->as_sign = 'Y';
		else	$config->as_sign = $currency->as_sign;
		if (!$currency->decimals) $config->decimals = 0;
		else	$config->decimals = $currency->decimals;

		return $config;
	}

/*
	function getItemInfo($item_srl) 
	{
		$config = $this->getModuleConfig();
		$args->item_srl = $item_srl;
		$output = $this->executeQuery('getItemInfo', $args);
		if (!$output->toBool()) return;
		$item = new nproductItem($output->data, $config->currency, $config->as_sign, $config->decimals);
		return $item;
	}

	function getItemByCode($item_code) {
		$config = $this->getModuleConfig();
		$args->item_code = $item_code;
		$output = $this->executeQuery('getItemInfo', $args);
		if (!$output->toBool()) return;
		$item = new nproductItem($output->data, $config->currency, $config->as_sign, $config->decimals);
		return $item;
	}

	function getItemByDocumentSrl($document_srl)
	{
		$config = $this->getModuleConfig();
		$args->document_srl = $document_srl;
		$output = $this->executeQuery('getItemInfo', $args);
		if (!$output->toBool()) return;
		$item = new nproductItem($output->data, $config->currency, $config->as_sign, $config->decimals);
		return $item;
	}
 */

	function getCartItem($cart_srl) 
	{
		$config = $this->getModuleConfig();
		$args->cart_srl = $cart_srl;
		$output = executeQuery('ncart.getCartItem', $args);
		if (!$output->toBool()) return;
		$item = new nproductItem($output->data, $config->currency, $config->as_sign, $config->decimals);
		return $item;
	}

	/**
	 * @brief 그룹할인이 있으면 그룹할인으로 적용하고 그룹할인이 없을 때는 상품별 할인 적용.
	 */
	function discountItems(&$item_list, $group_list=array(), $width=50, $height=50)
	{
		$oNproductModel = &getModel('nproduct');
		return $oNproductModel->discountItems($item_list, $group_list, $width, $height);
	}

	function getGuestCartInfo($non_key, $cartnos=null, $width=null, $height=null)
	{
		// non group list
		$group_list = array();

		// default values
		if(!$width) $width = 80;
		if(!$height) $height = 80;

		// cart items
		$args->non_key = $non_key;
		$args->cartnos = $cartnos;
		$output= executeQueryArray('ncart.getNonCartItems', $args);
		if(!$output->toBool()) return $output;

		$item_list = $output->data;
		if(!is_array($item_list)) $item_list = array();

		return $this->discountItems($item_list, $group_list, $width, $height);
	}

	function getMemberCartInfo($member_srl, $cartnos=null, $width=null, $height=null)
	{
		$oMemberModel = &getModel('member');

		// get group list
		$group_list = $oMemberModel->getMemberGroups($member_srl);

		// default values
		if (!$width) $width = 80;
		if (!$height) $height = 80;

		// cart items
		$args->cartnos = $cartnos;
		$args->member_srl = $member_srl;
		$output = executeQueryArray('ncart.getCartItems', $args);
		if (!$output->toBool()) return $output;

		$item_list = $output->data;
		if (!is_array($item_list)) $item_list = array();

		/*
		foreach($item_list as $key => $item)
		{
			$item = new nproductItem($item);
			$item_list[$key] = $item;
		}
		 */

		return $this->discountItems($item_list, $group_list, $width, $height);
	}

	/**
	 * @brief 회원 혹은 비회원 카트정보를 알아서 구해서 돌려준다. 로그인 했을 때 비회원 카트에 남아 있던 상품을 회원 카트로 옮겨 담아준다.
	 */
	function getCartInfo($cartnos=null, $width=null, $height=null)
	{
		$oNcartController = &getController('ncart');

		$logged_info = Context::get('logged_info');
		$non_key = $_COOKIE['non_key'];
		$cart_info = null;

		if (!$logged_info)
		{
			// 로그인 안되어 있을 때 비회원카트 정보를 가져옴
			$cart_info = $this->getGuestCartInfo($non_key, $cartnos, $width, $height);
		}
		else
		{
			// 로그인되고 non_key가 있으면(비회원으로 담은 상품이 있으면) 회원 카트로 이동
			if ($non_key) $oNcartController->updateGuestCartItems($logged_info->member_srl, $non_key);

			// 로그인 되어 있을 때 회원 카트정보 가져옴
			$cart_info = $this->getMemberCartInfo($logged_info->member_srl, $cartnos, $width, $height);
			debugPrint('$cart_info');
			debugPrint($cart_info);
		}

		return $cart_info;
	}

	function getCartItems($member_srl, $cartnos=null, $width=null, $height=null) 
	{
		$oFileModel = &getModel('file');
		$oMemberModel = &getModel('member');
		$oModuleModel = &getModel('module');

		// my group list
		$group_list = $oMemberModel->getMemberGroups($member_srl);


		// default values
		if (!$width) $width = 80;
		if (!$height) $height = 80;

		// cart items
		$args->member_srl = $member_srl;
		$args->module_srl = $module_srl;
		$args->cartnos = $cartnos;
		$output= executeQueryArray('ncart.getCartItems', $args);
		if (!$output->toBool()) return $output;
		$item_list = $output->data;
		if (!is_array($item_list)) $item_list = array();
		return $this->discountItems($item_list, $group_list, $width,$height);
	}

/*
	function getCartItems($member_srl, $cartnos=null, $width=null, $height=null)
	{

		$logged_info = Context::get('logged_info');
		$oFileModel = &getModel('file');
		$oMemberModel = &getModel('member');
		$oModuleModel = &getModel('module');
		$module_srl = Context::get('module_srl');

		$config = $this->getModuleConfig();
		// my group list
		$group_list = $oMemberModel->getMemberGroups($member_srl);

		// default values
		if (!$width) $width = 80;
		if (!$height) $height = 80;

		// cart items
		$args->module_srl = $module_srl;
		$args->cartnos = $cartnos;

		if(!$member_srl && $_COOKIE['non_key']  ) 
		{
			$args->non_key = $_COOKIE['non_key'];
			$output= $this->executeQueryArray('getNonCartItems', $args);
		}
		else if($member_srl)
		{
			if($_COOKIE['non_key'])
			{
				$args->member_srl = $member_srl;
				$args->non_key = $_COOKIE['non_key'];
				$args->del_non_key = '';
				$output = $this->executeQuery('updateNonCartItem', $args);
				
				if (!$output->toBool()) return $output;
			}
			$args->member_srl = $member_srl;
			$output= $this->executeQuery('getCartItems', $args);
		}
		else
		{
			$output = new Object();
			$output->data = array();
		}
		if (!$output->toBool()) return $output;

		$item_list = $output->data;
		if (!is_array($item_list)) $item_list = array();

		return $this->discountItems($item_list, $group_list, $width, $height);
	}
*/

	function getOrderTitle(&$item_list)
	{
		$item_count = count($item_list);

		$max_unit_price = -1;
		$title = '';
		foreach ($item_list as $key=>$val) {
			$sum = $val->price * $val->quantity;
			if ($val->price > $max_unit_price) {
				$max_unit_price = $val->price;
				$title = $val->item_name;
			}
		}
		if ($item_count > 1) $title = sprintf(Context::getLang('order_title'), $title, $item_count - 1);
		return $title;
	}

	/*
	function getItemSrlsByCartSrls($module_srl, $member_srl, $cartnos) 
	{
		$item_srls = array();
		
		if(is_array($cartnos)) $cartnos=implode(',',$cartnos);
		$args->member_srl = $member_srl;
		$args->module_srl = $module_srl;
		$args->cartnos = $cartnos;
		$output= $this->executeQueryArray('insertMileageHistory', $args);
		if (!$output->toBool()) return $output;
		$item_list = $output->data;

		if (!is_array($item_list)) $item_list = array();
		foreach ($item_list as $key=>$val) {
			$item_srls[] = $val->item_srl;
		}
		return $item_srls;
	}
	 */

	function getCombineItemExtras(&$item_info) 
	{
		$extra_vars = unserialize($item_info->extra_vars);
		$extend_form_list = $this->getItemExtraFormList($item_info->module_srl);
		if(!$extend_form_list) return;
		// Member info is open only to an administrator and him/herself when is_private is true. 
		$logged_info = Context::get('logged_info');

		foreach($extend_form_list as $srl => $item) {
			$column_name = $item->column_name;
			$value = $extra_vars->{$column_name};

/*
			if($logged_info->is_admin != 'Y' && $extra_vars->{'open_'.$column_name}!='Y') {
				$extend_form_list[$srl]->is_private = true;
				continue;
			}
*/
			// Change values depening on the type of extend form
			switch($item->column_type) {
				case 'checkbox' :
						if($value && !is_array($value)) $value = array($value);
					break;
				case 'text' :
				case 'homepage' :
				case 'email_address' :
				case 'tel' :
				case 'textarea' :
				case 'select' :
				case 'kr_zip' :
					break;
			}

			$extend_form_list[$srl]->value = $value;

			if($extra_vars->{'open_'.$column_name}=='Y') $extend_form_list[$srl]->is_opened = true;
			else $extend_form_list[$srl]->is_opened = false;
		}
		return $extend_form_list;
	}


	function getOrderInfo($order_srl, $module=null) 
	{
		$config = $this->getModuleConfig();

		// order info.
		$args->order_srl = $order_srl;
		$args->module = $module;
		$output = executeQuery('ncart.getOrderInfo', $args);
		$order_info = $output->data;

		// ordered items
		$args->order_srl = $order_srl;
		$output = executeQueryArray('ncart.getOrderItems', $args);
		$item_list = $output->data;
		if(!is_array($item_list)) $item_list = array($item_list);
		foreach ($item_list as $key=>$val)
		{
			$item = new nproductItem($val, $config->currency, $config->as_sign, $config->decimals);
			if ($item->option_srl)
			{
				$item->price += ($item->option_price);
			}
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

	function getFavoriteItems($member_srl, $width=null, $height=null) {
		$oFileModel = &getModel('file');
		$oMemberModel = &getModel('member');

		if (!$width) $width = $this->module_info->thumbnail_width;
		if (!$height) $height = $this->module_info->thumbnail_height;

		// my group list
		$group_list = $oMemberModel->getMemberGroups($member_srl);


		// favorite items
		$args->member_srl = $member_srl;
		$output = executeQueryArray('ncart.getFavoriteItems', $args);
		if (!$output->toBool()) return $output;
		$favorite_items = $output->data;
		if (!is_array($favorite_items)) $favorite_items = array();
		$retobj = $this->discountItems($favorite_items, $group_list, $width, $height);

		return $favorite_items;
	}


	function getNodeRouteLength($node_route) 
	{
		$arr = preg_split('/\./', $node_route);
		return count($arr)-1;
	}

	function getNodeRoute($node_route, $length) 
	{
		$route = '';
		$arr = preg_split('/\./', $node_route);
		for ($i = 0; $i < (count($arr)-1); $i++) {
			$route = $route . $arr[$i] . '.';
			if ($i >= $length) break;
		}
		return $route;
	}


	function getNcartCartItems() 
	{
		if(Context::get('image_width') && Context::get('image_height'))
		{
			$image_width = Context::get('image_width');
			$image_height = context::get('image_height');
		}
		else
		{
			$image_width = 50;
			$image_height = 50;
		}

		$cart_info = $this->getCartInfo(null, $image_width, $image_height);
		$this->add('data', $cart_info->item_list);
		$this->add('item_count', count($cart_info->item_list));

		// mileage
		$mileage = 0;
		$logged_info = Context::get('logged_info');
		if ($logged_info) $mileage = $this->getMileage($logged_info->member_srl);
		$this->add('mileage', $mileage);

	}

	function getNcartFavoriteItems() 
	{
		if(Context::get('image_width') && Context::get('image_height'))
		{
			$image_width = Context::get('image_width');
			$image_height = context::get('image_height');
		}
		else
		{
			$image_width = 50;
			$image_height = 50;
		}

		$logged_info = Context::get('logged_info');
		if (!$logged_info) return new Object(-1, 'msg_invalid_request');
		$member_srl = $logged_info->member_srl;
		$item_list = $this->getFavoriteItems($member_srl, $image_width, $image_height);

		$this->add('data', $item_list);
		$this->add('item_count', count($item_list));

		// mileage
		$mileage = $this->getMileage($member_srl);
		$this->add('mileage', $mileage);
	}

	function getReviewCount() 
	{
		return 1;
	}

	function getReviews(&$item_info) 
	{
		if(!$this->getReviewCount()) return;
		//if(!$this->isGranted() && $this->isSecret()) return;
		// cpage is a number of comment pages
		$cpage = Context::get('cpage');
		// Get a list of comments
		$oReviewModel = &getModel('store_review');
		$output = $oReviewModel->getReviewList($item_info->module_srl, $item_info->item_srl, $cpage, $is_admin);
		if(!$output->toBool() || !count($output->data)) return;
		// Create commentItem object from a comment list
		// If admin priviledge is granted on parent posts, you can read its child posts.
		$accessible = array();
		foreach($output->data as $key => $val) {
			$oStoreReviewItem = new store_reviewItem();
			$oStoreReviewItem->setAttribute($val);
			// If permission is granted to the post, you can access it temporarily
			if($oStoreReviewItem->isGranted()) $accessible[$val->item_srl] = true;
			// If the comment is set to private and it belongs child post, it is allowable to read the comment for who has a admin privilege on its parent post
			if($val->parent_srl>0 && $val->is_secret == 'Y' && !$oStoreReviewItem->isAccessible() && $accessible[$val->parent_srl]===true) {
				$oStoreReviewItem->setAccessible();
			}
			$review_list[$val->review_srl] = $oStoreReviewItem;
		}
		// Variable setting to be displayed on the skin
		Context::set('cpage', $output->page_navigation->cur_page);
		if($output->total_page>1) $this->review_page_navigation = $output->page_navigation;

		return $review_list;
	}
	function getNcartAddressInfo() {
		$logged_info = Context::get('logged_info');
		if (!$logged_info) return new Object(-1, 'msg_invalid_request');

		$args->member_srl = $logged_info->member_srl;
		$args->address_srl = Context::get('address_srl');
		$output = executeQuery('ncart.getAddressInfo', $args);
		if (!$output->toBool()) return $output;

		$output->data->address = unserialize($output->data->address);

		/*
		$address = unserialize($output->data->address);
		$output->data->address = $address[0];
		$output->data->address2 = $address[1];
		 */
		$this->add('data', $output->data);
	}


	function getDefaultAddress($member_srl) {

		$args->member_srl = $member_srl;
		$args->default = 'Y';
		$output = executeQuery('ncart.getAddressList', $args);
		if (!$output->toBool()) return $output;
		$default_address = $output->data;
		if (is_array($default_address)) $default_address = $default_address[0];
		if ($default_address) return $default_address;

		$args->member_srl = $member_srl;
		$args->default = 'N';
		$output = executeQuery('ncart.getAddressList', $args);
		if (!$output->toBool()) return $output;
		$default_address = $output->data;
		if (is_array($default_address)) $default_address = $default_address[0];
		return $default_address;
	}

	function getMileage($member_srl) 
	{
		$oNmileageModel = &getModel('nmileage');

		return $oNmileageModel->getMileage($member_srl);
	}

	function getGroupDiscount(&$item_info, $group_list) 
	{
		$args->item_srl = $item_info->item_srl;
		$output = executeQueryArray('ncart.getGroupDiscount', $args);
		if (!$output->toBool()) return $output;
		$group_discount = $output->data;

		if (!is_array($group_discount)) $group_discount = array();
		$discounted_price = 0;
		$discount_info = "";
		foreach ($group_discount as $key => $val) {
			if (array_key_exists($val->group_srl, $group_list)) {
				$discount_info = $group_list[$val->group_srl];
				if ($val->opt=='2') {
					$discounted_price = $item_info->price * ((100 - $val->price) / 100);
					$discount_info .= ' ' . $val->price . '% 할인';
				} else {
					$discounted_price = $val->price;
					$discount_info .= ' 할인';
				}
				if ($discounted_price > 0) break;
			}
		}
		if (!$discounted_price) $discounted_price = $item_info->price;

		$output = new Object();
		$output->discount_amount = $item_info->price - $discounted_price;
		$output->discounted_price = $discounted_price;
		$output->discount_info = $discount_info;
		return $output;
	}

	/*
	 * ncart_digital
	 */
	function getDeliveryCompanies() 
	{
		return $this->delivery_companies;

	}


	function getDiscount(&$item_info)
	{
		$output = new Object();
		$output->discount_amount = $item_info->discount_amount;
		$output->discounted_price = $item_info->price - $item_info->discount_amount;
		$output->discount_info = $item_info->discount_info;
		return $output;
	}

	function getExtraVars($module_srl)
	{
		$args->module_srl = $module_srl;
		$output = $this->executeQueryArray('getItemExtraList', $args);
		if (!$output->toBool()) return $output;
		$extra_list = $output->data;
		$extra_args = new StdClass();
		if ($extra_list)
		{
			foreach ($extra_list as $key=>$val)
			{
				$extra_args->{$val->column_name} = Context::get($val->column_name);
			}
		}
		return $extra_args;
	}


	function getKey()
	{
		$randval = rand(100000, 999999);
		$usec = explode(" ", microtime());
		$str_usec = str_replace(".", "", strval($usec[0]));
		$str_usec = substr($str_usec, 0, 6);
		return date("YmdHis") . $str_usec . $randval;
	}
	
	function getDefaultListConfig($module_srl) 
	{
		$extra_vars = array();

		// 체크박스, 이미지, 상품명, 수량, 금액, 주문 추가
		$virtual_vars = array('checkbox', 'image', 'title', 'stock', 'amount', 'cart_buttons', 'sales_count', 'download_count');
		foreach($virtual_vars as $key) {
			$extra_vars[$key] = new ExtraItem($module_srl, -1, Context::getLang($key), $key, 'N', 'N', 'N', null);
		}

		// 확장변수 정리
		$form_list = $this->getItemExtraFormList($module_srl);
		if(count($form_list))
		{
			$idx = 1;
			foreach ($form_list as $key => $val)
			{
				$extra_vars[$val->column_name] = new ExtraItem($module_srl, $idx, $val->column_title, $val->column_name, 'N', 'N', 'N', null);
				$idx++;
			}
		}

		return $extra_vars;

	}

	function getListConfig($module_srl) 
	{
		$oModuleModel = &getModel('module');
		$oDocumentModel = &getModel('document');

		$extra_vars = array();

		// 저장된 목록 설정값을 구하고 없으면 빈값을 줌.
		$list_config = $oModuleModel->getModulePartConfig('ncart', $module_srl);
		if(!$list_config || !count($list_config)) $list_config = array('checkbox', 'image', 'title', 'stock', 'amount', 'cart_buttons', 'sales_count', 'download_count');

		// 확장변수 정리
		$form_list = $this->getItemExtraFormList($module_srl);
		if(count($form_list))
		{
			$idx = 1;
			foreach ($form_list as $key => $val)
			{
				$extra_vars[$val->column_name] = new ExtraItem($module_srl, $idx, $val->column_title, $val->column_name, 'N', 'N', 'N', null);
				$idx++;
			}
		}

		foreach($list_config as $key) 
		{
			if(array_key_exists($key, $extra_vars))
			{
				$output[$key] = $extra_vars[$key];
			}
			else
			{
				$output[$key] = new ExtraItem($module_srl, -1, Context::getLang($key), $key, 'N', 'N', 'N', null);
			}
		}

		return $output;
	}

	function getPurchasedItem($member_srl, $cart_srl)
	{
		$args->member_srl = $member_srl;
		$args->cart_srl = $cart_srl;
		$output = executeQuery('ncart.getPurchasedItem', $args);
		if (!$output->toBool()) return;
		return $output->data;
	}

	/**
	 * @brief get fieldset list
	 */
	function getFieldSetList($module_srl)
	{
		$args->module_srl = $module_srl;
		$output = executeQueryArray('ncart.getFieldsetList', $args);
		if(!$output->toBool()) return $output;
		$fieldset_list = $output->data;

		foreach($fieldset_list as $key=>$val)
		{
			$args->fieldset_srl = $val->fieldset_srl;
			$output = executeQueryArray('ncart.getFieldList', $args);
			if(!$output->toBool()) return $output;
			$fields = $output->data;
			foreach($fields as $key2=>$val2)
			{
				$input_html = $this->getInputHtml($fields[$key2]);
				$fields[$key2]->html = $input_html;
				Context::setLang($val2->column_name, $val2->column_title);
			}
			$fieldset_list[$key]->fields = $fields;
		}

		return $fieldset_list;
	}


	function getCombineOrderForm(&$item_info) 
	{
		$extra_vars = unserialize($item_info->extra_vars);
		$extend_form_list = $this->getOrderFormList($item_info->module_srl);
		if(!$extend_form_list) return;
		// Member info is open only to an administrator and him/herself when is_private is true. 
		$logged_info = Context::get('logged_info');

		foreach($extend_form_list as $srl => $item) {
			$column_name = $item->column_name;
			$value = $extra_vars->{$column_name};

/*
			if($logged_info->is_admin != 'Y' && $extra_vars->{'open_'.$column_name}!='Y') {
				$extend_form_list[$srl]->is_private = true;
				continue;
			}
*/
			// Change values depening on the type of extend form
			switch($item->column_type) {
				case 'checkbox' :
						if($value && !is_array($value)) $value = array($value);
					break;
				case 'text' :
				case 'homepage' :
				case 'email_address' :
				case 'tel' :
				case 'textarea' :
				case 'select' :
				case 'kr_zip' :
					break;
			}

			$extend_form_list[$srl]->value = $value;

			if($extra_vars->{'open_'.$column_name}=='Y') $extend_form_list[$srl]->is_opened = true;
			else $extend_form_list[$srl]->is_opened = false;
		}
		return $extend_form_list;
	}

	function getOrderFormList($module_srl, $filter_response = false) 
	{
		global $lang;
		// Set to ignore if a super administrator.
		$logged_info = Context::get('logged_info');

		if(!$this->join_form_list) {
			// Argument setting to sort list_order column
			$args->sort_index = "list_order";
			$args->module_srl = $module_srl;
			$output = $this->executeQueryArray('getItemExtraList', $args);
			// NULL if output data deosn't exist
			$join_form_list = $output->data;
			if(!$join_form_list) return NULL;
			// Need to unserialize because serialized array is inserted into DB in case of default_value
			if(!is_array($join_form_list)) $join_form_list = array($join_form_list);
			$join_form_count = count($join_form_list);
			for($i=0;$i<$join_form_count;$i++) {
				$join_form_list[$i]->column_name = strtolower($join_form_list[$i]->column_name);

				$extra_srl = $join_form_list[$i]->extra_srl;
				$column_type = $join_form_list[$i]->column_type;
				$column_name = $join_form_list[$i]->column_name;
				$column_title = $join_form_list[$i]->column_title;
				$default_value = $join_form_list[$i]->default_value;
				// Add language variable
				$lang->extend_vars[$column_name] = $column_title;
				// unserialize if the data type if checkbox, select and so on
				if(in_array($column_type, array('checkbox','select','radio'))) {
					$join_form_list[$i]->default_value = unserialize($default_value);
					if(!$join_form_list[$i]->default_value[0]) $join_form_list[$i]->default_value = '';
				} else {
					$join_form_list[$i]->default_value = '';
				}

				$list[$extra_srl] = $join_form_list[$i];
			}
			$this->join_form_list = $list;
		}
		// Get object style if the filter_response is true
		if($filter_response && count($this->join_form_list)) {

			foreach($this->join_form_list as $key => $val) {
				if($val->is_active != 'Y') continue;
				unset($obj);
				$obj->type = $val->column_type;
				$obj->name = $val->column_name;
				$obj->lang = $val->column_title;
				if($logged_info->is_admin != 'Y') $obj->required = $val->required=='Y'?true:false;
				else $obj->required = false;
				$filter_output[] = $obj;

				unset($open_obj);
				$open_obj->name = 'open_'.$val->column_name;
				$open_obj->required = false;
				$filter_output[] = $open_obj;

			}
			return $filter_output;

		}
		// Return the result
		return $this->join_form_list;
	}

	function getInputHtml($formInfo)
	{
		global $lang;

		$inputTag = '';
		$formTag->title = $formInfo->column_title;
		if($formInfo->required=='Y') 
		{
			$formTag->title = $formTag->title.' <em style="color:red">*</em>';
		}
		$formTag->column_name = $formInfo->column_name;
		$formTag->column_title = $formInfo->column_title;

		//$extendForm = $extend_form_list[$formInfo->extra_srl];
		$extendForm = $formInfo;
		$replace = array('column_name' => $extendForm->column_name,
						 'value'		=> $extendForm->value);
		$extentionReplace = array();

		if($extendForm->column_type == 'text' || $extendForm->column_type == 'homepage' || $extendForm->column_type == 'email_address')
		{
			$template = '<input type="text" name="%column_name%" value="%value%" />';
		}
		else if($extendForm->column_type == 'tel')
		{
			$extentionReplace = array('tel_0' => $extendForm->value[0],
									  'tel_1' => $extendForm->value[1],
									  'tel_2' => $extendForm->value[2]);
			$template = '<ul class="tel"><li><input type="text" name="%column_name%[]" value="%tel_0%" size="4" /></li><li class="dash">-</li><li><input type="text" name="%column_name%[]" value="%tel_1%" size="4" /></li><li class="dash">-</li><li><input type="text" name="%column_name%[]" value="%tel_2%" size="4" /></li></ul>';
		}
		else if($extendForm->column_type == 'textarea')
		{
			$template = '<textarea name="%column_name%">%value%</textarea>';
		}
		else if($extendForm->column_type == 'checkbox')
		{
			$template = '';
			if($extendForm->default_value)
			{
				$template = '<ul>';
				$__i = 0;
				$extendForm->default_value = explode("\n", preg_replace("/\r/", '', $extendForm->default_value));
				foreach($extendForm->default_value as $v){
					$checked = '';
					if(is_array($extendForm->value) && in_array($v, $extendForm->value))
					{
						$checked = 'checked="checked"';
					}
					$template .= '<li><input type="checkbox" id="%column_name%'.$__i.'" name="%column_name%[]" value="'.htmlspecialchars($v).'" '.$checked.' /><label for="%column_name%'.$__i.'">'.$v.'</label></li>';
					$__i++;
				}
				$template .= '</ul>';
			}
		}
		else if($extendForm->column_type == 'radio')
		{
			$template = '';
			if($extendForm->default_value)
			{
				$template = '<ul class="radio">%s</ul>';
				$optionTag = array();
				$extendForm->default_value = explode("\n", preg_replace("/\r/", '', $extendForm->default_value));
				$__i = 0;
				foreach($extendForm->default_value as $v){
					if($extendForm->value == $v)
					{
						$checked = 'checked="checked"';
					}
					else $checked = '';
					$optionTag[] = '<li><input type="radio" id="%column_name%'.$__i.'" name="%column_name%[]" value="'.$v.'" '.$checked.' /><label for="%column_name%'.$__i.'">'.$v.'</label></li>';
					$__i++;
				}
				$template = sprintf($template, implode('', $optionTag));
			}
		}
		else if($extendForm->column_type == 'select')
		{
			$template = '<select name="'.$formInfo->column_name.'">%s</select>';
			$optionTag = array();
			if($extendForm->default_value)
			{
				$extendForm->default_value = explode("\n", preg_replace("/\r/", '', $extendForm->default_value));
				foreach($extendForm->default_value as $v){
					if($v == $extendForm->value) 
					{
						$selected = 'selected="selected"';
					}
					else $selected = '';
					$optionTag[] = sprintf('<option value="%s" %s >%s</option>'
											,$v
											,$selected
											,$v);
				}
			}
			$template = sprintf($template, implode('', $optionTag));
		}
		else if($extendForm->column_type == 'date')
		{
			$extentionReplace = array('date' => zdate($extendForm->value, 'Y-m-d'),
									  'cmd_delete' => $lang->cmd_delete);
			$template = '<input type="hidden" name="%column_name%" id="date_%column_name%" value="%value%" /><input type="text" class="inputDate" value="%date%" readonly="readonly" /> <input type="button" value="%cmd_delete%" class="dateRemover" />'."\n".
							'<script type="text/javascript">'."\n".
							'(function($){'."\n".
							'    $(function(){'."\n".
							'        var option = { dateFormat: "yy-mm-dd", changeMonth:true, changeYear:true, gotoCurrent: false,yearRange:\'-100:+10\', onSelect:function(){'."\n".
							'            $(this).prev(\'input[type="hidden"]\').val(this.value.replace(/-/g,""))}'."\n".
							'        };'."\n".
							'        $.extend(option,$.datepicker.regional[\''.Context::getLangType().'\']);'."\n".
							'        $(".inputDate").datepicker(option);'."\n".
							'               $(".dateRemover").click(function(){' . "\n" .
							'                       $(this).siblings("input").val("");' . "\n" .
							'                       return false;' . "\n" .
							'               })' . "\n" .
							'    });'."\n".
							'})(jQuery);'."\n".
							'</script>';
		}
		else if($extendForm->column_type == 'kr_zip')
		{
			$krzipModel = &getModel('krzip');
			if($krzipModel && method_exists($krzipModel , 'getKrzipCodeSearchHtml' ))
			{
				$template = $krzipModel->getKrzipCodeSearchHtml($extendForm->column_name, $extendForm->value);
			}
		}

		$replace = array_merge($extentionReplace, $replace);
		$inputTag = preg_replace('@%(\w+)%@e', '$replace[$1]', $template);

		if($extendForm->description)
			$inputTag .= '<p style="color:#999;">'.htmlspecialchars($extendForm->description).'</p>';

		$formTag->inputTag = $inputTag;

		return $formTag;
	}

	function getOrderFormInputHtml($item_info)
	{
		$extend_form_list = $this->getCombineOrderForm($item_info);

		$args->module_srl = $item_info->module_srl;
		$output = $this->executeQueryArray('getItemExtraList', $args);
		if(!$output->toBool())
		{
			return $output;
		}
		$extra_vars = $output->data;

		$formTags = array();
		if(!$extra_vars) 
		{
			return $formTags;
		}
		foreach ($extra_vars as $no=>$formInfo) {
			unset($formTag);
			$inputTag = '';
			$formTag->title = $formInfo->column_title;
			if($formInfo->required=='Y') 
			{
				$formTag->title = $formTag->title.' <em style="color:red">*</em>';
			}
			$formTag->column_name = $formInfo->column_name;
			$formTag->column_title = $formInfo->column_title;

			$extendForm = $extend_form_list[$formInfo->extra_srl];
			$replace = array('column_name' => $extendForm->column_name,
							 'value'		=> $extendForm->value);
			$extentionReplace = array();

			if($extendForm->column_type == 'text' || $extendForm->column_type == 'homepage' || $extendForm->column_type == 'email_address')
			{
				$template = '<input type="text" name="%column_name%" value="%value%" />';
			}
			else if($extendForm->column_type == 'tel')
			{
				$extentionReplace = array('tel_0' => $extendForm->value[0],
										  'tel_1' => $extendForm->value[1],
										  'tel_2' => $extendForm->value[2]);
				$template = '<input type="text" name="%column_name%[]" value="%tel_0%" size="4" />-<input type="text" name="%column_name%[]" value="%tel_1%" size="4" />-<input type="text" name="%column_name%" value="%tel_2%" size="4" />';
			}
			else if($extendForm->column_type == 'textarea')
			{
				$template = '<textarea name="%column_name%">%value%</textarea>';
			}
			else if($extendForm->column_type == 'checkbox')
			{
				$template = '';
				if($extendForm->default_value)
				{
					$__i = 0;
					foreach($extendForm->default_value as $v){
						$checked = '';
						if(is_array($extendForm->value) && in_array($v, $extendForm->value))
						{
							$checked = 'checked="checked"';
						}
						$template .= '<input type="checkbox" id="%column_name%'.$__i.'" name="%column_name%[]" value="'.htmlspecialchars($v).'" '.$checked.' /><label for="%column_name%'.$__i.'">'.$v.'</label>';
						$__i++;
					}
				}
			}
			else if($extendForm->column_type == 'radio')
			{
				$template = '';
				if($extendForm->default_value)
				{
					$template = '<ul class="radio">%s</ul>';
					$optionTag = array();
					foreach($extendForm->default_value as $v){
						if($extendForm->value == $v)
						{
							$checked = 'checked="checked"';
						}
						else $checked = '';
						$optionTag[] = '<li><input type="radio" name="%column_name%" value="'.$v.'" '.$checked.' />'.$v.'</li>';
					}
					$template = sprintf($template, implode('', $optionTag));
				}
			}
			else if($extendForm->column_type == 'select')
			{
				$template = '<select name="'.$formInfo->column_name.'">%s</select>';
				$optionTag = array();
				if($extendForm->default_value)
				{
					foreach($extendForm->default_value as $v){
						if($v == $extendForm->value) 
						{
							$selected = 'selected="selected"';
						}
						else $selected = '';
						$optionTag[] = sprintf('<option value="%s" %s >%s</option>'
												,$v
												,$selected
												,$v);
					}
				}
				$template = sprintf($template, implode('', $optionTag));
			}
			else if($extendForm->column_type == 'date')
			{
				$extentionReplace = array('date' => zdate($extendForm->value, 'Y-m-d'),
										  'cmd_delete' => $lang->cmd_delete);
				$template = '<input type="hidden" name="%column_name%" id="date_%column_name%" value="%value%" /><input type="text" class="inputDate" value="%date%" readonly="readonly" /> <input type="button" value="%cmd_delete%" class="dateRemover" />'.
							'<script type="text/javascript">'."\n".
							'(function($){'."\n".
							'    $(function(){'."\n".
							'        var option = { dateFormat: "yy-mm-dd", changeMonth:true, changeYear:true, gotoCurrent: false,yearRange:\'-100:+10\', onSelect:function(){'."\n".
							'            $(this).prev(\'input[type="hidden"]\').val(this.value.replace(/-/g,""))}'."\n".
							'        };'."\n".
							'        $.extend(option,$.datepicker.regional[\''.Context::getLangType().'\']);'."\n".
							'        $("#date_%column_name%").datepicker(option);'."\n".
							'               $("#dateRemover_%column_name%").click(function(){' . "\n" .
							'                       $(this).siblings("input").val("");' . "\n" .
							'                       return false;' . "\n" .
							'               })' . "\n" .
							'    });'."\n".
							'})(jQuery);'."\n".
							'</script>';

			}
			else if($extendForm->column_type == 'kr_zip')
			{
				Context::loadFile(array('./modules/member/tpl/js/krzip_search.js', 'body'), true);
				$extentionReplace = array(
								 'msg_kr_address'       => $lang->msg_kr_address,
								 'msg_kr_address_etc'       => $lang->msg_kr_address_etc,
								 'cmd_search'	=> $lang->cmd_search,
								 'cmd_search_again'	=> $lang->cmd_search_again,
								 'addr_0'	=> $extendForm->value[0],
								 'addr_1'	=> $extendForm->value[1],);
				$replace = array_merge($extentionReplace, $replace);
				$template = <<<EOD
				<div class="krZip">
					<div class="a" id="zone_address_search_%column_name%" >
						<label for="krzip_address1_%column_name%">%msg_kr_address%</label><br />
						<input type="text" id="krzip_address1_%column_name%" value="%addr_0%" />
						<button type="button">%cmd_search%</button>
					</div>
					<div class="a" id="zone_address_list_%column_name%" style="display:none">
						<select name="%column_name%[]" id="address_list_%column_name%"><option value="%addr_0%">%addr_0%</select>
						<button type="button">%cmd_search_again%</button>
					</div>
					<div class="a address2">
						<label for="krzip_address2_%column_name%">%msg_kr_address_etc%</label><br />
						<input type="text" name="%column_name%[]" id="krzip_address2_%column_name%" value="%addr_1%" />
					</div>
				</div>
				<script type="text/javascript">jQuery(function($){ $.krzip('%column_name%') });</script>
EOD;
			}

			$replace = array_merge($extentionReplace, $replace);
			$inputTag = preg_replace('@%(\w+)%@e', '$replace[$1]', $template);

			if($extendForm->description)
				$inputTag .= '<p style="color:#999;">'.htmlspecialchars($extendForm->description).'</p>';

			$formTag->inputTag = $inputTag;
			$formTags[] = $formTag;
		}

		return $formTags;
	}

	function getModInstList()
	{
		$output = executeQueryArray('ncart.getModInstList', $args);
		return $output->data;
	}

	/*
	 * iphone 구별
	 */
	function checkBrowser()
	{
		$browser_list = array('MSIE', 'Chrome', 'Firefox', 'iPhone', 'iPad', 'Android', 'PPC', 'Safari', 'none');
		$browser_name = 'none';
		foreach($browser_list as $user_browser)
		{
			if($user_browser == 'none') break;
			if(strpos($_SERVER['HTTP_USER_AGENT'], $user_browser))
			{
				$browser_name = $user_browser;
				break;
			}
		}

		if($browser_name == "iPhone" || $browser_name == "iPad") setCookie('check_browser', "true");
		else setCookie('check_browser','false');
	}

	/**
     * @brief return module name in sitemap
     **/
    function triggerModuleListInSitemap(&$obj)
    {
        array_push($obj, 'ncart');
    }

	/**
	 * @brief set target_act for order detail view.
	 */
	function triggerTransactionList(&$list)
	{
		foreach($list as $key=>$val)
		{
			if($val->target_module == 'ncart') $list[$key]->target_act = 'getNcartAdminOrderDetails';
		}
	}
}
/* End of file ncart.model.php */
/* Location: ./modules/ncart/ncart.model.php */
