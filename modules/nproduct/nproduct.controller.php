<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nproductController
 * @author NURIGO(contact@nurigo.net)
 * @brief  nproductController
 */
class nproductController extends nproduct
{
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

	/**
	 * @brief update sale count
	 */
	function updateSalesCount($item_srl, $quantity) 
	{
		if (!$item_srl) return;
		$args->item_srl = $item_srl;
		for ($i = 0; $i < $quantity; $i++)
		{
			executeQuery('nproduct.updateSalesCount', $args);
		}
	}

	/**
	 * @brief update review count
	 */
	function updateReviewCount($item_srl) 
	{
		$args->item_srl = $item_srl;
		return executeQuery('nproduct.updateReviewCount', $args);
	}

	/**
	 * @brief update download count
	 */
	function updateDownloadCount($item_srl) 
	{
		$args->item_srl = $item_srl;
		return executeQuery('nproduct.updateDownloadCount', $args);
	}


	/**
	 * @brief node_id의 node_route를 구해서 node_route로 검색하여 하위 명단 갯수를 구하여 업댓
	 * @param[in] node_id : 업댓할 node_id
	 **/
	function updateSubnode($node_id) 
	{
		$subnode = 0;

		$args->node_id = $node_id;
		$output = executeQuery('nproduct.getCategoryInfo', $args);
		if(!$output->toBool()) return $output;
		$node_route = $output->data->node_route . $node_id . '.';

		unset($args);
		$args->node_route = $node_route;
		$output = executeQuery('nproduct.getSubCategoryCount', $args);
		if(!$output->toBool()) return $output;
		if($output->data) $subnode = $output->data->count;

		unset($args);
		$args->subnode = $subnode;
		$args->node_id = $node_id;
		$output = executeQuery('nproduct.updateSubnode', $args);
		if(!$output->toBool()) return $output;
		return $output;
	}

	/**
	 * @brief move node
	 */
	function moveNode($node_id, $parent_id) 
	{
		$logged_info = Context::get('logged_info');
		if (!$logged_info) return;

		// get destination
		if (in_array($parent_id, array('f.','t.','s.'))) 
		{
			$dest_route = $parent_id;
		} 
		else 
		{
			$args->node_id = $parent_id;
			$output = executeQuery('nproduct.getCategoryInfo', $args);
			if (!$output->toBool()) return $output;
			$dest_node = $output->data;
			$dest_route = $dest_node->node_route . $dest_node->node_id . '.';
			$route_text = Context::getLang('category') . ' > ' . $output->data->category_name;
		}

		// new route
		$new_args->node_id = $node_id;
		$new_args->node_route = $dest_route;
		$new_args->node_route_text = $route_text;
		$new_args->list_order = $parent_id + 1;

		// update children
		$args->node_id = $node_id;
		$output = executeQuery('nproduct.getCategoryInfo', $args);
		if(!$output->toBool()) return $output;
		$route_text = $route_text . ' > ' . $output->data->category_name;
		$search_args->node_route = $output->data->node_route . $output->data->node_id . '.';
		$output = executeQueryArray('nproduct.getCategoryInfoByNodeRoute', $args);
		if (!$output->toBool()) return $output;

		$old_route = $search_args->node_route;
		$new_route = $new_args->node_route . $node_id . '.';

		if ($output->data) 
		{
			foreach ($output->data as $no => $val) 
			{
				$val->node_route = str_replace($old_route, $new_route, $val->node_route);
				$val->node_route_text = $route_text;
				$output = executeQuery('nproduct.updateCategoryInfo', $args);
				if(!$output->toBool()) return $output;
			}
		}
		
		// update current
		$output = executeQuery('nproduct.updateCategoryInfo', $args);
		if (!$output->toBool()) return $output;
		
		// root folder has no node_id.
		$this->updateSubItem($node_id, $old_route);
	}

