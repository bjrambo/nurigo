<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  ncartView
 * @author NURIGO(contact@nurigo.net)
 * @brief  ncartView
 */
class ncartView extends ncart
{
	function init()
	{
		if(!$this->module_info->skin) $this->module_info->skin = 'default';
		$skin = $this->module_info->skin;
		$oModuleModel = &getModel('module');
		// 템플릿 경로 설정
		$this->setTemplatePath(sprintf('%sskins/%s', $this->module_path, $skin));

		$logged_info = Context::get('logged_info');

		if($logged_info) Context::set('login_chk','Y');
		else if(!$logged_info) Context::set('login_chk','N');

		Context::set('hide_trolley', 'true');
	}

	function keygen()
	{
		$randval = rand(100000, 999999);
		$usec = explode(" ", microtime());
		$str_usec = str_replace(".", "", strval($usec[0]));
		$str_usec = substr($str_usec, 0, 6);
		return date("YmdHis") . $str_usec . $randval;
	}

	function getCategoryTree($module_srl) 
	{
		$oNcartModel = &getModel('ncart');
		$category = Context::get('category');
		if ($category && $this->module_info->category_display=='2') {
			$category_info = $oNcartModel->getCategoryInfo($category);
			$top = preg_split('/\./', $category_info->node_route);
			if (count($top) >= 2) {
				$args->node_route = sprintf("%s.%s.", $top[0], $top[1]);
				Context::set('top_category_srl', $top[1]);
			}
			if ($category_info->node_route=='f.') $args->node_route = 'f.' . $category . '.';
		}

		// category tree
		$args->module_srl = $module_srl;
		$output = $this->executeQueryArray('getCategoryAllSubitems', $args);
		if (!$output->toBool()) return $output;
		$category_list = $output->data;
		$category_tree = array();
		$category_index = array();
		if ($category_list) {
			foreach ($category_list as $no => $cate) {
				$node_route = $cate->node_route.$cate->node_id;
				$stages = explode('.',$node_route);
				$code_str = '$category_tree["' . implode('"]["', $stages) . '"] = array();';
				eval($code_str);
				$category_index[$cate->node_id] = $cate;
			}
		}
		Context::set('category_tree', $category_tree);
		Context::set('category_index', $category_index);
	}

	/**
	 * @brief triggered by epay.processReview(after)
	 * @return payment review form
	 */
	function triggerReviewForm(&$in_args)
	{
		$oModuleModel = &getModel('module');

		// load module config
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($in_args->module_srl);
		if(!$module_info->skin) $module_info->skin = 'default';
		// load skin config
		$oModuleModel->syncSkinInfoToModuleInfo($module_info);
		Context::set('ncart_module_info', $module_info);

		// load cart info.
		$oNcartModel = &getModel('ncart');
		$cart_info = $oNcartModel->getCartInfo($in_args->cartnos);
		Context::set('cart_info', $cart_info);
		
		// compile template file
		$template_path = sprintf('%sskins/%s', $this->module_path, $module_info->skin);
		$oTemplate = &TemplateHandler::getInstance();
		$in_args->review_form = $oTemplate->compile($template_path, 'reviewform.html');
	}

	function dispNcartCartItems() 
	{
		$oNcartModel = &getModel('ncart');
		$cart = $oNcartModel->getCartInfo(Context::get('cartnos'));

		Context::set('list',$cart->item_list);
		Context::set('sum_price',$cart->sum_price);
		Context::set('total_price',$cart->total_price);
		Context::set('delivery_fee',$cart->delivery_fee);
		Context::set('total_discounted_price',$cart->total_discounted_price);
		Context::set('total_discount_amount',$cart->total_discount_amount);

		// get module config
		$config = $oNcartModel->getModuleConfig();
		Context::set('config',$config);

		$this->setTemplateFile('cartitems');
	}

	function dispNcartFavoriteItems() 
	{
		$oFileModel = &getModel('file');
		$oNcartModel = &getModel('ncart');

		$logged_info = Context::get('logged_info');
		if (!$logged_info) return new Object(-1, 'msg_login_required');

		// favorite items
		$favorite_items = $oNcartModel->getFavoriteItems($logged_info->member_srl);
		Context::set('favorite_items', $favorite_items);

		// get module config
		$config = $oNcartModel->getModuleConfig();
		Context::set('config',$config);

		$this->setTemplateFile('favoriteitems');
	}

