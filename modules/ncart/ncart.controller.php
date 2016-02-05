<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  ncartController
 * @author NURIGO(contact@nurigo.net)
 * @brief  ncartController
 */
class ncartController extends ncart
{
	function keygen()
	{
		$randval = rand(100000, 999999);
		$usec = explode(" ", microtime());
		$str_usec = str_replace(".", "", strval($usec[0]));
		$str_usec = substr($str_usec, 0, 6);
		return date("YmdHis") . $str_usec . $randval;
	}

	function addGroups($member_srl, $group_srl_list) {
		$oMemberModel = &getModel('member');
		$oMemberController = &getController('member');

		$groups = $oMemberModel->getMemberGroups($member_srl);
		foreach ($group_srl_list as $group_srl) {
			if (!in_array($group_srl, array_keys($groups))) {
				$oMemberController->addMemberToGroup($member_srl, $group_srl);
			}
		}
	}

	function moveNodeToNext($node_id, $parent_id, $next_id) 
	{
		$logged_info = Context::get('logged_info');
		if (!$logged_info) return;

		$args->node_id = $next_id;
		$output = $this->executeQuery('getCategoryInfo', $args);
		if (!$output->toBool()) return $output;
		$next_node = $output->data;
		unset($args);

		// plus next siblings
		$args->node_route = $next_node->node_route;
		$args->list_order = $next_node->list_order;
		$output = $this->executeQuery('updateCategoryOrder', $args);
		if (!$output->toBool()) return $output;

		// update myself
		$list_order = $next_node->list_order;
		$args->node_id = $node_id;
		$args->list_order = $list_order;
		$output = $this->executeQuery('updateCategoryNode', $args);
		if (!$output->toBool()) return $output;
	}
	function moveNodeToPrev($node_id, $parent_id, $prev_id) {
		$logged_info = Context::get('logged_info');
		if (!$logged_info) return;

		$args->node_id = $prev_id;
		$output = $this->executeQuery('getCategoryInfo', $args);
		if (!$output->toBool()) return $output;
		$prev_node = $output->data;
		unset($args);

		// update myself
		$list_order = $prev_node->list_order+1;
		$args->node_id = $node_id;
		$args->list_order = $list_order;
		$output = $this->executeQuery('updateCategoryNode', $args);
		if (!$output->toBool()) return $output;
	}

	function procNcartMoveCategory() 
	{
		$logged_info = Context::get('logged_info');
		if (!$logged_info) return new Object(-1, 'msg_log_required');

		$parent_id = Context::get('parent_id');
		$node_id = Context::get('node_id');
		$target_id = Context::get('target_id');
		$position = Context::get('position');

		$this->moveNode($node_id, $parent_id);

		if ($position=='next') {
			$output = $this->moveNodeToNext($node_id, $parent_id, $target_id);
			if (!$output->toBool()) return $output;
		}
		if ($position=='prev') {
			$output = $this->moveNodeToPrev($node_id, $parent_id, $target_id);
			if (!$output->toBool()) return $output;
		}

	}

	/**
	 * @brief 콤마로 분리된 문자열을 array타입으로 리턴
	 */
	function getArrCommaSrls($key)
	{
		$srls = Context::get($key);

		// explode 함수는 $srls값이 "" 이면 { 0:"" } 을 돌려줘서 요소가 1개가 있는 것으로 처리되므로 문제가 되므로,
		// $srls이 빈문자열일 때 explode로 처리하지 않고 array()로 할당해 준다.
		if ($srls)
		{
			$srls = explode(',',$srls);
		}
		else
		{
			$srls = array();
		}

		return $srls;
	}
	function getKey()
	{
		$randval = rand(100000, 999999);
		$usec = explode(" ", microtime());
		$str_usec = str_replace(".", "", strval($usec[0]));
		$str_usec = substr($str_usec, 0, 6);
		return date("YmdHis") . $str_usec . $randval;
	}