	/**
	 * @brief move node to next
	 */
	function moveNodeToNext($node_id, $parent_id, $next_id) 
	{
		$logged_info = Context::get('logged_info');
		if (!$logged_info) return;

		$args->node_id = $next_id;
		$output = executeQuery('nproduct.getCategoryInfo', $args);
		if (!$output->toBool()) return $output;
		$next_node = $output->data;
		unset($args);

		// plus next siblings
		$args->node_route = $next_node->node_route;
		$args->list_order = $next_node->list_order;
		$output = executeQuery('nproduct.updateCategoryOrder', $args);
		if (!$output->toBool()) return $output;

		// update myself
		$list_order = $next_node->list_order;
		$args->node_id = $node_id;
		$args->list_order = $list_order;
		$output = executeQuery('nproduct.updateCategoryNode', $args);
		if (!$output->toBool()) return $output;
	}

	/**
	 * @brief move node to previous
	 */
	function moveNodeToPrev($node_id, $parent_id, $prev_id) 
	{
		$logged_info = Context::get('logged_info');
		if (!$logged_info) return;

		$args->node_id = $prev_id;
		$output = executeQuery('nproduct.getCategoryInfo', $args);
		if (!$output->toBool()) return $output;
		$prev_node = $output->data;
		unset($args);

		// update myself
		$list_order = $prev_node->list_order+1;
		$args->node_id = $node_id;
		$args->list_order = $list_order;
		$output = executeQuery('nproduct.updateCategoryNode', $args);
		if (!$output->toBool()) return $output;
	}

	/**
	 * @brief update sub item
	 */
	function updateSubItem($node_id, $old_route) 
	{
            // check node_id
            if (!$node_id && $old_route) return new Object(-1, 'msg_invalid_request');

            // get node_route
            $args->node_id = $node_id;
            $output = executeQuery('nproduct.getCategoryInfo', $args);
            if (!$output->toBool()) return $output;
            $node_route = $output->data->node_route . $node_id . '.';

            // get subfolder count
            unset($args);
            $args->node_route = $old_route;
			$output = executeQuery('nproduct.getItemsByNodeRoute', $args);
            if (!$output->toBool()) return $output;
            // update subfolder count
			unset($args);

			foreach($output->data as $k => $v)
			{
				$args->item_srl = $v->item_srl;
	            $args->node_route = $node_route;
				$output = executeQuery('nproduct.updateItem', $args);
				if(!$output->toBool()) return $output;
			}
			return $output;
	}

	/**
	 * @brief insert item
	 */
	function insertItem($in_args) 
	{
		$oDocumentController = &getController('document');
		$oNproductModel = &getModel('nproduct');
		$oModuleModel = &getModel('module');

		$logged_info = Context::get('logged_info');
		if (!$logged_info) return new Object(-1, 'msg_login_required');

		$module_srl = $in_args->module_srl;
		$item_code = $in_args->item_code;
		$item_name = $in_args->item_name;
		$category_id = $in_args->category_id;
		$document_srl = $in_args->document_srl;
		$description = $in_args->description;
		//$delivery_info = Context::get('delivery_info');
		$price = $in_args->price;
		$taxfree = $in_args->taxfree;
		$display = $in_args->display;

		if (!$module_srl || !$item_name || !$display) return new Object(-1,'msg_invalid_request');

		$category_info = $oNproductModel->getCategoryInfo($category_id);
		if ($category_info)
		{
			$node_route = $category_info->node_route . $category_info->node_id . '.';
		}
		else
		{
			$node_route = 'f.';
		}

		$item_srl = getNextSequence();
		if (!$item_code) $item_code = $item_srl;

		// insert document
		if (!$document_srl) $document_srl = getNextSequence();
		$doc_args->document_srl = $document_srl;
		//$doc_args->category_srl = $category_id;
		$doc_args->module_srl = $module_srl;
		$doc_args->content = $description;
		$doc_args->title = $item_name;
		$doc_args->list_order = $doc_args->document_srl*-1;
		$doc_args->tags = Context::get('tag');
		$doc_args->allow_comment = 'Y';
		$output = $oDocumentController->insertDocument($doc_args);
		if (!$output->toBool()) return $output;
		unset($doc_args);

		// default delivery_info
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
		$delivery_info = $module_info->delivery_info;

		// insert item
		$extra_vars = $oNproductModel->getExtraVars($module_srl);

		$args->item_srl = $item_srl;
		$args->item_code = $item_code;
		$args->item_name = $item_name;
		$args->module_srl = $module_srl;
		$args->category_id = $category_id;
		$args->proc_module = $in_args->proc_module;
		$args->node_route = $node_route;
		$args->document_srl = $document_srl;
		$args->price = $price;
		$args->taxfree = $taxfree;
		$args->display = $display;
		$args->delivery_info = $delivery_info;
		$args->list_order = $item_srl * -1;

		$extra_vars = delObjectVars($extra_vars, $args);
		$args->extra_vars = serialize($extra_vars);

		$output = executeQuery('nproduct.insertItem', $args);
		if (!$output->toBool()) return $output;

		$output = new Object();
		$output->add('item_srl', $item_srl);
		return $output;
	}