	/**
	 * @brief set omitted items to each of purchasing items.
	 * @param $itemList is an array of item objects. omittedItems property will be added to the each item.
	 */
	function setOmittedItems(&$itemList)
	{
		$oNproductModel = &getModel('nproduct');
		$itemSrlsOfPurchasingItems = array();
		foreach($itemList as $item)
		{
			$itemSrlsOfPurchasingItems[] = $item->item_srl;
		}
		$list = $oNproductModel->getItemList($itemSrlsOfPurchasingItems, 999);
		foreach($list as $key => $item)
		{
			$forcedItemSrls = array();
			if($item->related_items) $forcedItemSrls = $oNproductModel->getForcedItemSrls($item->related_items);
			$omittedItemSrls = array();
			foreach($forcedItemSrls as $itemSrl)
			{
				if(!in_array($itemSrl, $itemSrlsOfPurchasingItems)) $omittedItemSrls[] = $itemSrl;
			}
			if(count($omittedItemSrls)) $itemList[$key]->omittedItems = $oNproductModel->getItemList($omittedItemSrls, 999);
		}
	}

	/**
	 * @brief set minimum order items to each of purchasing items
	 * @param $inputItemList is an array of item objects. minimumOrderItems property will be added to the each item.
	 */
	function setMinimumOrderItems(&$inputItemList)
	{
		// get nproduct model reference
		$oNproductModel = &getModel('nproduct');

		// get item srls
		$itemSrlsToPurchase = $oNproductModel->getItemSrls($inputItemList);

		// $inputItemList에는 related_items 정보가 없으므로 온전한 상품정보를 가져온다.
		$itemList = $oNproductModel->getItemList($itemSrlsToPurchase, 999);

		// get minimum order items
		$minimumOrderItems = $oNproductModel->getMinimumOrderItems($itemList);

		// add minimumOrderItems to each item of $inputItemList
		foreach($inputItemList as $no => $item)
		{
			if(!isset($minimumOrderItems[$item->item_srl])) continue;
			$tmpItem = $minimumOrderItems[$item->item_srl];
			$inputItemList[$no]->minimumOrderItems = $tmpItem->message;
		}
	}

	function dispNcartOrderItems() 
	{
		global $lang;

		$oFileModel = &getModel('file');
		$oEpayView = &getView('epay');
		$oStoreController = &getController('ncart');
		$oNcartModel = &getModel('ncart');

		$logged_info = Context::get('logged_info');

		// get module config
		$config = $oNcartModel->getModuleConfig();
		Context::set('config',$config);

		if($config->guest_buy != 'Y' && !$logged_info)
		{
			return new Object(-1, 'msg_no_guest_buy');
		}

		$cartnos = Context::get('cartnos');
		$cart = $oNcartModel->getCartInfo($cartnos);

		if (!count($cart->item_list))
		{
			return new Object(-1, $lang->msg_no_items);
		}


		/*
		 * stock check
		 */

		$oNproductModel = &getModel('nproduct');

		//quantity
		foreach ($cart->item_list as $key=>$val) 
		{
			$item_info = $oNproductModel->getItemInfo($val->item_srl);

			if(!$stock[$val->item_srl])
			{
				if($stock[$val->item_srl] !== 0) $stock[$val->item_srl] = $oNproductModel->getItemExtraVarValue($val->item_srl, 'stock');
				if($stock[$val->item_srl] == '0') return new Object(-1, sprintf(Context::getLang('msg_not_enough_stock'), $item_info->item_name));
			}

			if($stock[$val->item_srl] != null)
			{
				if($stock[$val->item_srl] < $val->quantity) return new Object(-1, sprintf(Context::getLang('msg_not_enough_stock'), $item_info->item_name));
				if($stock[$val->item_srl] === 0 || $stock[$val->item_srl] > 0) $stock[$val->item_srl] = $stock[$val->item_srl] - $val->quantity;
				if($stock[$val->item_srl] < 0) return new Object(-1, sprintf(Context::getLang('msg_not_enough_stock'), $item_info->item_name));
			}
		}

		/*
		 * end
		 */

		Context::set('list',$cart->item_list);
		Context::set('sum_price',$cart->sum_price);
		Context::set('total_price',$cart->total_price);
		Context::set('delivery_fee',$cart->delivery_fee);
		Context::set('total_discounted_price',$cart->total_discounted_price);
		Context::set('total_discount_amount',$cart->total_discount_amount);
		
		// get order title
		$order_title = $oNcartModel->getOrderTitle($cart->item_list);
		$args->item_name = $order_title;

		// pass payment amount, item name, etc.. to epay module.
		// Context::set('payment_amount', 10000);
		$args->epay_module_srl = $this->module_info->epay_module_srl;
		$args->module_srl = $this->module_info->module_srl;
		$args->price = $cart->total_price;
		//$args->order_srl = $order_srl;
		
		if($logged_info)
		{
			$args->purchaser_name = $logged_info->nick_name;
			$args->purchaser_email = $logged_info->email_address;
			$args->purchaser_telnum = "$lang->msg_phone_input"."ex)010-0000-0000";
		}
		else if(!$logged_info)
		{
			$args->purchaser_name = $lang->non_member;
			$args->purchaser_email = $lang->msg_email_input;
			$args->purchaser_telnum = "$lang->msg_phone_input"."ex)010-0000-0000";
		}
		$args->join_form = 'fo_insert_order';
		$args->target_module = 'ncart';

		$output = $oEpayView->getPaymentForm($args);
		if (!$output->toBool()) 
		{
			return $output;
		}
		$epay_form = $output->data;
		Context::set('epay_form', $epay_form);
		unset($args);

		Context::addJsFile('./modules/krzip/tpl/js/krzip_search.js');
		Context::set('soldout_process', $this->soldout_process);

		$oNmileageModel = &getModel('nmileage');
		$mileage_config = $oNmileageModel->getModuleConfig();
		Context::set('mileage_flag', $mileage_config->use_flag);

		// mileage info
		$my_mileage = $oNcartModel->getMileage($logged_info->member_srl);
		if (!$my_mileage) $my_mileage = 0; 
		Context::set('my_mileage', $my_mileage);

		// fieldset
		$fieldset_list = $oNcartModel->getFieldSetList($this->module_info->module_srl);
		Context::set('fieldset_list', $fieldset_list);

		// add ruleset messages for order form
		$script = "<script>(function($) { jQuery(function($) { var appValidator = xe.getApp('validator')[0];";
		foreach($fieldset_list as $fieldset)
		{
			foreach($fieldset->fields as $field)
			{
				$column_name = $field->column_name;
				// if column types are tel or kr_zip, add [] in suffix
				if(in_array($field->column_type, array('tel','kr_zip'))) $column_name = $field->column_name . '[]';
				$script .= sprintf("appValidator.cast('ADD_MESSAGE',['%s','%s']);", $column_name, $field->column_title);
			}
		}
		$script .= "}); }) (jQuery);</script>";
		Context::addHtmlHeader($script);

		if($logged_info)
		{
			$args->member_srl = $logged_info->member_srl;
			$args->opt = '1';
			$output = executeQueryArray('ncart.getAddressList', $args);
			if (!$output->toBool()) return $output;
			unset($args);
			Context::set('address_list', $output->data);
		}

		// 필수 구매사항 확인
		$this->setOmittedItems($cart->item_list);

		// check minimum order quantity
		$this->setMinimumOrderItems($cart->item_list);

		$this->setTemplateFile('orderitems');
	}

