<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nproductModel
 * @author NURIGO(contact@nurigo.net)
 * @brief  nproductModel
 */
class nproductModel extends nproduct
{
	/**
	 * @brief init
	 *
	 */
	function init() 
	{
		if (!$this->module_info->thumbnail_width) $this->module_info->thumbnail_width = 150;
		if (!$this->module_info->thumbnail_height) $this->module_info->thumbnail_height = 150;
	}

	/**
	 * @brief get module config
	 *
	 */
	function getModuleConfig()
	{
		$oModuleModel = &getModel('module');
		$config = $oModuleModel->getModuleConfig('nproduct');
		if (!$config->cart_thumbnail_width) $config->cart_thumbnail_width = 100;
		if (!$config->cart_thumbnail_height) $config->cart_thumbnail_height = 100;
		if (!$config->favorite_thumbnail_width) $config->favorite_thumbnail_width = 100;
		if (!$config->favorite_thumbnail_height) $config->favorite_thumbnail_height = 100;
		if (!$config->order_thumbnail_width) $config->order_thumbnail_width = 100;
		if (!$config->order_thumbnail_height) $config->order_thumbnail_height = 100;
		if (!$config->address_input) $config->address_input = 'krzip';

		$oCurrencyModel = &getModel('currency');
		$currency = $oCurrencyModel->getModuleConfig();
		$config->currency = $currency->currency;
		if (!$currency->currency) $config->currency = 'KRW';

		$config->as_sign = $currency->as_sign;
		if (!$currency->as_sign) $config->as_sign = 'Y';

		$config->decimals = $currency->decimals;
		if (!$currency->decimals) $config->decimals = 0;
		return $config;
	}