	/**
	 * @brief insert category
	 */
	function procNproductInsertCategory() 
	{
		$logged_info = Context::get('logged_info');
		if (!$logged_info) return new Object(-1, 'msg_login_required');

		$module_srl = Context::get('module_srl');
		$parent_node = Context::get('parent_node');
		$category_name = Context::get('category_name');

		// deny adding to trashcan and folder shared
		if (in_array($parent_node, array('t.','s.'))) 
			return new Object(-1, 'msg_cannot_create_folder');

		// get node_route
		if (in_array($parent_node, array('f.','t.','s.'))) 
		{
			$node_route = $parent_node;
			$node_route_text = Context::getLang('category');
		} 
		else 
		{
			// get parent node
			$args->node_id = $parent_node;
			$output = executeQuery('nproduct.getCategoryInfo', $args);
			if (!$output->toBool()) return $output;
			if (!$output->data) return new Object(-1, 'msg_parent_node_not_found');
			$pnode = $output->data;
			$node_route = $pnode->node_route . $pnode->node_id . '.';
			$node_route_text = $pnode->node_route_text . ' > ' . $pnode->category_name;
			unset($args);
		}
	
		if(!preg_match('/^([a-zA-Z0-9]+\.){1,4}$/',$node_route))
			return new Object(-1, 'msg_subcategory_limit');	
		 
		$node_id = getNextSequence();
		$args->node_id = $node_id;
		$args->node_route = $node_route;
		$args->node_route_text = $node_route_text;
		$args->module_srl = $module_srl;
		$args->category_name = $category_name;
		$args->list_order = $node_id;
		$output = executeQuery('nproduct.insertCategoryNode', $args);
		if (!$output->toBool()) return $output;
		unset($args);

		if(!in_array($parent_node, array('f.','t.','s.')))
			$this->updateSubnode($parent_node);

		$this->add('node_id', $node_id);
		$this->add('parent_node', $parent_node);
	}

	/**
	 * @brief update category
	 */
	function procNproductUpdateCategory() 
	{
		$logged_info = Context::get('logged_info');
		if (!$logged_info) return new Object(-1, 'msg_login_required');

		$node_id = Context::get('node_id');
		$category_name = Context::get('category_name');

		if (in_array($node_id, array('f.'))) 
			return new Object(-1, 'msg_cannot_update_root');

		$args->node_id = $node_id;
		$args->category_name = $category_name;
		$output = executeQuery('nproduct.updateCategoryNode', $args);
		if (!$output->toBool()) return $output;
		unset($args);

		$this->add('node_id', $node_id);
	}