	function dispNcartOrderComplete() 
	{
		$oNcartModel = &getModel('ncart');
		$oEpayModel = &getModel('epay');
		$logged_info = Context::get('logged_info');

		$order_srl = Context::get('order_srl');
		if (!$order_srl) return new Object(-1, 'msg_invalid_request');

		// 주문정보 읽어오기
		$order_info = $oNcartModel->getOrderInfo($order_srl);
		if(!$order_info) return new Object(-1, 'msg_invalid_order_number');
		Context::set('order_info', $order_info);
		$extra_vars = unserialize($order_info->extra_vars);

		// 주문한 사람이 아니라면
		if($order_info->member_srl != $logged_info->member_srl) return new Object(-1, 'msg_not_permitted');

		// 로그인 안했을 때 권한 확인 : triggerProcessPayment 에서 설정된다.
		if(!$logged_info && $_SESSION['ORDER_COMPLETE_VIEW_PERMISSION'] != $order_srl) return new Object(-1, 'msg_not_permitted');

		$payment_info = $oEpayModel->getTransactionByOrderSrl($order_srl);
		Context::set('payment_info',$payment_info);

		// fieldset
		$fieldset_list = $oNcartModel->getFieldSetList($this->module_info->module_srl);
		foreach($fieldset_list as $key=>&$val)
		{
				foreach($val->fields as $key2=>&$field)
				{
						if(isset($extra_vars->{$field->column_name}))
						{
							$field->value = $extra_vars->{$field->column_name};
						}
				}
		}
		Context::set('fieldset_list', $fieldset_list);
		Context::set('order_status', $this->getOrderStatus());

		$oNmileageModel = &getModel('nmileage');
		$mileage_config = $oNmileageModel->getModuleConfig();
		Context::set('mileage_flag', $mileage_config->use_flag);

		$this->setTemplateFile('ordercomplete');
	}