	/**
	 * @brief get extra item form list
	 *
	 */
	function getItemExtraFormList($module_srl, $filter_response = false) 
	{
		global $lang;
		// Set to ignore if a super administrator.
		$logged_info = Context::get('logged_info');

		$this->join_form_list = null;
		if(!$this->join_form_list) 
		{
			// Argument setting to sort list_order column
			$args->sort_index = "list_order";
			$args->module_srl = $module_srl;
			$output = executeQueryArray('nproduct.getItemExtraList', $args);
			if(!$output->toBool()) return $output;

			// NULL if output data deosn't exist
			$join_form_list = $output->data;
			if(!$join_form_list) return NULL;

			// Need to unserialize because serialized array is inserted into DB in case of default_value
			if(!is_array($join_form_list)) $join_form_list = array($join_form_list);
			$join_form_count = count($join_form_list);

			for($i=0;$i<$join_form_count;$i++) 
			{
				$join_form_list[$i]->column_name = strtolower($join_form_list[$i]->column_name);

				$extra_srl = $join_form_list[$i]->extra_srl;
				$column_type = $join_form_list[$i]->column_type;
				$column_name = $join_form_list[$i]->column_name;
				$column_title = $join_form_list[$i]->column_title;
				$default_value = $join_form_list[$i]->default_value;

				// Add language variable
				$lang->extend_vars[$column_name] = $column_title;

				// unserialize if the data type if checkbox, select and so on
				if(in_array($column_type, array('checkbox','select','radio'))) 
				{
					$join_form_list[$i]->default_value = unserialize($default_value);
					if(!$join_form_list[$i]->default_value[0]) $join_form_list[$i]->default_value = '';
				} 
				else 
				{
					$join_form_list[$i]->default_value = '';
				}

				$list[$extra_srl] = $join_form_list[$i];
			}
			$this->join_form_list = $list;
		}

		// Get object style if the filter_response is true
		if($filter_response && count($this->join_form_list)) 
		{
			foreach($this->join_form_list as $key => $val) 
			{
				if($val->is_active != 'Y') continue;
				unset($obj);
				$obj->type = $val->column_type;
				$obj->name = $val->column_name;
				$obj->lang = $val->column_title;
				$obj->required = false;
				if($logged_info->is_admin != 'Y') $obj->required = $val->required=='Y'?true:false;
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

	/**
	 * @brief get default list config
	 *
	 */
	function getDefaultListConfig($module_srl) 
	{
		$extra_vars = array();

		// 체크박스, 이미지, 상품명, 수량, 금액, 주문 추가
		$virtual_vars = array('checkbox', 'image', 'title', 'quantity', 'amount', 'cart_buttons', 'sales_count', 'download_count');
		foreach($virtual_vars as $key) 
		{
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

	/**
	 * @brief get list config
	 *
	 */
	function getListConfig($module_srl) 
	{
		$oModuleModel = &getModel('module');
		$oDocumentModel = &getModel('document');

		$extra_vars = array();

		// 저장된 목록 설정값을 구하고 없으면 빈값을 줌.
		$list_config = $oModuleModel->getModulePartConfig('nproduct', $module_srl);

		if(!$list_config || !count($list_config)) $list_config = array('checkbox', 'image', 'title', 'quantity', 'amount', 'cart_buttons', 'sales_count', 'download_count');

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

		$ret_arr = array();
		foreach($list_config as $key) 
		{
			if(array_key_exists($key, $extra_vars))
			{
				$ret_arr[$key] = $extra_vars[$key];
			}
			else
			{
				$ret_arr[$key] = new ExtraItem($module_srl, -1, Context::getLang($key), $key, 'N', 'N', 'N', null);
			}
		}

		return $ret_arr;
	}

	/**
	 * @brief get detail list config
	 *
	 */
	function getDetailListConfig($module_srl) 
	{
		$oModuleModel = &getModel('module');
		$oDocumentModel = &getModel('document');

		$extra_vars = array();

		// 저장된 목록 설정값을 구하고 없으면 빈값을 줌.
		$list_config = $oModuleModel->getModulePartConfig('nproduct.detail', $module_srl);

		if(!$list_config || !count($list_config)) $list_config = array('checkbox', 'image', 'title', 'quantity', 'amount', 'cart_buttons', 'sales_count', 'download_count');

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

		$ret_arr = array();
		foreach($list_config as $key) 
		{
			if(array_key_exists($key, $extra_vars))
			{
				$ret_arr[$key] = $extra_vars[$key];
			}
			else
			{
				$ret_arr[$key] = new ExtraItem($module_srl, -1, Context::getLang($key), $key, 'N', 'N', 'N', null);
			}
		}

		return $ret_arr;
	}
	/**
	 * @brief 확장변수 목록과 값을 취합하여 리턴
	 */
	function getCombineItemExtras(&$item_info) 
	{
		// 값을 읽어온다
		$extra_vars = new stdclass();
		$output = $this->getNproductExtraVars('', $item_info->item_srl);
		if($output)
		{
			foreach ($output as $key => $val)
			{
				$extra_vars->{$key} = $val;
			}
		}
		//
		// 변수목록을 읽어온다.
		$extend_form_list = $this->getItemExtraFormList($item_info->module_srl);
		if(!$extend_form_list) return;

		// 값 취합
		foreach($extend_form_list as $srl => $item) 
		{
			$column_name = $item->column_name;
			$value = $extra_vars->{$column_name};

			$extend_form_list[$srl]->value = $value;

			if($extra_vars->{'open_'.$column_name}=='Y') $extend_form_list[$srl]->is_opened = true;
			else $extend_form_list[$srl]->is_opened = false;
		}

		return $extend_form_list;
	}

	/**
	 * @brief get category info
	 *
	 */
	function getCategoryInfo($node_id) 
	{
		$args->node_id = $node_id;
		$output = executeQuery('nproduct.getCategoryInfo', $args);
		if (!$output->toBool()) return;
		/*
		 * nstore 
		 */
		if (!$output->data)
		{
			$args->category_srl = $node_id;
			$output = executeQuery('nproduct.getDisplayCategoryInfo', $args);
			if (!$output->toBool()) return;
		}
		return $output->data;
	}

	/**
	 * @return category info on success, return NULL on failure.
	 */
	function getCategory($node_id)
	{
		$args->node_id = $node_id;
		$output = executeQuery('nproduct.getCategoryInfo', $args);
		if (!$output->toBool()) return NULL;
		return $output->data;
	}

	/**
	 * @brief get all categories
	 *
	 */
	function getAllCategories() 
	{
		$output = executeQuery('nproduct.getAllCategories');
		if (!$output->toBool()) return;
		return $output->data;
	}

	/**
	 * @brief get item info
	 *
	 */
	function getItemInfo($item_srl) 
	{
		$config = $this->getModuleConfig();
		$args->item_srl = $item_srl;
		$output = executeQuery('nproduct.getItemInfo', $args);
		if (!$output->toBool()) return;
		$item = new nproductItem($output->data, $config->currency, $config->as_sign, $config->decimals);
		return $item;
	}

	/**
	 * @brief get item_srls.
	 * @param an array typed item list which consist of item objects.
	 * @return an array typed item_srl list.
	 */
	function getItemSrls(&$itemList)
	{
		$itemSrls = array();
		if(!$itemList) return $itemSrls;
		foreach($itemList as $item)
		{
			$itemSrls[] = $item->item_srl;
		}
		return $itemSrls;
	}

	/**
	 * @brief get item names 
	 * @return an array typed item names list.
	 */
	function getItemNames(&$itemList)
	{
		$itemNames = array();
		if(!$itemList) return $itemNames;
		foreach($itemList as $item)
		{
			$itemNames[] = $item->item_name;
		}
		return $itemNames;
	}

	/**
	 * @param $item_srl 데이터 가져올 item_srl (2개 이상일 때 array로)
	 */
	function getItemList($item_srl, $list_count=20, $sort_index='list_order') 
	{
		$config = $this->getModuleConfig();
		$args->item_srl = $item_srl;
		$args->list_count = $list_count;
		$args->sort_index = $sort_index;
		$output = executeQueryArray('nproduct.getItemList', $args);
		if (!$output->toBool()) return;
		$list = array();
		foreach($output->data as $no=>$val)
		{
			$list[] = new nproductItem($val, $config->currency, $config->as_sign, $config->decimals);
		}
		return $list;
	}

	/**
	 * @brief get item list by category id
	 */
	function getItemListByCategory($category_id, $list_count=20, $sort_index='list_order') 
	{
		$config = $this->getModuleConfig();
		$args->category_id = $category_id;
		$args->list_count = $list_count;
		$args->sort_index = $sort_index;
		$output = executeQueryArray('nproduct.getItemList', $args);
		if (!$output->toBool()) return;

		$list = array();
		foreach($output->data as $no=>$val)
		{
			$list[] = new nproductItem($val, $config->currency, $config->as_sign, $config->decimals);
		}
		return $list;
	}

	/**
	 * @brief get related item_srls
	 * @param $relatedItems is a JSON formatted item list which contains item_srl and force_purchase.
	 * @return an array of the forced items' item_srls
	 */
	function getRelatedItemSrls($relatedItems)
	{
		if(!$this->isJson($relatedItems)) $relatedItems = $this->convertCsvToJson($relatedItems);
		$retArray = array();
		if(!$relatedItems) return $retArray;
		$objList = json_decode($relatedItems);
		foreach($objList as $item)
		{
			$retArray[] = $item->item_srl;
		}
		return $retArray;
	}

	/**
	 * @brief get forced item_srls
	 * @param $relatedItems is a JSON formatted item list which contains item_srl and force_purchase.
	 * @return an array of the forced items' item_srls
	 */
	function getForcedItemSrls($relatedItems)
	{
		if(!$this->isJson($relatedItems)) $relatedItems = $this->convertCsvToJson($relatedItems);
		$retArray = array();
		if(!$relatedItems) return $retArray;
		$objList = json_decode($relatedItems);
		foreach($objList as $item)
		{
			if($item->force_purchase == 'Y') $retArray[] = $item->item_srl;
		}
		return $retArray;
	}

	/**
	 * @brief get omitted item_srls
	 * @param $itemList is an array which consists of item objects(instances of item class).
	 * @return an array typed omitted items' item_srls.
	 */
	function getOmittedItemSrls(&$itemList)
	{
		$omittedItemSrls = array();
		$itemSrlsOfPurchaseItems = $this->getItemSrls($itemList);
        foreach($itemList as $key => $item)
        {
			if(!$item->related_items) continue;
            $forcedItemSrls = $this->getForcedItemSrls($item->related_items);
            foreach($forcedItemSrls as $itemSrl)
            {
                if(!in_array($itemSrl, $itemSrlsOfPurchaseItems)) $omittedItemSrls[] = $itemSrl;
            }
        }
		return $omittedItemSrls;
	}

	/**
	 * @brief get omitted items
	 * @param $itemList is an array which consists of item objects(instances of item class).
	 * @return an array typed omitted item objects which are forced to purchase.
	 */
	function getOmittedItems(&$itemList)
	{
		$omittedItemSrls = $this->getOmittedItemSrls($itemList);
		if(!count($omittedItemSrls)) return array();
		$omittedItems = $this->getItemList($omittedItemSrls, 999);
		return $omittedItems;
	}

	/**
	 * @brief get minimum order item list
	 *        a warning messages will be added to each item.
	 * @param $itemList is an array which consists of item objects(instances of item class).
	 * @return an array tpyed item list which buyers must place orders at a minimum.
	 */
	function getMinimumOrderItems(&$itemList)
	{
		$returnArray = array();
		$itemSrlsToPurchase = $this->getItemSrls($itemList);
		foreach($itemList as $item)
		{
			$count = 0;
			$relatedItemSrls = $this->getRelatedItemSrls($item->related_items);
			if(!count($relatedItemSrls)) continue;
			foreach($relatedItemSrls as $itemSrl)
			{
				if(in_array($itemSrl, $itemSrlsToPurchase)) $count++;
			}
			if($count < $item->minimum_order_quantity)
			{
				switch($item->proc_module)
				{
					case 'elearning':
						$msg = sprintf(Context::getLang('msg_minimum_order_quantity_elearning'), $item->minimum_order_quantity);
						break;
					default:
						$msg = sprintf(Context::getLang('msg_minimum_order_quantity'), $item->minimum_order_quantity);
						break;
				}
				$returnArray[$item->item_srl] = $item;
				$returnArray[$item->item_srl]->message = $msg;
			}
		}
		return $returnArray;
	}

	/**
	 * @brief 판매중인 상품 모두 읽어오기
	 */
	function getAllValidItemList() 
	{
		$config = $this->getModuleConfig();
		$args->display = 'Y';
		$output = executeQueryArray('nproduct.getItemList', $args);
		if (!$output->toBool()) return;
		$list = $output->data;
		
		$retobj = $this->discountItems($list);
		return $list;
	}

	/**
	 * @brief 업데이트된 상품 읽어오기
	 */
	function getUpdatedItemList($updatetime) 
	{
		$config = $this->getModuleConfig();
		$args->display = 'Y';
		$args->updatetime = $updatetime;
		$output = executeQueryArray('nproduct.getItemList', $args);
		if (!$output->toBool()) return;
		$list = $output->data;
		$retobj = $this->discountItems($list);
		return $list;
	}

	function getItemByCode($item_code) {
		$config = $this->getModuleConfig();
		$args->item_code = $item_code;
		$output = executeQuery('nproduct.getItemInfo', $args);
		if (!$output->toBool()) return;
		$item = new nproductItem($output->data, $config->currency, $config->as_sign, $config->decimals);
		return $item;
	}

	/**
	 * @brief getting item information using document_srl.
	 */
	function getItemByDocumentSrl($document_srl)
	{
		$config = $this->getModuleConfig();
		$args->document_srl = $document_srl;
		$output = executeQuery('nproduct.getItemInfo', $args);
		if (!$output->toBool()) return;
		$item = new nproductItem($output->data, $config->currency, $config->as_sign, $config->decimals);
		return $item;
	}

	/**
	 * @brief 
	 */
	function getNproductItemInfos() 
	{
		$oMemberModel = &getModel('member');

		$document_srls = Context::get('document_srls');
		$logged_info = Context::get('logged_info');

		if(!$document_srls) return new Object(-1,'no srls');

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

		$document_srls = explode(',',$document_srls);

		foreach($document_srls as $k => $v)
		{
			$config = $this->getModuleConfig();
			$args->document_srl = $v;
			$output = executeQuery('nproduct.getItemInfo', $args);
			if (!$output->toBool()) return;
			$output->data->quantity = 1;
			$items[] = $output->data;
		}

		if($items) 
		{
			if($logged_info) $group_list = $oMemberModel->getMemberGroups($logged_info->member_srl);
			else $group_list = array();

			$items = $this->discountItems($items, $group_list, $image_width, $image_height);
			$this->add('data', $items);
			$this->add('module', 'nproduct');
		}
		return $items;
	}

	/**
	 * @brief get sub category count
	 */
	function getSubcategoryCount($node_route)
	{
		$args->node_route = $node_route;
		$output = executeQuery('nproduct.getSubCategoryCount', $args);
		if(!$output->toBool()) return 0;
		if($output->data) return $output->data->count;
		return 0;
	}

	/**
	 * @brief get extra vars
	 */
	function getExtraVars($module_srl)
	{
		$args->module_srl = $module_srl;
		$output = executeQueryArray('nproduct.getItemExtraList', $args);
		if (!$output->toBool()) return $output;
		$extra_list = $output->data;
		$extra_args = new StdClass();
		if ($extra_list)
		{
			foreach ($extra_list as $key=>$val)
			{
				$value = Context::get($val->column_name);
				$extra_args->{$val->column_name} = new NExtraItem($val->module_srl, $key+1, $val->column_name, $val->column_title, $val->column_type, $val->default_value, $val->description, $val->required, 'N', $value);
			}
		}
		return $extra_args;
	}

	/**
	 * @brief get discount
	 */
	function getDiscount(&$item_info)
	{
		$output = new Object();
		$output->discount_amount = $item_info->discount_amount;
		$output->discounted_price = $item_info->price;
		if($item_info->discount_amount) $output->discounted_price = $item_info->price - $item_info->discount_amount;
		$output->discount_info = $item_info->discount_info;
		return $output;
	}

	/**
	 * @brief get group discount
	 */
	function getGroupDiscount(&$item_info, $group_list) 
	{
		$args->item_srl = $item_info->item_srl;
		$output = executeQueryArray('nproduct.getGroupDiscount', $args);
		if (!$output->toBool()) return $output;
		$group_discount = $output->data;

		if (!is_array($group_discount)) $group_discount = array();
		$discounted_price = 0;
		$discount_info = "";
		foreach ($group_discount as $key => $val) 
		{
			if (array_key_exists($val->group_srl, $group_list)) 
			{
				$discount_info = $group_list[$val->group_srl];
				if ($val->opt=='2') 
				{
					$discounted_price = $item_info->price * ((100 - $val->price) / 100);
					$discount_info .= ' ' . $val->price . '% 할인';
				} else 
				{
					$discounted_price = $item_info->price - ($val->price);
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

	/**
	 * @brief get global group discount
	 */
	function getGlobalGroupDiscount($module_srl, &$item_info, $group_list) 
	{
		$args->module_srl = $module_srl;
		$output = executeQueryArray('nproduct.getGlobalGroupDiscount', $args);
		if (!$output->toBool()) return $output;
		$group_discount = $output->data;

		if (!is_array($group_discount)) $group_discount = array();
		$discounted_price = 0;
		$discount_info = "";
		foreach ($group_discount as $key => $val) {
			if (array_key_exists($val->group_srl, $group_list)) 
			{
				$discount_info = $group_list[$val->group_srl];
				if ($val->opt=='2') 
				{
					$discounted_price = $item_info->price * ((100 - $val->price) / 100);
					$discount_info .= ' ' . $val->price . '% 할인';
				} 
				else 
				{
					$discounted_price = $item_info->price - ($val->price);
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

	/**
	 * @brief get quantity discount
	 */
	function getQuantityDiscount(&$item_info, $logged_info)
	{
		if(!$logged_info) return new Object();

		$oNcartModel = &getModel($item_info->proc_module);
		$purchase_count  = 0;
		
		if(method_exists($oNcartModel, 'getPurchaseCount')) $purchase_count = $oNcartModel->getPurchaseCount($logged_info->member_srl, $item_info->item_srl);

		$args->item_srl = $item_info->item_srl;

		// check 
		$output = executeQuery('nproduct.getQuantityDiscountInfo', $args);
		if(!$output->toBool()) return $output;

		if(!$output->data) return new Object();

		$quantity_discount_data = $output->data;
		$quantity = $quantity_discount_data->quantity;
		$quantity_opt = $quantity_discount_data->opt;
		$quantity_discount = $quantity_discount_data->discount;
	
		if($purchase_count < $quantity) return new Object();

		// 구매수량이 정해준 수량을 넘으면 
		if($quantity_opt == '1')
		{
			$discounted_price = $item_info->price - $quantity_discount;
			$discount_info .= $quantity_discount . '원 할인';
		}
		else
		{
			$discounted_price = $item_info->price * ((100 - $quantity_discount) / 100);
			$discount_info .= $quantity_discount . '% 할인';
		}

		$output->discount_amount = $item_info->price - $discounted_price;
		$output->discounted_price = $discounted_price;
		$output->discount_info = $discount_info;

		return $output;
	}

	/**
	 * @brief get member discount
	 */
	function getMemberDiscount(&$item_info, $logged_info)
	{
		if(!$logged_info) return new Object();

		$args->member_srl = $logged_info->member_srl;

		// check 
		$output = executeQuery('nproduct.getMemberDiscountInfo', $args);
		if(!$output->toBool()) return $output;
		if(!$output->data) return new Object();

		$member_discount_data = $output->data;
		$member_opt = $member_discount_data->opt;
		$member_discount = $member_discount_data->discount;
	
		if($member_opt == '1')
		{
			$discounted_price = $item_info->price - $member_discount;
			$discount_info .= $member_discount . '원 할인';
		}
		else
		{
			$discounted_price = $item_info->price * ((100 - $member_discount) / 100);
			$discount_info .= $member_discount . '% 할인';
		}

		if(!$discounted_price) return new Object();

		$output->discount_amount = $item_info->price - $discounted_price;
		$output->discounted_price = $discounted_price;
		$output->discount_info = $discount_info;

		return $output;
	}

	/**
	 * @brief get discount item
	 */
	function discountItem(&$item, $group_list=null)
	{
		if(!$group_list)
		{
			$logged_info = Context::get('logged_info');
			if($logged_info)
			{
				$oMemberModel = &getModel('member');
				$group_list = $oMemberModel->getMemberGroups($logged_info->member_srl);
			}
			else
			{
				$group_list = array();
			}
		}

		// 회원별 할인
		$output = $this->getMemberDiscount($item, $logged_info);
		if(!$output->toBool()) return $output;
		if (!$output->discount_amount)
		{
			// 구매수량별 할인
			$output = $this->getQuantityDiscount($item, $logged_info);
			if(!$output->toBool()) return $output;

			if (!$output->discount_amount)
			{
				// 상품 할인
				$output = $this->getDiscount($item);
				// 상품할인이 없으면 그룹할인
				if (!$output->discount_amount)
				{
					// 그룹할인 계산
					$output = $this->getGroupDiscount($item, $group_list);
					if(!$output->toBool()) return $output;

					// 상품개별그룹할인이 없으면 글로벌그룹할인
					if(!$output->discount_amount && $item->module_srl)
					{
						$output = $this->getGlobalGroupDiscount($item->module_srl, $item, $group_list);
						if(!$output->toBool()) return $output;
					}
				}
			}
		}

		return $output;
	}

	/**
	 * @brief discount items
	 */
	function discountItems(&$item_list, $group_list=array(), $width=50, $height=50)
	{
		$oNcartModel = &getModel('ncart');

		$config = $oNcartModel->getModuleConfig();

		$ret_obj->total_price=0;
		$ret_obj->sum_price=0;
		$ret_obj->delivery_fee=0;
		$ret_obj->total_discounted_price=0;
		$ret_obj->total_discount_amount=0;
		$ret_obj->taxation_amount=0;
		$ret_obj->supply_amount=0;
		$ret_obj->taxfree_amount=0;
		$ret_obj->vat=0;
		$free_delivery = 'N';

		if(!$group_list) $group_list = array();
		$proc_modules = array();

		foreach ($item_list as $key=>$val) 
		{
			if(!in_array($val->module, $proc_modules)) $proc_modules[] = $val->module;
			$item = new nproductItem($val, $config->currency, $config->as_sign, $config->decimals);
			$item->thumbnail_url = $item->getThumbnail($width, $height);
			$item_list[$key] = $item;

			$output = $this->discountItem($val, $group_list);

			$item_list[$key]->discounted_price = $output->discounted_price;
			$item_list[$key]->discount_amount = $output->discount_amount;
			$item_list[$key]->discount_info = $output->discount_info;
			$item_list[$key]->sum_discount_amount = $output->discount_amount * $val->quantity;
			$item_list[$key]->sum_discounted_price = $output->discounted_price * $val->quantity;
			$item_list[$key]->sum_price = $val->price * $val->quantity;

			// option
			$option = FALSE;
			if ($val->option_srl)
			{
				$options = $this->getOptions($val->item_srl);
				if (isset($options[$val->option_srl]))
				{
					$option = $options[$val->option_srl];
				}
			}
			if ($option)
			{
				// 단가
				$item_list[$key]->price = $val->price + ($option->price);
				// 할인가 합계
				$item_list[$key]->sum_discounted_price += ($option->price * $val->quantity);
				// 판매가(원가격)
				$item_list[$key]->sum_price += ($option->price * $val->quantity);
			}

			$ret_obj->total_discounted_price += $item_list[$key]->sum_discounted_price;
			$ret_obj->total_discount_amount += $item_list[$key]->sum_discount_amount;
			$ret_obj->sum_price += $item_list[$key]->sum_price;
			//$ret_obj->sum_price += ($val->price * $val->quantity);

			// add currency strings
			$item_list[$key]->currency_price = $item->printPrice($item_list[$key]->price);
			$item_list[$key]->currency_discounted_price = $item->printPrice($item_list[$key]->sum_discounted_price);

			// 과세,비과세
			if ($val->taxfree=='Y') $ret_obj->taxfree_amount += $item_list[$key]->sum_discounted_price;
			else $ret_obj->taxation_amount += $item_list[$key]->sum_discounted_price;

			// item_delivery_free 에 값이 있으면 배송비 무료
            $output = $this->getItemExtraVarValue($val->item_srl, 'item_delivery_free');
            if($output) $free_delivery = 'Y';
		}

		if(in_array('nstore', $proc_modules))
		{
			if(!$config->freedeliv_amount || ($ret_obj->total_discounted_price < $config->freedeliv_amount)) $ret_obj->delivery_fee = $config->delivery_fee;
		}

        if(!$ret_obj->delivery_fee) $ret_obj->delivery_fee = 0;
        if($free_delivery == 'Y') $ret_obj->delivery_fee = 0;

		$ret_obj->total_price = $ret_obj->total_discounted_price + $ret_obj->delivery_fee;
		$ret_obj->supply_amount = round($ret_obj->taxation_amount / 1.1);
		$ret_obj->vat = $ret_obj->taxation_amount - $ret_obj->supply_amount;
		$ret_obj->item_list = $item_list;

		return $ret_obj;
	}

	/**
	 * 등록된 상품정보 목록을 가져옴
	 * @param $module_srl (필수 입력) 가져올 대상 모듈 일련번호, 2개 이상일 땐 array형식으로 넘겨줘야함
	 * @param $category_srl 가져올 대상 카테고리 일련번호 (미입력시 전체 카테고리를 대상으로 함)
	 * @param $num_columns 가져올 레코드 수
	 */
	function getFrontDisplayItems($module_srl, $category_srl=null, $num_columns=null) 
	{
		$oFileModel = &getModel('file');

		// display categories
		$args->module_srl = $module_srl;
		if ($category_srl) $args->category_srl = $category_srl;
		$output = executeQueryArray('nproduct.getDisplayCategoryList', $args);
		if(!$output->toBool()) return $output;
		$display_categories = $output->data;
		if ($display_categories) 
		{
			foreach ($display_categories as $key => $val) 
			{
				$args->category_srl = $val->category_srl;
				$args->list_count = $num_columns;
				$output = executeQueryArray('nproduct.getDisplayItems', $args);
				if (!$output->toBool()) return $output;
				$val->items = $output->data;
				if ($val->items) 
					$retobj = $this->discountItems($val->items);

				$display_categories[$key] = $val;
			}
		}
		return $display_categories;
	}

	/**
	 * @brief get node length
	 */
	function getNodeRouteLength($node_route) 
	{
		$arr = preg_split('/\./', $node_route);
		return count($arr)-1;
	}

	/**
	 * @brief get node route
	 */
	function getNodeRoute($node_route, $length) 
	{
		$route = '';
		$arr = preg_split('/\./', $node_route);
		for ($i = 0; $i < (count($arr)-1); $i++) 
		{
			$route = $route . $arr[$i] . '.';
			if ($i >= $length) break;
		}
		return $route;
	}

	/**
	 * 등록된 상품정보 목록을 가져옴
	 * @param $module_srls 가져올 대상 모듈 일련번호, 2개 이상일 땐 array형식으로 넘겨줘야함, null 일 때 전체 대상
	 * @param $category_srl 가져올 대상 카테고리 일련번호
	 * @param $maxsize 가져올 레코드 수
	 */
	function getDisplayItems($module_srls, $category_srl, $maxsize) 
	{
		$display_categories = array();
		$node_route = 'f.';
		$category_info = null;

		// get category info
		if ($category_srl)
		{
			$category_info = $this->getCategoryInfo($category_srl);
			if (!$category_info) return array();
			$node_route = $category_info->node_route . $category_info->node_id . '.';
		}

		// get items
		$items = array();

		$nr_length = $this->getNodeRouteLength($node_route);

		for ($i = $nr_length-1; $i >= 0; $i--) 
		{
			$args->module_srl = $module_srls;
			$args->node_route = $this->getNodeRoute($node_route, $i);
			$args->list_count = $maxsize;
			$output = executeQueryArray('nproduct.getDisplayItems', $args);
			if(!$output->toBool()) return $output;

			$tmp_items = $output->data;
			if ($tmp_items) 
			{
				foreach ($tmp_items as $key=>$val) 
				{
					//$items[$val->item_srl] = new nstore_coreItem($val);
					$items[$val->item_srl] = $val;
					if (count($items) >= $maxsize) break;
				}
			}
			if (count($items) >= $maxsize) break;
		}
		$retobj = $this->discountItems($items);
		$category_info->items = $items;

		$display_categories[] = $category_info;
		return $display_categories;
	}

	/**
	 * @brief get review count
	 */
	function getReviewCount() 
	{
		return 1;
	}

	/**
	 * @brief get reviews
	 */
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
		foreach($output->data as $key => $val) 
		{
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

	/**
	 * @brief get options
	 */
	function getOptions($item_srl)
	{
		$args->item_srl = $item_srl;
		$output = executeQueryArray('nproduct.getOptions', $args);
		$options = array();
		if (!$output->data) return $options;
		foreach ($output->data as $key=>$val)
		{
			$options[$val->option_srl] = $val;
		}
		return $options;
	}

	/**
	 * @brief get category list
	 */
	function getNproductCategoryList() 
	{
		$module_srl = Context::get('module_srl');
		$node_id = Context::get('node_id');

		$logged_info = Context::get('logged_info');
		if (!$logged_info) return new Object(-1, 'msg_login_required');

		$data = array();
		if ($node_id=='root') 
		{
			$obj = new StdClass();
			$obj->attr = new StdClass();
			$obj->attr->id = 'f.';
			$obj->attr->node_id = 'f.';
			$obj->attr->node_name = Context::getLang('category');
			$obj->attr->node_route = '';
			$obj->attr->subfolder = '';
			$obj->attr->subnode = '';
			$obj->attr->rel = 'root';
			$obj->state = 'closed';
			$obj->data = Context::getLang('category');
			$data[] = $obj;
			$this->add('data', $data);
			return;
		}

		// get node_route
		switch ($node_id) 
		{
			case "f.":
			case "t.":
			case "s.":
				$node_route = $node_id;
				break;
			default:
				if ($node_id) 
				{
					//$args->user_id = $logged_info->user_id;
					$args->node_id = $node_id;
					$output = executeQuery('nproduct.getCategoryInfo', $args);
					if (!$output->toBool()) return $output;
					$node_route = $output->data->node_route . $node_id . '.';
					$user_id = $output->data->user_id;
				} 
				else 
				{
					$node_route = 'f.';
				}
				break;
		}

		unset($args);
		$args->module_srl = $module_srl;
		$args->node_route = $node_route;
		$output = executeQueryArray('nproduct.getCategoryList', $args);
		if ($output->data) 
		{
			foreach ($output->data as $no => $val) 
			{
				$obj = new StdClass();
				$obj->attr = new StdClass();
				$obj->attr->id = $val->node_id;
				$obj->attr->node_id = $val->node_id;
				$obj->attr->node_name = $val->category_name;
				$obj->attr->node_route = $val->node_route;
				$obj->attr->subfolder = '';
				$obj->attr->subnode = '';
				$obj->attr->rel = 'folder';
				$obj->state = 'closed';
				$obj->data = $val->category_name;
				$data[] = $obj;
			}
		}
		$this->add('data', $data);
	}

	/**
	 * @brief category info.
	 **/
	function getNproductCategoryInfo() 
	{
		$logged_info = Context::get('logged_info');
		if (!$logged_info) return new Object(-1, 'msg_login_required');

		$node_id = Context::get('node_id');

		if ($node_id == 'f.') 
		{
			$category_info->node_id = 'f.';
			$category_info->node_route = 'f.';
			$category_info->node_route_text = Context::getLang('category');
			$category_info->category_name = Context::getLang('category');
			$category_info->regdate = '';
		} 
		else 
		{
			$args->node_id = $node_id;
			$output = executeQuery('nproduct.getCategoryInfo', $args);
			if (!$output->toBool()) return $output;
			$category_info = $output->data;
		}
		$this->add('data', $category_info);
	}

	/**
	 * @brief get display items
	 */
	function getNproductDisplayItems() 
	{
		$category_srl = Context::get('category_srl');
		$args->category_srl = $category_srl;
		$output = executeQueryArray('nproduct.getDisplayItems', $args);
		$this->add('data', $output->data);
	}

	/**
	 * @brief get extra vars
	 */
	function getNproductExtraVars($module_name=null, $item_srl=null)
	{
		$oModel = &getModel($module_name);
		if(!$module_name && !$item_srl) return;

		if($item_srl)
		{
			$args->item_srl = $item_srl;
			$output = executeQueryArray("nproduct.getNproductExtraVars",$args);
			if(!$output->toBool()) return $output;
			if($output->data) 
			{
				foreach($output->data as $k => $v)
				{
					$extra_values[$v->name] = $v->value;
				}
				Context::set('extra_values', $extra_values);
			}
			if(!$module_srl) return $extra_values;
		}

		if($module_name)
		{
			$output = $oModel->getNproductExtraVars();
			return $output;
		}
	}

	/**
	 * @brief get input extra vars
	 */
	function getNproductInputExtraVars($extra_info)
	{
		$extend_form_list = $extra_info;

		$formTags = array();
		if(!$extend_form_list) 
		{
			return $formTags;
		}

		foreach ($extend_form_list as $no=>$formInfo) 
		{
			unset($formTag);
			$inputTag = '';
			$formTag = $formInfo;
			$formTag->title = $formInfo->column_title;
			if($formInfo->required=='Y') 
				$formTag->title = $formTag->title.' <em style="color:red">*</em>';

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
					foreach($extendForm->default_value as $v)
					{
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
					foreach($extendForm->default_value as $v)
					{
						if($v == $extendForm->value) 
						{
							$selected = 'selected="selected"';
						}
						else $selected = '';
						$optionTag[] = sprintf('<option value="%s" %s >%s</option>' ,$v ,$selected ,$v);
					}
				}
				$template = sprintf($template, implode('', $optionTag));
			}
			else if($extendForm->column_type == 'date')
			{
				$extentionReplace = array('date' => zdate($extendForm->value, 'Y-m-d'),
										  'cmd_delete' => $lang->cmd_delete);
				$template = '<input type="hidden" name="%column_name%" id="date_%column_name%" value="%value%" /><input type="text" class="inputDate" value="%date%" readonly="readonly" /> <input type="button" value="%cmd_delete%" class="dateRemover" />';
			}
			else if($extendForm->column_type == 'file')
			{
				$oFileModel = &getModel('file');
				if($extendForm->value)
				{
					$file = $oFileModel->getFile($extendForm->value);
					$template = '<p><a href="'.$file->download_url.'">'.$file->source_filename.'</a> ('.FileHandler::filesize($file->file_size).')</p>';
				}
				$template .= '<input type="file" name="%column_name%" />';
			}

			$replace = array_merge($extentionReplace, $replace);
			$inputTag = preg_replace('@%(\w+)%@e', '$replace[$1]', $template);

			$formTag->inputTag = $inputTag;
			$formTags[] = $formTag;
		}

		return $formTags;
	}

	/**
	 * @brief 해당 아이템(상품)의 확장변수 값을 리턴한다.
	 * @param $item_srl 아이템(상품) 일련번호
	 * @return array 형의 key = value
	 */
	function getItemExtraVarList($item_srl)
	{
		$args->item_srl = $item_srl;
		$output = executeQueryArray("nproduct.getNproductExtraVars",$args);
		if(!$output->toBool()) return $output;

		if($output->data) 
		{
			foreach($output->data as $k => $v)
			{
				$extra_values[$v->name] = $v->value;
			}
			return $extra_values;
		}
	}

	/**
	 * @brief 아이템 확장변수 값을 찾아서 리턴
	 * @param $item_srl 아이템(상품) 일련번호, $var_name 리턴할 변수명
	 * @return string형의 값
	 */
	function getItemExtraVarValue($item_srl, $var_name)
	{
		if(isset($GLOBALS["item_extra_var_list".$item_srl]))
		{
			$item_extra_var_list = $GLOBALS["item_extra_var_list".$item_srl];
		}
		else
		{
			$item_extra_var_list = $this->getItemExtraVarList($item_srl);
			$GLOBALS["item_extra_var_list".$item_srl] = $item_extra_var_list;
		}
		if(isset($item_extra_var_list[$var_name])) return $item_extra_var_list[$var_name];
		return null;
	}

	/**
	 * @brief get extra item by module srl
	 */
	function getItemExtraByModuleSrl($module_srl)
	{
		$args->module_srl = $module_srl;
		$output = executeQueryArray('nproduct.getItemExtraList', $args);
		if($output->data) return $output->data;
		else return;
	}

	/**
	 * @brief get item srl by item name
	 */
	function getItemSrlByItemName($item_name, $module_srl)
	{
		$args->item_name = $item_name;
		$args->module_srl = $module_srl;
		$output = executeQuery('nproduct.getItemInfoByItemName', $args);
		return $output;
	}

	/**
	 * @brief get proc modules
	 */
	function getProcModules()
	{
        // 상품타입 정보 가져오기
        $module_list = array();
        $output = ModuleHandler::triggerCall('nproduct.getProcModules', 'before', $module_list);
        if(!$output->toBool()) debugPrint($output);
		return $module_list;
	}

	/**
	 * @brief get item list by item_srls
	 */
	function getNproductItems()
	{
		$item_srls = Context::get('item_srls');
		$itemList = array();
		$data = array();
		if($item_srls) $itemList = $this->getItemList($item_srls, 999);
		foreach($itemList as $key => $val)
		{
			$obj = new stdClass();
			$obj->item_srl = $val->item_srl;
			$obj->item_name = $val->item_name;
			$data[] = $obj;
		}
		$this->add('data', $data);
	}

	/**
	 * @brief get JSON formatted category list
	 */
	function getNproductCategoryListJson() 
	{
		// get parameters
		$node_id = Context::get('node_id');
		$is_page = Context::get('is_page');

		// initialize variables
		$category_id = 0;

		$logged_info = Context::get('logged_info');
		if (!$logged_info) return new Object(-1, 'msg_login_required');

		$data = array();
		if ($node_id=='root') 
		{
			// get module instance list
			$args->list_count = 1000;
			$output = executeQueryArray('nproduct.getModInstList', $args);
			$list = $output->data;
			foreach($list as $element) 
			{
				$obj = new StdClass();
				$obj->id = $element->module_srl;
				$obj->text = $element->browser_title;
				$obj->state = new stdClass();
				$obj->state->closed = TRUE;
				$obj->state->disabled = TRUE;
				$obj->li_attr = new stdClass();
				$obj->li_attr->is_page = TRUE;
				$obj->a_attr = new stdClass();
				$obj->a_attr->class = "_nodeType_1";
				$obj->children = TRUE;
				$data[] = $obj;
			}
			echo json_encode($data);
			exit();
		}

		unset($args);
		if($is_page)
		{
			$args->node_route = 'f.';
			$args->module_srl = $node_id;
		}
		else
		{
			$categoryInfo = $this->getCategory($node_id);
			if(!$categoryInfo) return new Object(-1, 'category not found');
			$args->node_route = $categoryInfo->node_route . $node_id . '.';
			$category_id = $node_id;
		}

		$output = executeQueryArray('nproduct.getAllCategories', $args);
		if ($output->data) 
		{
			foreach ($output->data as $no => $val) 
			{
				$obj = new StdClass();
				$obj->id = $val->node_id;
				$obj->text = $val->category_name;
				$obj->state = new stdClass();
				$obj->state->closed = TRUE;
				$obj->state->disabled = TRUE;
				$obj->a_attr = new stdClass();
				$obj->a_attr->class = "_nodeType_1";
				$obj->children = TRUE;
				$data[] = $obj;
			}
		}
		$itemList = $this->getItemListByCategory($category_id, 9999);
		foreach($itemList as $key => $val)
		{
			$obj = new stdClass();
			$obj->id = $val->item_srl;
			$obj->text = $val->item_name;
			$obj->state = $val->closed;
			$obj->icon = 'jstree-file';
			$obj->a_attr = new stdClass();
			$obj->a_attr->item_srl = $val->item_srl;
			$data[] = $obj;
		}
		echo json_encode($data);
		exit();
	}

	/**
	 * @brief return module name in sitemap
	 **/
	function triggerModuleListInSitemap(&$obj)
	{
		array_push($obj, 'nproduct');
	}
}
/* End of file nproduct.model.php */
/* Location: ./modules/nproduct/nproduct.model.php */