	function addItems(&$in_args)
	{
		$oNcartModel = &getModel('ncart');
		$oModuleModel = &getModel('module');
		$config = $oNcartModel->getModuleConfig();
		$logged_info = Context::get('logged_info');

		$cart_srl = getNextSequence();
		$args->cart_srl = $cart_srl;
		$args->module = $in_args->module;
		$args->item_srl = $in_args->item_srl;
		$args->item_code = $in_args->item_code;
		$args->item_name = $in_args->item_name;
		$args->document_srl = $in_args->document_srl;
		$args->file_srl = $in_args->file_srl;
		$args->thumb_file_srl = $in_args->thumb_file_srl;
		$args->member_srl = $in_args->member_srl;
		if(!$args->member_srl && $logged_info) $args->member_srl = $logged_info->member_srl;
		$args->module_srl = $in_args->module_srl;
		$args->quantity = $in_args->quantity;
		$args->price = $in_args->price;
		$args->taxfree = $in_args->taxfree;
		$args->option_srl = $in_args->option_srl;
		$args->option_price = $in_args->option_price;
		$args->option_title = $in_args->option_title;
		$args->discount_amount = $in_args->discount_amount;
		$args->discount_info = $in_args->discount_info;
		$args->discounted_price = $in_args->discounted_price;
		if(!$logged_info)
		{
			if(!$_COOKIE['non_key'])
			{
				$args->non_key = $this->getKey(); 
				setCookie('non_key', $args->non_key);
			}
			else $args->non_key = $_COOKIE['non_key']; 
		}

		//$args->non_key = $in_args->non_key;
		$output = executeQuery('ncart.insertCartItem', $args);
		if (!$output->toBool()) return $output;
		unset($args);

		$retobj = new Object();
		$retobj->add('cart_srl', $cart_srl);
		return $retobj;
	}

	function addItemsToFavorites(&$in_args) 
	{
			$output = executeQuery('ncart.getFavoriteItemCount', $in_args);
			if(!$output->toBool()) return $output;
			if($output->data && $output->data->count) return new Object(-1,'msg_duplicated_favorite_item');

			$output = executeQuery('ncart.insertFavoriteItem', $in_args);
			if (!$output->toBool()) return $output;

			return new Object();
	}



	function giveMileage($member_srl, $item_srl, $review_srl, $amount)
	{
		$args->member_srl = $member_srl;
		$args->item_srl = $item_srl;
		$item_list = $this->executeQuery('getNonReviewedPurchasedItems', $args);
		if ($item_list->toBool() && count($item_list->data))
		{
			$item = $item_list->data[0];
			$args->cart_srl = $item->cart_srl;
			$args->review_srl = $review_srl;
			$output = $this->executeQuery('updateReviewSrl', $args);
			if (!$output->toBool()) return $output;

			$title = '상품평 등록';
			$oNmileageController = &getController('nmileage');
			$oNmileageController->plusMileage($member_srl, $amount, $title, $item->order_srl);
		}
	}

	function updateSalesCount($item_srl, $quantity) 
	{
		if (!$item_srl) return;
		$args->item_srl = $item_srl;
		for ($i = 0; $i < $quantity; $i++)
		{
			$this->executeQuery('updateSalesCount', $args);
		}
	}

	function procNcartDeleteCart() 
	{
		$cart_srls = Context::get('cart_srls');
		$cart_srls = explode(',', $cart_srls);
		foreach ($cart_srls as $val) 
		{
			if (!$val) continue;
			$args->cart_srl = $val;
			$output = executeQuery('ncart.deleteCart', $args);
			if (!$output->toBool()) return $output;
		}
		$this->setMessage('success_deleted');
	}

	function procNcartDeleteFavoriteItems() {
		$item_srls = Context::get('item_srls');
		$item_srls = explode(',', $item_srls);
		foreach ($item_srls as $val) {
			if (!$val && !$val == 0) continue;
			$args->item_srl = $val;
			$output = executeQuery('ncart.deleteFavoriteItem', $args);
			if (!$output->toBool()) return $output;
		}
		$this->setMessage('success_deleted');
	}