	function dispNcartOrderDetail() 
	{
		$oFileModel = &getModel('file');
		$oEpayModel = &getModel('epay');
		$oNcartModel = &getModel('ncart');

		$logged_info = Context::get('logged_info');

		// 주문번호가 없다면
		if(!Context::get('order_srl')) return new Object(-1, 'msg_invalid_order_number');

		$order_srl = Context::get('order_srl');
		$order_info = $oNcartModel->getOrderInfo($order_srl);

		// 주문정보가 없다면
		if(!$order_info) return new Object(-1, 'msg_invalid_order_number');

		// 로그인이 되어 있지 않다면
		if(!$logged_info) return new Object(-1, 'msg_not_permitted');

		// 주문한 사람이 아니라면
		if($order_info->member_srl != $logged_info->member_srl) return new Object(-1, 'msg_not_permitted');

		Context::set('order_info', $order_info);
		Context::set('order_status', $this->getOrderStatus());

		$payment_info = $oEpayModel->getTransactionByOrderSrl($order_srl);
		Context::set('payment_info',$payment_info);
		Context::set('payment_method',$this->getPaymentMethods());

		Context::set('delivery_inquiry_urls', $this->delivery_inquiry_urls);
		Context::set('delivery_companies', $oNcartModel->getDeliveryCompanies());
		Context::set('soldout_process', $this->soldout_process);

		$this->setTemplateFile('orderdetail');
	}

	function dispNcartReplyComment() 
	{
		// 권한 체크
		if(!$this->grant->write_comment) return new Object(-1,'msg_not_permitted');

		// 목록 구현에 필요한 변수들을 가져온다
		$parent_srl = Context::get('comment_srl');

		// 지정된 원 댓글이 없다면 오류
		if(!$parent_srl) return new Object(-1, 'msg_invalid_request');

		// 해당 댓글를 찾아본다
		$oCommentModel = &getModel('comment');
		$oSourceComment = $oCommentModel->getComment($parent_srl, $this->grant->manager);

		// 댓글이 없다면 오류
		if(!$oSourceComment->isExists()) return new Object(-1, 'msg_invalid_request');
		if(Context::get('document_srl') && $oSourceComment->get('document_srl') != Context::get('document_srl')) return new Object(-1, 'msg_invalid_request');

		// 대상 댓글을 생성
		$oComment = $oCommentModel->getComment();
		$oComment->add('parent_srl', $parent_srl);
		$oComment->add('document_srl', $oSourceComment->get('document_srl'));

		// 필요한 정보들 세팅
		Context::set('oSourceComment',$oSourceComment);
		Context::set('oComment',$oComment);
		Context::set('module_srl',$this->module_info->module_srl);

		/** 
		 * 사용되는 javascript 필터 추가
		 **/
		//Context::addJsFilter($this->module_path.'tpl/filter', 'insert_comment.xml');

		$this->setTemplateFile('commentform');
	}

	function dispNcartAddressList() 
	{
		$oNcartModel = &getModel('ncart');
		$oNcartModel->checkBrowser(); // iphone check

		$logged_info = Context::get('logged_info');
		if (!$logged_info) return new Object(-1, 'msg_login_required');

		$args->member_srl = $logged_info->member_srl;
		$args->opt = '1';
		$output = executeQueryArray('ncart.getAddressList', $args);
		if (!$output->toBool()) return $output;
		Context::set('list', $output->data);

		$fieldset_list = $oNcartModel->getFieldSetList($this->module_info->module_srl);
		Context::set('fieldset_list', $fieldset_list);

		$this->setLayoutFile('default_layout');
		$this->setTemplateFile('addresslist');
	}

	function dispNcartAddressManagement() 
	{
		$oNcartModel = &getModel('ncart');

		$logged_info = Context::get('logged_info');
		if (!$logged_info) return new Object(-1, 'msg_login_required');

		$args->member_srl = $logged_info->member_srl;
		$args->opt = '1';
		$output = executeQueryArray('ncart.getAddressList', $args);
		if (!$output->toBool()) return $output;

		Context::set('list', $output->data);

		$fieldset_list = $oNcartModel->getFieldSetList($this->module_info->module_srl);
		Context::set('fieldset_list', $fieldset_list);

		$this->setLayoutFile('default_layout');
		$this->setTemplateFile('addressmanagement');

		Context::addJsFile('./modules/member/tpl/js/krzip_search.js');
	}

	function dispNcartRecentAddress() 
	{
		$oNcartModel = &getModel('ncart');

		$logged_info = Context::get('logged_info');
		if (!$logged_info) return new Object(-1, 'msg_login_required');

		$args->member_srl = $logged_info->member_srl;
		$args->opt = '2';
		$args->sort_index = 'address_srl';
		$args->sort_order = 'desc';
		$output = executeQueryArray('ncart.getAddressList', $args);
		if (!$output->toBool()) return $output;
		Context::set('list', $output->data);

		$fieldset_list = $oNcartModel->getFieldSetList($this->module_info->module_srl);
		Context::set('fieldset_list', $fieldset_list);

		$this->setLayoutFile('default_layout');
		$this->setTemplateFile('recentaddress');
	}

	function dispNcartLogin() 
	{
		$oNcartModel = &getModel('ncart');
		// get module config
		$config = $oNcartModel->getModuleConfig();
		Context::set('config',$config);

		$this->setTemplateFile('login_form');
	}
}
/* End of file ncart.view.php */
/* Location: ./modules/ncart/ncart.view.php */