	/**
	 * @brief move category
	 */
	function procNproductMoveCategory() 
	{
		$logged_info = Context::get('logged_info');
		if (!$logged_info) return new Object(-1, 'msg_log_required');

		$parent_id = Context::get('parent_id');
		$node_id = Context::get('node_id');
		$target_id = Context::get('target_id');
		$position = Context::get('position');

		$this->moveNode($node_id, $parent_id);

		if ($position=='next') 
		{
			$output = $this->moveNodeToNext($node_id, $parent_id, $target_id);
			if (!$output->toBool()) return $output;
		}

		if ($position=='prev') 
		{
			$output = $this->moveNodeToPrev($node_id, $parent_id, $target_id);
			if (!$output->toBool()) return $output;
		}
	}


	/**
	 * @brief 장바구니에 상품 담기
	 */
	function addItemsToUnifiedCart() 
	{
		$oNcartController = &getController('ncart');
		$oNproductModel = &getModel('nproduct');
		$oModuleModel = &getModel('module');

		$config = $oNproductModel->getModuleConfig();
		$all_args = Context::getRequestVars();

		$logged_info = Context::get('logged_info');

		$option_srls = $this->getArrCommaSrls('option_srls');
		//$quantities = $this->getArrCommaSrls('quantities');

		$item_srl = $this->getArrCommaSrls('item_srl');
		$quantity = $this->getArrCommaSrls('quantity');
		$cart_srl_arr = array();

		foreach ($item_srl as $key=>$val) 
		{
			if (!$val) continue;
			$item_info = $oNproductModel->getItemInfo($val);

			if (!$item_info)
				return new Object(-1, 'Item not found.');
	
			$output = $oNproductModel->discountItem($item_info);
			if(!$output->toBool()) return $output;
			$item_info->discount_amount = $output->discount_amount;
			$item_info->discount_info = $output->discount_info;
			$item_info->discounted_price = $output->discounted_price;

			/**
			 * 구매옵션 정보 확인
			 */
			$options = $oNproductModel->getOptions($val);

			// 구매옵션이 있는 상품이면 구매옵션 선택 여부를 체크해야 한다.
			if (count($options) && !count($option_srls))
				return new Object(-1, 'msg_select_option');

			// 기본 배송회사ID 가져오기 위해 모듈정보 읽기
			$module_srl = $item_info->module_srl;
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);

			// 구매옵션이 있으면 옵션만큼 카트에 상품담기
			if (count($options) > 0) 
			{
				// 구매옵션 정보를 nstore_cart_options 테이블에 넣는다.
				$optseq = 0;
				foreach ($option_srls as $opt_key=>$opt_val)
				{
					$args->cart_srl = 0; // will be passed by $oNcartController->addItems
					$args->item_srl = $item_info->item_srl;
					$args->item_code = $item_info->item_code;
					$args->item_name = $item_info->item_name;
					$args->document_srl = $item_info->document_srl;
					$args->file_srl = $item_info->file_srl;
					$args->thumb_file_srl = $item_info->thumb_file_srl;
					$args->member_srl = 0;
					if($logged_info) $args->member_srl = $logged_info->member_srl;
					
					$args->module_srl = $module_srl;
					$args->quantity = 1;
					if(array_key_exists($optseq,$quantity)) $args->quantity = $quantity[$optseq];
					
					$optseq++;
					$args->price = $item_info->price;
					$args->taxfree = $item_info->taxfree;
					$args->discount_amount = $item_info->discount_amount;
					$args->discount_info = $item_info->discount_info;
					$args->discounted_price = $item_info->discounted_price;
					$args->express_id = $module_info->express_id;
					$args->option_srl = $opt_val;
					$args->option_price = $options[$opt_val]->price;
					$args->option_title = $options[$opt_val]->title;
					$args->module = $item_info->proc_module;

					// addItems will return $args->cart_srl
					$output = $oNcartController->addItems($args);
					if (!$output->toBool()) return $output;

					$cart_srl_arr[] = $output->get('cart_srl');
					unset($args);

					if($config->cart_on == 'N') $this->setMessage('msg_put_item_in_cart');
					$this->add('cart_on',$config->cart_on);
				}
			}
			else // 구매옵션이 없으면 카트에 상품 한개만 담기
			{
				/**
				 * 상품정보 카트에 담기
				 */
				$args->cart_srl = 0; // will be passed by $oNcartController->addItems
				$args->item_srl = $item_info->item_srl;
				$args->item_code = $item_info->item_code;
				$args->item_name = $item_info->item_name;
				$args->document_srl = $item_info->document_srl;
				$args->file_srl = $item_info->file_srl;
				$args->thumb_file_srl = $item_info->thumb_file_srl;
				$args->module_srl = $module_srl;
				$args->member_srl = 0;
				if($logged_info) $args->member_srl = $logged_info->member_srl;
				$args->quantity = 1;
				if(array_key_exists($key,$quantity))
					$args->quantity = $quantity[$key];

				$stock = $oNproductModel->getItemExtraVarValue($item_info->item_srl, 'stock');
				if($stock != null) 
				{
					if($stock < $args->quantity || $stock == '0')  
						return new Object(-1, sprintf(Context::getLang('msg_not_enough_stock'), $item_info->item_name));
				}
			
				$args->price = $item_info->price;
				$args->taxfree = $item_info->taxfree;
				$args->discount_amount = $item_info->discount_amount;
				$args->discount_info = $item_info->discount_info;
				$args->discounted_price = $item_info->discounted_price;
				$args->express_id = $module_info->express_id;
				$args->module = $item_info->proc_module;

				// addItems will return $args->cart_srl
				$output = $oNcartController->addItems($args);
				if(!$output->toBool()) return $output;
				unset($args);

				$cart_srl_arr[] = $output->get('cart_srl');
				if($config->cart_on == 'N') $this->setMessage('msg_put_item_in_cart');
				$this->add('cart_on',$config->cart_on);	
			}
		}
		$this->add('cart_srl', implode(',',$cart_srl_arr));
		// return parent::procNstore_coreAddItemsToCart();
	}

	/**
	 * @brief add items to cart
	 */
	function procNproductAddItemsToCart() 
	{
		return $this->addItemsToUnifiedCart();
	}

	/**
	 * @brief 장바구니에 상품 담기
	 */
	function procNproductAddItemsToCartObj() 
	{
		// get references of modules' controllers
		$oNcartController = &getController('ncart');
		$oNproductModel = &getModel('nproduct');
		$oModuleModel = &getModel('module');

		// prepare variables to use in this function
		$config = $oNproductModel->getModuleConfig();
		$all_args = Context::getRequestVars();
		$data = json_decode(Context::get('data'));

		// get all item_srls to purchase
		$itemSrlsToPurchase = $oNproductModel->getItemSrls($data);

		// check forced purchasing items
		$itemList = $oNproductModel->getItemList($itemSrlsToPurchase);
		$omittedItems = $oNproductModel->getOmittedItems($itemList);
		$omittedItemNames = $oNproductModel->getItemNames($omittedItems);
		if(count($omittedItems)) 
			return new Object(-1, sprintf(Context::getLang('msg_omitted_item_found'), count($omittedItems), implode(',', $omittedItemNames)));

		// check minimum order quantity
		$minimumOrderItems = $oNproductModel->getMinimumOrderItems($itemList);
		if(count($minimumOrderItems))
		{
			$item = array_pop($minimumOrderItems);
			return new Object(-1, $item->message);
		}

		// add items to cart
		foreach($data as $key=>$val) 
		{
			if(!$val) continue;
			if(!$val->quantity) $val->quantity = 1;
			$item_info = $oNproductModel->getItemInfo($val->item_srl);
			if(!$item_info) return new Object(-1, 'Item not found.');

			// check stock
			$stock = $oNproductModel->getItemExtraVarValue($item_info->item_srl, 'stock');
			if($stock != null && ($stock < $args->quantity || $stock == '0')) 
				return new Object(-1, sprintf(Context::getLang('msg_not_enough_stock'), $item_info->item_name));

			$output = $oNproductModel->discountItem($item_info);
			if(!$output->toBool()) return $output;
			$item_info->discount_amount = $output->discount_amount;
			$item_info->discount_info = $output->discount_info;
			$item_info->discounted_price = $output->discounted_price;

			/**
			 * 구매옵션 정보 확인
			 */
			$options = $oNproductModel->getOptions($val->item_srl);

			// 구매옵션이 있는 상품이면 구매옵션 선택 여부를 체크해야 한다.
			if (count($options) && !$val->option_srl) return new Object(-1, 'msg_select_option');

			// 기본 배송회사ID 가져오기 위해 모듈정보 읽기
			$module_srl = $item_info->module_srl;
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);

			$args->cart_srl = 0; // will be passed by $oNcartController->addItems
			$args->item_srl = $item_info->item_srl;
			$args->item_code = $item_info->item_code;
			$args->item_name = $item_info->item_name;
			$args->document_srl = $item_info->document_srl;
			$args->file_srl = $item_info->file_srl;
			$args->thumb_file_srl = $item_info->thumb_file_srl;
			$args->member_srl = 0;
			if($logged_info) $args->member_srl = $logged_info->member_srl;

			$args->module_srl = $module_srl;
			$args->quantity = $val->quantity;
			$args->price = $item_info->price;
			$args->taxfree = $item_info->taxfree;
			$args->discount_amount = $item_info->discount_amount;
			$args->discount_info = $item_info->discount_info;
			$args->discounted_price = $item_info->discounted_price;
			$args->express_id = $module_info->express_id;
			$args->option_srl = $val->option_srl;
			$args->option_price = $options[$val->option_srl]->price;
			$args->option_title = $options[$val->option_srl]->title;
			$args->module = $item_info->proc_module;

			// addItems will return $args->cart_srl
			$output = $oNcartController->addItems($args);
			if (!$output->toBool()) return $output;
			unset($args);

			$cart_srl_arr[] = $output->get('cart_srl');

			if($config->cart_on == 'N') $this->setMessage('msg_put_item_in_cart');
			$this->add('cart_on',$config->cart_on);
		}
		$this->add('cart_srl', implode(',',$cart_srl_arr));
	}

	/**
	 * @brief add items to favorite
	 */
	function procNproductAddItemsToFavorites()
	{
		$oNproductModel = &getModel('nproduct');
		$oNcartController = &getController('ncart');

		$logged_info = Context::get('logged_info');
		if (!$logged_info) return new Object(-1, 'msg_login_required');

		$item_srl = $this->getArrCommaSrls('item_srl');
		foreach($item_srl as $val)
		{
			$item_info = $oNproductModel->getItemInfo($val);
			if(!$item_info) return new Object(-1, 'Item not found.');

			$output = $oNproductModel->discountItem($item_info);
			$args = $item_info;

			$args->discount_amount = $output->discount_amount;
			$args->discount_info = $output->discount_info;
			$args->discounted_price = $output->discounted_price;
			$args->thumb_file_srl = $item_info->thumb_file_srl;
			$args->item_srl = $val;
			$args->member_srl = $logged_info->member_srl;

			$output = $oNcartController->addItemsToFavorites($args);
			if(!$output->toBool()) return $output;
		}
	}

	/**
	 * @brief insert review
	 */
	function procNproductInsertReview() 
	{
		$oStoreReviewController = &getController('store_review');
		$oNproductModel = &getModel('nproduct');

		$logged_info = Context::get('logged_info');
		if (!$logged_info) return new Object(-1, 'msg_not_permitted');
		if (!$this->grant->write_comment) return new Object(-1, 'msg_not_permitted');
		if (!$this->module_srl) return new Object(-1,'msg_invalid_request');

		$reqvars = Context::gets('item_srl','document_srl','star_point','content');

		// item info
		if ($reqvars->item_srl)
		{
			$item_info = $oNproductModel->getItemInfo($reqvars->item_srl);
		}
		else
		{
			$item_info = $oNproductModel->getItemByDocumentSrl($reqvars->document_srl);
		}

		$args->module_srl = $item_info->module_srl;
		$args->item_srl = $item_info->item_srl;
		$args->comment_srl = getNextSequence();
		$args->content = nl2br($reqvars->content);
		$args->voted_count = $reqvars->star_point;
		$review_output = $oStoreReviewController->insertReview($args);
		if(!$review_output->toBool()) return $review_output;

		// update review count
		$this->updateReviewCount($item_info->item_srl);

		// give mileage
		$config = $oNproductModel->getModuleConfig();
		if ($config->review_bonus)
			$this->giveMileage($logged_info->member_srl, $item_info->item_srl, $review_output->get('review_srl'), $config->review_bonus);

		$this->setMessage('success_registed');
		$this->setRedirectUrl(getNotEncodedUrl('', 'mid', Context::get('mid'),'act','','document_srl',$item_info->document_srl));
	}

	/**
	 * @brief insert comment
	 */
	function procNproductInsertComment() 
	{
		$oCommentController = &getController('comment');
		$oNproductModel = &getModel('nproduct');

		$logged_info = Context::get('logged_info');
		if (!$logged_info) return new Object(-1, 'msg_login_required');
		if (!$this->grant->write_comment) return new Object(-1, 'msg_not_permitted');
		if (!$this->module_srl) return new Object(-1,'msg_invalid_request');

		$reqvars = Context::gets('document_srl','comment_srl','parent_srl','content','item_srl');

		// item info
		if($reqvars->item_srl)
		{
			$item_info = $oNproductModel->getItemInfo($reqvars->item_srl);
		}
		else
		{
			$item_info = $oNproductModel->getItemByDocumentSrl($reqvars->document_srl);
		}
		if(!$item_info) return new Object(-1, 'msg_invalid_request');

		$args->module_srl = $this->module_srl;
		$args->document_srl = $item_info->document_srl;
		$args->comment_srl = getNextSequence();
		$args->parent_srl = $reqvars->parent_srl;
		$args->content = nl2br($reqvars->content);
		$output = $oCommentController->insertComment($args);
		if(!$output->toBool()) return $output;

		$this->setMessage('success_registed');
		$this->setRedirectUrl(getNotEncodedUrl('', 'mid', Context::get('mid'),'act','','document_srl',$item_info->document_srl));
	}

	/**
	 * @brief delete review
	 */
	function procNproductDeleteReview() 
	{
		$oReviewModel = &getModel('store_review');
		$oReviewController = &getController('store_review');

		if(!$this->grant->write_comment) return new Object(-1, 'msg_not_permitted');
		if(!$this->module_srl) return new Object(-1,'msg_invalid_request');

		$args = Context::gets('item_srl','review_srl');
		$args->module_srl = $this->module_srl;

		$review_srl = Context::get('review_srl');
		$oReview = $oReviewModel->getReview($review_srl);
		if(!$oReview->isExists() || !$oReview->isGranted()) return new Object(-1,'msg_invalid_request');

		$output = $oReviewController->deleteReview($oReview->review_srl);
		if(!$output->toBool()) return $output;

		$this->setRedirectUrl(getNotEncodedUrl('', 'mid', Context::get('mid'),'act','','item_srl',Context::get('item_srl')));
	}

	/**
	 * @brief delete comment
	 */
	function procNproductDeleteComment() 
	{
		$oCommentModel = &getModel('comment');
		$oCommentController = &getController('comment');

		if(!$this->grant->write_comment) return new Object(-1, 'msg_not_permitted');
		if(!$this->module_srl) return new Object(-1,'msg_invalid_request');

		$args = Context::gets('item_srl','comment_srl');
		$args->module_srl = $this->module_srl;

		$comment_srl = Context::get('comment_srl');
		$oComment = $oCommentModel->getComment($comment_srl);
		if(!$oComment->isExists() || !$oComment->isGranted()) return new Object(-1,'msg_invalid_request');

		$output = $oCommentController->deleteComment($oComment->comment_srl);
		if(!$output->toBool()) return $output;

		$this->setRedirectUrl(getNotEncodedUrl('', 'mid', Context::get('mid'),'act','','item_srl',Context::get('item_srl')));
	}

	/**
	 * @brief delete category
	 */
	function procNproductDeleteCategory() 
	{
		$oNproductModel = &getModel('nproduct');

		$node_id = Context::get('node_id');
		if(in_array($node_id, array('f.'))) 
			return new Object(-1, 'msg_cannot_delete_root');

		$category_info = $oNproductModel->getCategoryInfo($node_id);
		if(!$category_info) return new Object(-1, 'msg_invalid_request');

		$args->module_srl = Context::get('module_srl');
		$args->node_route = $category_info->node_route . $category_info->node_id . '.';
		$output = executeQuery('nproduct.getSubCategoryCount', $args);
		if(!$output->toBool()) return $output;
		if((int)$output->data->count > 0) 
			return new Object(-1, 'msg_subcategory_exist_in_category');

		unset($args);

		$args->node_route = $category_info->node_route . $category_info->node_id . '.';
		$args->page = 1;
		$output = executeQuery('nproduct.getItemsByNodeRoute', $args);
		if(!$output->toBool()) return $output;
		if($output->total_count > 0) 
			return new Object(-1, 'msg_items_exist_in_category');

		$args->node_id = $node_id;
		$output = executeQuery('nproduct.deleteCategory', $args);
		if (!$output->toBool()) return $output;

		$this->add('node_id', $node_id);
	}

	/**
	 * @brief insert options
	 */
	function procNproductInsertOptions()
	{
		$oNproductModel = &getModel('nproduct');

		$item_srl = Context::get('item_srl');
		if (!$item_srl) return new Object(-1, 'msg_invalid_request');

		$option_srls = Context::get('option_srls');
		$options_title = Context::get('options_title');
		$options_price = Context::get('options_price');

		$existing_options = $oNproductModel->getOptions($item_srl);

		foreach ($options_title as $key=>$val)
		{
			if (!$val) continue;

			$args->option_srl = $option_srls[$key];
			if (!$args->option_srl)
			{
				$args->option_srl = getNextSequence();
				$args->item_srl = $item_srl;
				$args->list_order = $args->option_srl * -1;
				$args->title = $val;
				$args->price = $options_price[$key];
				$output = executeQuery('nproduct.insertOption', $args);
				if (!$output->toBool()) return $output;
			}
			else
			{
				$args->item_srl = $item_srl;
				$args->list_order = $args->option_srl * -1;
				$args->title = $val;
				$args->price = $options_price[$key];
				$output = executeQuery('nproduct.updateOption', $args);
				if (!$output->toBool()) return $output;
				unset($existing_options[$args->option_srl]);
			}
		}

		if (count($existing_options))
		{
			$args->option_srl = array_keys($existing_options);
			$output = executeQuery('nproduct.deleteOptions', $args);
			if (!$output->toBool()) return $output;

		}

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) 
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module','admin','act','dispNproductAdminUpdateItem','module_srl',Context::get('module_srl'),'item_srl',$item_srl);
			$this->setRedirectUrl($returnUrl);
			return;
		}
	}

	/**
	 * @brief update extra vars
	 */
	function updateExtraVars($item_srl, $name, $value)
	{
		if($item_srl && $name)
		{	
			$args->item_srl = $item_srl;
			$args->name = $name;
			$output = executeQuery('nproduct.deleteNproductExtraVars', $args);
			if(!$output->toBool()) return $output;

			$args->value = $value;
			$output = executeQuery('nproduct.insertNproductExtraVars', $args);
			if(!$output->toBool()) return $output;
		}

		return new Object();
	}
}
/* End of file nproduct.controller.php */
/* Location: ./modules/nproduct/nproduct.controller.php */