	function procNcartInsertAddress() 
	{
		$oNcartModel = &getModel('ncart');

		$logged_info = Context::get('logged_info');
		if (!$logged_info) return new Object(-1, 'msg_login_required');

		$req_args = Context::getRequestVars();

		// check the gotten values.
		$fieldset_list = $oNcartModel->getFieldSetList($this->module_info->module_srl);
		foreach($fieldset_list as $key=>$fieldset)
		{
			foreach($fieldset->fields as $key2=>$field)
			{
				// check whether the field is required.
				if($field->required == 'Y' && !isset($req_args->{$field->column_name}))
				{
					return new Object(-1, sprintf(Context::getLang('msg_field_input_required'), $field->column_title . '[' . $filed->column_name . ']'));
				}
				if($field->is_head == 'Y' && isset($req_args->{$field->column_name})) $title = $req_args->{$field->column_name};
				if($field->column_type == 'kr_zip') $in_args->{$field->column_name} = explode('|@|', $in_args->{$field->column_name});
				if(!$title) $title = $req_args->{$field->column_name};
			}
		}

		$args = $req_args;
		$args->member_srl = $logged_info->member_srl;
		if(is_array($title)) $title = implode(' ', $title);
		$args->title = $title;
		$args->serialized_address = serialize($req_args);
		$args->opt = '1';

		if ($args->default && $args->default=='Y') {
			$tmp_args->default = 'N';
			$tmp_args->member_srl = $logged_info->member_srl;
			$output = executeQuery('ncart.updateAddressDefault', $tmp_args);
		}

		if ($args->address_srl) {
			$output = executeQuery('ncart.updateAddress', $args);
			if (!$output->toBool()) return $output;
		} else {
			$args->address_srl = getNextSequence();
			$args->list_order = $args->address_srl;
			$output = executeQuery('ncart.insertAddress', $args);
			if (!$output->toBool()) return $output;
		}

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'act', 'dispNcartAddressList');
			$this->setRedirectUrl($returnUrl);
			return;
		}
	}

	function procNcartUpdateQuantity() 
	{
		$args->cart_srl = Context::get('cart_srl');
		$args->quantity = Context::get('quantity');
		$output = executeQuery('ncart.updateCartItem', $args);
		if (!$output->toBool()) return $output;
		$this->setMessage('success_changed');
	}

	function updateReviewCount($item_srl) 
	{
		$args->item_srl = $item_srl;
		return $this->executeQuery('updateReviewCount', $args);
	}

	function procNcartDeleteAddress() 
	{
		$args->address_srl = Context::get('address_srl');
		$output = executeQuery('ncart.deleteAddress', $args);
		return $output;
	}

	function procNcartMileagePayment()
	{
		$args = Context::getRequestVars();
		$args->order_srl = getNextSequence();
		$args->payment_method = "MI";
		
		$output = $this->triggerProcessReview($args);
		if(is_object($output) && method_exists($output, 'toBool') && !$output->toBool())
		{
			return $output;
		}

		$args->state = 2;
		$output =$this->triggerProcessPayment($args);
		if(is_object($output) && method_exists($output, 'toBool') && !$output->toBool())
		{
			return $output;
		}
	}

	function updateItemStock($item_srls)
	{
		foreach($item_srls as $k => $v)
		{
			$args->item_srl = $v;
			$output = $this->executeQuery('getItemInfo', $args);
			$item_stock = $output->data->item_stock;

			if($item_stock == 0) return new Object(-1,'재고가 0 입니다.');
			else if($item_stock > 0) $this->executeQuery('updateItemStock', $args);
		}
		return new Object();
	}

	function moveNode($node_id, $parent_id) 
	{
		$logged_info = Context::get('logged_info');
		if (!$logged_info) return;

		// get destination
		if (in_array($parent_id, array('f.','t.','s.'))) {
			$dest_route = $parent_id;
		} else {
			$args->node_id = $parent_id;
			$output = $this->executeQuery('getCategoryInfo', $args);
			if (!$output->toBool()) return $output;
			$dest_node = $output->data;
			$dest_route = $dest_node->node_route . $dest_node->node_id . '.';
			$route_text = '분류'. ' > ' . $output->data->category_name;
		}

		// new route
		$new_args->node_id = $node_id;
		$new_args->node_route = $dest_route;
		$new_args->node_route_text = $route_text;
		$new_args->list_order = $parent_id + 1;

		// update children
		$args->node_id = $node_id;
		$output = $this->executeQuery('getCategoryInfo', $args);

		$route_text = $route_text . ' > ' . $output->data->category_name;

		if (!$output->toBool()) return $output;

		$search_args->node_route = $output->data->node_route . $output->data->node_id . '.';
		//$previous_node = $this->getPostNode($output->data->node_route);
		$output = $this->executeQueryArray('getCategoryInfoByNodeRoute', $args);
		
		if (!$output->toBool()) return $output;

		$old_route = $search_args->node_route;
		$new_route = $new_args->node_route . $node_id . '.';

		if ($output->data) {
			foreach ($output->data as $no => $val) {
				$val->node_route = str_replace($old_route, $new_route, $val->node_route);
				$val->node_route_text = $route_text;
				$output = $this->executeQuery('updateCategoryInfo', $args);
			}
		}
		
		// update current
		$output = $this->executeQuery('updateCategoryInfo', $args);
		if (!$output->toBool()) return $output;
		
		// root folder has no node_id.
	
		$this->updateSubItem($node_id, $old_route);
	}

	function updateSubItem($node_id, $old_route) 
	{

            // check node_id
            if (!$node_id && $old_route) return new Object(-1, 'msg_invalid_request');

            // get node_route
            $args->node_id = $node_id;
            $output = $this->executeQuery('getCategoryInfo', $args);
            if (!$output->toBool()) return $output;
            $node_route = $output->data->node_route . $node_id . '.';

            // get subfolder count
            unset($args);
            $args->node_route = $old_route;
			$output = $this->executeQuery('getItemsByNodeRoute', $args);
            if (!$output->toBool()) return $output;
            // update subfolder count
			unset($args);

			foreach($output->data as $k => $v)
			{
				$args->item_srl = $v->item_srl;
	            $args->node_route = $node_route;
				$output = $this->executeQuery('updateItem', $args);
			}
			return $output;

	}

	/**
	 * @brief 로그인 했을 때 회원 카트로 이동 (non_key값을 삭제하고 member_srl값을 입력)
	 */
	function updateGuestCartItems($member_srl, $non_key)
	{
		$args->member_srl = $member_srl;
		$args->non_key = $non_key;
		$args->del_non_key = '';
		$output = executeQuery('ncart.updateNonCartItem', $args);
		if (!$output->toBool()) return $output;
	}

	/**
	 * @brief this method will be called by epay module when users complete to pay to buy somethings.
	 * $in_args->item_name, $in_args->price fields must be set
	 */
	function triggerProcessReview(&$in_args)
	{
		// return if the target_module is not itself.
		if ($in_args->target_module != 'ncart') return;

		// objects to be used below.
		$oNcartModel = &getModel('ncart');
		$oModuleModel = &getModel('module');
		$oMemberModel = &getModel('member');

		// get the member information.
		$logged_info = Context::get('logged_info');

		// $args will be passed to the target modules.
		$args = $in_args;
		if($args->manorder_pid) $manorder_pid = $args->manorder_pid; // 결제대행 유저 아이디.

		// get cart info
		$cart = $oNcartModel->getCartInfo($in_args->cartnos);

		$module_list = array();
		$purchased_modules = array();
		foreach($cart->item_list as $key=>$val)
		{
			// check quantity
			if(!$val->quantity) return new Object(-1, 'msg_no_quantity_input');
			// add module
			if(!in_array($val->module, $module_list)) $module_list[] = $val->module;
			$purchased_modules[] = $val->module;
		}

		$item_count = count($cart->item_list);
		if (!$item_count) return new Object(-1, 'No items to order');

		// check the gotten values.
		$fieldset_list = $oNcartModel->getFieldSetList($in_args->module_srl);
		$fieldcount = 0;
		$delivdest_info = array();
		foreach($fieldset_list as $key=>$fieldset)
		{
			$proc_modules = explode(',', $fieldset->proc_modules);
			debugPrint('$purchased_modules');
			debugPrint($purchased_modules);
			debugPrint($proc_modules);
			debugPrint($fieldset);
			if(count(array_diff($proc_modules, $purchased_modules))==count($proc_modules)) continue;
			foreach($fieldset->fields as $key2=>$field)
			{
				// check whether the field is required.
				if($field->required == 'Y' && !isset($in_args->{$field->column_name}))
				{
					return new Object(-1, sprintf(Context::getLang('msg_field_input_required'), $field->column_title));
				}
				if($field->is_head == 'Y' && isset($in_args->{$field->column_name})) $title = $in_args->{$field->column_name};
				if(in_array($field->column_type, array('kr_zip','tel','checkbox','radio')) && isset($in_args->{$field->column_name}))
				{
					if(!is_array($in_args->{$field->column_name}))
					{
						$in_args->{$field->column_name} = explode('|@|', $in_args->{$field->column_name});
					}
				}
				if(!$title) $title = $in_args->{$field->column_name};
				$delivdest_info[$field->column_title] = $in_args->{$field->column_name};
				$fieldcount++;
			}
		}

		// insert the address
		if($logged_info && $fieldcount)
		{
			$args->member_srl = $logged_info->member_srl;
			if(is_array($title)) $title = implode(' ', $title);
			$args->title = $title;
			$args->serialized_address = serialize($in_args);
			$args->opt = '2';

			$args->address_srl = getNextSequence();
			$args->list_order = $args->address_srl;
			$output = executeQuery('ncart.insertAddress', $args);
			if (!$output->toBool()) return $output;
		}

		// delivdest_info
		$in_args->delivdest_info = $delivdest_info;

		// get title
		$title = $oNcartModel->getOrderTitle($cart->item_list);

		// set item name
		$in_args->item_name = $title;
		$in_args->order_title = $title; // for compatibility
		// set price which is transformed by currency module setting.
		$in_args->price = nproductItem::price($cart->total_price);

		// delivery fee
		if ($in_args->delivfee_inadvance=='N') {
			$cart->total_price -= $cart->delivery_fee;
			$cart->delivery_fee = 0;
		}

		$args = $in_args;

        // use mileage
        if ($args->use_mileage) {
            $cart->total_price = $cart->total_price - (int)$args->use_mileage;
        }
        // calculate mileage
        $args->mileage = 0;
		$config = $oNcartModel->getModuleConfig();
        if ($config->mileage_percent)
        {
            $args->mileage = round($cart->total_price * ((float)$config->mileage_percent/100));
        }

		// insert into store_order
		//$args->order_srl = $order_srl;
		$args->order_srl = $in_args->order_srl;
		$args->title = $title;
		$args->order_title = $title;
		$args->item_count = $item_count;
		if($logged_info)
		{
			$args->member_srl = $logged_info->member_srl;
			$args->purchaser_email = $logged_info->email_address;
			$args->purchaser_name = $logged_info->nick_name;
			if (isset($logged_info->{$config->purchaser_cellphone})) $args->purchaser_cellphone = $logged_info->{$config->purchaser_cellphone};
			if (isset($logged_info->{$config->purchaser_telnum})) $args->purchaser_telnum = $logged_info->{$config->purchaser_telnum};
		}
		if($manorder_pid)
		{
			$args->user_id = $manorder_pid;
			$output = executeQuery('member.getMemberInfo', $args);

			$args->member_srl = $output->data->member_srl;
			$args->purchaser_name = $output->data->nick_name;
			$args->purchaser_email = $output->data->email_address;
		}
		if(!$manorder_pid && !$logged_info)
		{
			$args->purchaser_name = "비회원_".$in_args->purchaser_name;
			$args->purchaser_cellphone = $in_args->cellphone;
			$args->purchaser_telnum = $in_args->telnum;
			$args->purchaser_email = $in_args->email_address;
			$args->member_srl = 0;
		}

		$args->total_price = $cart->total_price;
		$args->price = $in_args->price;
		$args->sum_price = $cart->sum_price;
		$args->delivery_fee = $cart->delivery_fee;
		$args->total_discounted_price = $cart->total_discounted_price;
		$args->total_discount_amount = $cart->total_discount_amount;
		$args->taxation_amount = $cart->taxation_amount;
		$args->supply_amount = $cart->supply_amount;
		$args->taxfree_amount = $cart->taxfree_amount;
		$args->vat = $cart->vat;

		$args->cart = &$cart;
		$args->extra_vars = serialize($in_args);

		$output = $this->insertOrder($args, $args->cart);
		if (!$output->toBool()) return $output;

		// call the review process method of the target module
		foreach($module_list as $key=>$val)
		{
			// get the target module's instance
			$oTargetModule = &getController($val);
			if($oTargetModule)
			{
				if(method_exists($oTargetModule, 'processCartReview'))
				{
					// call the target method
					$output = $oTargetModule->processCartReview($args);
					if(is_object($output) && method_exists($output, 'toBool') && !$output->toBool()) return $output;
				}
				else
				{
					debugPrint(sprintf("processCartReview does not exist in %s controller.", $val));
				}
			}
			else
			{
				debugPrint(sprintf("%s module controller does not exist.", $val));
			}
		}
	}

	/**
	 * $obj->return_url 에 URL을 넘겨주면 pay::procEpayDoPayment에서 해당 URL로 Redirect시켜준다.
	 */
	function triggerProcessPayment(&$obj)
	{
		if ($obj->target_module != 'ncart') return;

		$oNcartModel = &getModel('ncart');
		$oModuleModel = &getModel('module');
		$oPointController = &getController('point');

		$logged_info = Context::get('logged_info');
		$config = $oNcartModel->getModuleConfig();

		$cart = $oNcartModel->getOrderInfo($obj->order_srl);

		// complete order
		if ($obj->state != '3')
		{
			if ($cart->use_mileage)
			{
				$oNmileageController = &getController('nmileage');
				$output = $oNmileageController->minusMileage($cart->member_srl, $cart->use_mileage, $cart->title, $cart->order_srl);
				if(!$output->toBool()) return $output;
			}
		}

		$module_list = array();
		foreach($cart->item_list as $key=>$val)
		{
			/*
			 * stock update
			 */
			if($obj->state == '2')
			{
				$oNproductModel = &getModel('nproduct');
				$oNproductController = &getController('nproduct');

				$stock = $oNproductModel->getItemExtraVarValue($val->item_srl, 'stock');
				if($stock != null)
				{
					$stock = $stock - $val->quantity;
					$output = $oNproductController->updateExtraVars($val->item_srl, 'stock', $stock);
				}
			}

			// add module
			if(!in_array($val->module, $module_list)) $module_list[] = $val->module;
		}
	
		foreach($module_list as $key=>$val)
		{
			$oTargetModule = &getController($val);
			if($oTargetModule)
			{
				if(method_exists($oTargetModule, 'processCartPayment'))
				{
					$output = $oTargetModule->processCartPayment($obj);
					if(is_object($output) && method_exists($output, 'toBool') && !$output->toBool()) return $output;
				}
				else
				{
					debugPrint(sprintf("processCartPayment does not exist in %s controller.", $val));
				}
			}
			else
			{
				debugPrint(sprintf("%s module controller does not exist.", $val));
			}
		}

		/*
		$args->order_srl = $obj->order_srl;
		$args->order_status = '1';
		$output = executeQuery('ncart.updateCartOrderStatus', $args);
		if(!$output->toBool()) return $output;
		 */
		// update order info for success
		switch ($obj->state) 
		{
			case '1': // not completed
				$order_status = '1';
				break;
			case '2': // completed
				$order_status = '2';
				break;
			case '3': // failure
				$order_status = '1';
				break;
		}

		$args->order_status = $order_status;
		$args->payment_method = $obj->payment_method;
		$output = $this->updateOrderStatus($obj->order_srl, $args);
		if(!$output->toBool()) return $output;

		// 비회원 주문일 때 주문완료페이지로 가기 위한 권한설정
		$_SESSION['ORDER_COMPLETE_VIEW_PERMISSION'] = $obj->order_srl;

		$cartModuleInfo = $oModuleModel->getModuleInfoByModuleSrl($obj->module_srl);
		$obj->return_url = getNotEncodedUrl('','act','dispNcartOrderComplete','order_srl',$obj->order_srl,'mid',$cartModuleInfo->mid);
		$this->setRedirectUrl($obj->return_url);
	}

	function insertOrder($in_args, &$cart) 
	{
		$oNstoreModel = &getModel('ncart');

		$args = $in_args;
		/*
		if (is_array($args->purchaser_cellphone)) $args->purchaser_cellphone = implode('-',$in_args->purchaser_cellphone);
		if (is_array($args->purchaser_telnum)) $args->purchaser_telnum = implode('-',$in_args->purchaser_telnum);
		$args->purchaser_address = serialize($in_args->purchaser_address);
		$args->recipient_name = $in_args->recipient_name;
		$args->recipient_cellphone = $in_args->recipient_cellphone;
		$args->recipient_telnum = $in_args->recipient_telnum;
		$args->recipient_address = serialize($in_args->recipient_address);
		$args->non_password = $in_args->non_password;
		 */
		$output = executeQuery('ncart.insertOrder', $args);

		if (!$output->toBool()) return $output;
		unset($args);

		// update cart items.
		$args->order_srl = $in_args->order_srl;
		$args->member_srl = $in_args->member_srl;
		$args->module_srl = $in_args->module_srl;
		foreach ($cart->item_list as $key=>$val) {
			$args->cart_srl = $val->cart_srl;
			$args->discount_amount = $val->discount_amount;
			$args->discount_info = $val->discount_info;
			$args->discounted_price = $val->discounted_price;
			$output = executeQuery('ncart.updateCartItem', $args);
			if (!$output->toBool()) return $output;

		}

		return new Object();
	}

	function updateOrderStatus($order_srl, $in_args) 
	{
		$oNcartModel = &getModel('ncart');
		$config = $oNcartModel->getModuleConfig();

/*
		// if the order is completed, give mileage to the member.
		if ($in_args->state == '2')
		{
			$order_info = $oNcartModel->getOrderInfo($order_srl);
			if ($order_info->member_srl && $order_info->mileage && $order_info->mileage_save=='N')
			{
				$oNmileageController = &getController('nmileage');
				$oNmileageController->plusMileage($order_info->member_srl, $order_info->mileage, $order_info->title, $order_srl);
				$args->mileage_save = 'Y';
			}
		}
*/

		// for order table
		$args->order_srl = $order_srl;
		$args->order_status = $in_args->order_status;
		$args->purdate = "YmdHiS";
		$args->payment_method = $in_args->payment_method;
		$output = executeQuery('ncart.updateOrderStatus', $args);
		if (!$output->toBool()) return $output;

		// for cart table
		$args->order_srl = $order_srl;
		$args->order_status = $in_args->order_status;
		$args->purdate = "YmdHiS";
		$output = executeQuery('ncart.updateCartOrderStatus', $args);
		if (!$output->toBool()) return $output;

		return new Object();
	}
}
/* End of file ncart.controller.php */
/* Location: ./modules/ncart/ncart.controller.php */
