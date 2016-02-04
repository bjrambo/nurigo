<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nproductView
 * @author NURIGO(contact@nurigo.net)
 * @brief  nproductView
 */
class nproductView extends nproduct
{
	/**
	 * @brief init
	 */
	function init()
	{
		// 템플릿 경로 설정
		if($this->module_info->module != 'nproduct') $this->module_info->skin = 'default';
		if(!$this->module_info->skin) $this->module_info->skin = 'default';
		if(!$this->module_info->display_caution) $this->module_info->display_caution = 'Y';
		$this->setTemplatePath($this->module_path."skins/{$this->module_info->skin}");
		Context::set('module_info',$this->module_info);

		if(!$this->module_info->proc_module) return;
	}

	/**
	 * @brief index page
	 */
	function dispNproductIndex() 
	{
		// add translation for javascript
		Context::addHtmlHeader(sprintf("<script>
											xe.lang.msg_put_item_in_cart = '%s';
										</script>", Context::getLang('msg_put_item_in_cart')));
		
		if(Context::get('item_srl') || Context::get('document_srl'))
		{
            return $this->dispNproductItemDetail();
        }
        $this->dispNproductItemList();
	}

	/**
	 * @return post node
	 **/
	function getPostNode($node_route) 
	{
		$route_arr = preg_split('/\./', trim($node_route, '.'));
		$last = count($route_arr) - 1;
		if ($last < 0) return;
		return $route_arr[$last];
	}

	/**
	 * @brief get entire category tree
	 */
	function getEntireCategoryTree($module_srl)
	{
		// category tree
		$args->module_srl = $module_srl;
		$output = executeQueryArray('nproduct.getCategoryAllSubitems', $args);
		if (!$output->toBool()) return $output;
		$category_list = $output->data;
		$category_tree = array();
		$category_index = array();
		if ($category_list) 
		{
			foreach ($category_list as $no => $cate) 
			{
				$node_route = $cate->node_route.$cate->node_id;
				$stages = explode('.',$node_route);
				$code_str = '$category_tree["' . implode('"]["', $stages) . '"] = array();';
				eval($code_str);
				$category_index[$cate->node_id] = $cate;
			}
		}
		Context::set('entire_category_tree', $category_tree);
		Context::set('entire_category_index', $category_index);
	}

	/**
	 * @brief get category tree
	 */
	function getCategoryTree($module_srl) 
	{
		$oNproductModel = &getModel('nproduct');
		$category = Context::get('category');

		$this->getEntireCategoryTree($module_srl);
		if ($category) 
		{
			$selected_category_info = $oNproductModel->getCategoryInfo($category);
			if ($selected_category_info->node_route=='f.') $args->node_route = 'f.' . $category . '.';
			$current_node_route = $selected_category_info->node_route . $category . '.'; // . $category . '.';

			$route = preg_split('/\./', $current_node_route);
			array_shift($route);
			$selected_category_info->route = $route;

			Context::set('category_info', $selected_category_info);
			Context::set('category_depth', count($route));
		}
		else
		{
			$current_node_route = 'f.';
			$selected_category_info->node = array();
			$selected_category_info->category_name = Context::getLang('home');
			$selected_category_info->node_route = 'f.';
			Context::set('category_info', $selected_category_info);
			Context::set('category_depth', 0);
		}

		$oNproductModel->getSubcategoryCount($current_node_route);

		// has sub-nodes
		if($selected_category_info->subnode)
		{
			$node_route = $current_node_route;
			$parent_category_info = $selected_category_info;
		}
		else
		{
			// has sub-nodes
			if($oNproductModel->getSubcategoryCount($current_node_route)>0)
			{
				$node_route = $current_node_route;
				$parent_category_info = $selected_category_info;
			}
			else
			{
				// non-subnodes
				$node_route = $selected_category_info->node_route;
				$parent_node = $this->getPostNode($selected_category_info->node_route);
				if($parent_node == 'f') $parent_node = $selected_category_info->node_id;
				$parent_category_info = $oNproductModel->getCategoryInfo($parent_node);
			}
		}
		Context::set('parent_category_info', $parent_category_info);

		// get children
		$args->module_srl = $module_srl;
		$args->node_route= $node_route;
		$output = executeQueryArray('nproduct.getCategoryList', $args);
		if (!$output->toBool()) return $output;
		$category_list = $output->data;
		Context::set('category_list', $category_list);

		// get sibilings
		$args->node_route = $parent_category_info->node_route;
		$args->module_srl = $module_srl;
		$output = executeQueryArray('nproduct.getCategoryList', $args);
		if (!$output->toBool()) return $output;
		$category_list = $output->data;
		Context::set('siblings', $category_list);
	}

	/**
	 * @brief display item list
	 */
	function dispNproductItemList() 
	{
		$oNproductModel = &getModel('nproduct');
		$oStore_reviewModel = &getModel('store_review');
		$oNproductModel = &getModel('nproduct');
		$oFileModel = &getModel('file');

		$config = $oNproductModel->getModuleConfig();

		Context::set('list_config', $oNproductModel->getListConfig($this->module_info->module_srl));
		Context::set('config',$config);

		// item list
		$category = Context::get('category');
		$list_count = Context::get('disp_numb');
		if (!$list_count && $this->module_info->list_count)
		{
			$list_count = $this->module_info->list_count;
		}
		$sort_index = Context::get('sort_index');
		$order_type = Context::get('order_type');

		if (!$sort_index) $sort_index = "list_order";
		if (!$order_type) $order_type = 'asc';
		if ($category) 
		{
			$category_info = $oNproductModel->getCategoryInfo($category);
			Context::set('category_info', $category_info);

			$args->module_srl = $this->module_info->module_srl;
			$args->display='Y';
			$args->node_route = $category_info->node_route . $category_info->node_id . '.';
			$args->page = Context::get('page');
			$args->list_count = $list_count;
			$args->sort_index = $sort_index;
			$args->order_type = $order_type;
			$output = executeQueryArray('nproduct.getItemsByNodeRoute', $args);
			if (!$output->toBool()) return $output;
			$item_list = $output->data;
			Context::set('total_count', $output->total_count);
			Context::set('total_page', $output->total_page);
			Context::set('page', $output->page);
			Context::set('page_navigation', $output->page_navigation);
		} 
		else 
		{
			$args->module_srl = $this->module_info->module_srl;
			$args->display='Y';
			$args->node_route = 'f.';
			$args->page = Context::get('page');
			$args->list_count = $list_count;
			$args->sort_index = $sort_index;
			$args->order_type = $order_type;
			$output = executeQueryArray('nproduct.getItemsByNodeRoute', $args);
			if (!$output->toBool()) return $output;
			$item_list = $output->data;
			Context::set('total_count', $output->total_count);
			Context::set('total_page', $output->total_page);
			Context::set('page', $output->page);
			Context::set('page_navigation', $output->page_navigation);
		}

		$discounted = $oNproductModel->discountItems($item_list);
		foreach($item_list as $key => $item)
		{
			$review = $oStore_reviewModel->getTotalReviewInfo($item->item_srl);
			$item_list[$key]->review = $review;
		}

		Context::set('list', $discounted->item_list);

		/*
		 *  nmileage set
		 */

		$logged_info = Context::get('logged_info');
		if($logged_info)
		{
			$oNmileageModel = &getModel('nmileage');
			if($this->module_info->store_mileage_mid) Context::set('mileage_mid', $this->module_info->store_mileage_mid);

			if(!$oNmileageModel)
			{
				Context::set('nmileage', 'nmileage not installed.');
			}
			else
			{
				$nmileage = $oNmileageModel->getMileage($logged_info->member_srl);
				Context::set('nmileage', $nmileage);
			}
		}

		/* 
		 * end
		 */
		// category list
		$this->getCategoryTree($this->module_info->module_srl);
		require_once('nproduct.category.php');
		$category = new nproductCategory($this->module_info->module_srl, Context::get('category'));
		Context::set('categoryTree', $category);
		$this->setTemplateFile('itemlist');
	}

	/**
	 * @brief display item detail info
	 */
	function dispNproductItemDetail() 
	{
		if($_COOKIE['mobile'] == "true") Context::set('is_mobile', 'true');

		$oDocumentModel = &getModel('document');
		$oFileModel = &getModel('file');
		$oNproductModel = &getModel('nproduct');
		$oStoreReviewModel = &getModel('store_review');
	
		$item_srl = Context::get('item_srl');
		$document_srl = Context::get('document_srl');
		Context::set('list_config', $oNproductModel->getDetailListConfig($this->module_info->module_srl));

		// get config
		$config = $oNproductModel->getModuleConfig();
		Context::set('config',$config);

		// item info
		if ($item_srl) 
		{
			$args->item_srl = $item_srl;
		}
		else if ($document_srl) 
		{
			$args->document_srl = $document_srl;
		} 
		else 
		{
			return new Object(-1, 'Item Not Found.');
		}

		$output = executeQuery('nproduct.getItemInfo', $args);
		if (!$output->toBool()) return $output;
		$item_info = $output->data;
		// thumbnail
		if($item_info->thumb_file_srl) 
		{
			$file = $oFileModel->getFile($item_info->thumb_file_srl);
			if($file) $item_info->thumbnail_url = getFullUrl().$file->download_url;
		}

		$item_info = new nproductItem($item_info, $config->currency, $config->as_sign, $config->decimals);

		// category
		$this->getCategoryTree($this->module_info->module_srl);

		// document
		$oDocument = $oDocumentModel->getDocument($item_info->document_srl);
		Context::set('oDocument', $oDocument);

		if ($item_info->item_srl) 
		{
			$review_list = $oNproductModel->getReviews($item_info);
			Context::set('review_list', $review_list);
		}

		$output = $oNproductModel->discountItem($item_info);
		$item_info->discounted_price = $output->discounted_price;
		$item_info->discount_amount = $output->discount_amount;
		$item_info->discount_info = $output->discount_info;
		Context::set('discounted_price', $output->discounted_price);
		Context::set('discount_amount', $output->discount_amount);
		Context::set('discount_info', $output->discount_info);

		// get options
		$args->item_srl = $item_info->item_srl;
		$output = executeQueryArray('nproduct.getOptions', $args);
		Context::set('options', $output->data);

		// set browser title
		Context::setBrowserTitle(strip_tags($item_info->item_name) . ' - ' . Context::getBrowserTitle());

		// get related items information
		if($item_info->related_items)
		{
			if(!$this->isJson($item_info->related_items)) $item_info->related_items = $this->convertCsvToJson($item_info->related_items);
			$relatedItems = json_decode($item_info->related_items);
			$relatedItemSrls = array();
			foreach($relatedItems as $key => $val)
			{
				$relatedItemSrls[] = $val->item_srl;
			}
			if(count($relatedItemSrls)) $item_info->related_items = $oNproductModel->getItemList(implode(',' ,$relatedItemSrls), 999);
		}

		$trigger_output = ModuleHandler::triggerCall('nproduct.dispNproductItemDetail', 'before', $item_info);
		if(!$trigger_output->toBool()) return $trigger_output;
	
		// pass variables to html template
		Context::set('category', $item_info->category_id);
		Context::set('item_srl', $item_info->item_srl);
		Context::set('item_info', $item_info);
		$extra_vars = NExtraItemList::getList($item_info);
		Context::set('extra_vars', $extra_vars);

		$this->setTemplateFile('itemdetail');
	}

	/**
	 * @brief display replay comment
	 */
	function dispNproductReplyComment() 
	{
		$oCommentModel = &getModel('comment');

		// 권한 체크
		if(!$this->grant->write_comment) return new Object(-1,'msg_not_permitted');

		// 목록 구현에 필요한 변수들을 가져온다
		$parent_srl = Context::get('comment_srl');

		// 지정된 원 댓글이 없다면 오류
		if(!$parent_srl) return new Object(-1, 'msg_invalid_request');

		// 해당 댓글를 찾아본다
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
}
/* End of file nproduct.view.php */
/* Location: ./modules/nproduct/nproduct.view.php */
