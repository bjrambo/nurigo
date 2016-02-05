<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nproductAdminController
 * @author NURIGO(contact@nurigo.net)
 * @brief  nproductAdminController
 */
class nproductAdminController extends nproduct
{
	/**
	 * @brief insert module config
	 **/
	function procNproductAdminConfig() 
	{
		$oModuleControll = getController('module');
		$args = Context::getRequestVars();
		
		// save module configuration.
		$output = $oModuleControll->insertModuleConfig('nproduct', $args);
		$this->setMessage('success_updated');

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) 
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispNproductAdminConfig','module_srl',Context::get('module_srl'));
			$this->setRedirectUrl($returnUrl);
		}
	}

	/**
	 * @brief 모듈 환경설정값 쓰기
	 **/
	function procNproductAdminInsertModInst() 
	{
		// module 모듈의 model/controller 객체 생성
		$oModuleController = &getController('module');
		$oModuleModel = &getModel('module');
		$oNproductModel = &getModel('nproduct');

		// 게시판 모듈의 정보 설정
		$args = Context::getRequestVars();
		$args->module = 'nproduct';
		$extra_var_info = $args;

		// module_srl이 넘어오면 원 모듈이 있는지 확인
		if($args->module_srl) 
		{
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);
			if($module_info->module_srl != $args->module_srl)
			{
				unset($args->module_srl);
			}
		}

		// module_srl의 값에 따라 insert/update
		if(!$args->module_srl) 
		{
			$output = $oModuleController->insertModule($args);
			$msg_code = 'success_registed';

			$extra_module_info = $oModuleModel->getModuleInfoByMid($extra_var_info->mid);
			$default_extra_forms = $oNproductModel->getNproductExtraVars($extra_var_info->proc_module);
			if($default_extra_forms)
			{
				foreach($default_extra_forms as $k=>$v)
				{
					$extra->module_srl = $extra_module_info->module_srl;
					$extra->column_type = $v->column_type;
					$extra->column_name = $v->column_name;
					$extra->column_title = $v->column_title;
					$extra->default_value = explode("\n", str_replace("\r", '',$v->default_value));
					$extra->required = $v->required;
					$extra->is_active = (isset($extra->required));
					$extra->description = $v->description;

					$this->insertItemExtra($extra);
					unset($extra);
				}
			}
		}
		else
		{
			$output = $oModuleController->updateModule($args);
			$msg_code = 'success_updated';
		}

		if(!$output->toBool()) return $output;

		
		$this->add('module_srl',$output->get('module_srl'));
		$this->setMessage($msg_code);

		$returnUrl = getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispNproductAdminInsertModInst','module_srl',$output->get('module_srl'));
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * @brief delete module instance
	 **/
	function procNproductAdminDeleteModInst()
	{
		$oModuleController = &getController('module');

		$module_srl = Context::get('module_srl');
		$output = $oModuleController->deleteModule($module_srl);
		if(!$output->toBool()) return $output; 

		$this->add('module', 'nproduct');
		$this->add('page', Context::get('page'));
		$this->setMessage('success_deleted');

		$returnUrl = getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispNproductAdminModInstList');
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * @brief insert global group discount
	 **/
	function procNproductAdminGroupDiscount()
	{
		$oMemberModel = &getModel('member');

		$module_srl = Context::get('module_srl');
		// update group discount
		$args->module_srl = $module_srl;
		$output = executeQuery('nproduct.deleteGlobalGroupDiscount', $args);
		if (!$output->toBool()) return $output;
		unset($args);

		$group_list = $oMemberModel->getGroups();
		foreach ($group_list as $key=>$val)
		{
			if (Context::get('group_discount_'.$val->group_srl))
			{
				$opt = Context::get('group_opt_'.$val->group_srl);
				if (!$opt) $opt='1';
				$args->module_srl = $module_srl;
				$args->group_srl = $val->group_srl;
				$args->opt = $opt;
				$args->price = Context::get('group_discount_'.$val->group_srl);
				$output = executeQuery('nproduct.insertGlobalGroupDiscount', $args);
				if (!$output->toBool()) return $output;
				unset($args);
			}
		}

		$this->setMessage('success_updated');
		$returnUrl = getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispNproductAdminGroupDiscount','module_srl',$module_srl);
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * @brief insert display category
	 **/
	function procNproductAdminInsertDisplayCategory() 
	{
		$args = Context::gets('category_srl','module_srl','category_name','thumbnail_width','thumbnail_height','num_columns','num_rows');
		if(!$args->category_srl) 
		{
			$args->category_srl = getNextSequence();
			$args->list_order = $args->category_srl;
			$output = executeQuery('nproduct.insertDisplayCategory', $args);
			if(!$output->toBool()) return $output;
		} 
		else 
		{
			$output = executeQuery('nproduct.updateDisplayCategory', $args);
			if(!$output->toBool()) return $output;
		}
		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) 
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispNproductAdminDisplayCategories','module_srl',Context::get('module_srl'));
			$this->setRedirectUrl($returnUrl);
			return;
		}
	}

	/**
	 * @brief delete display category
	 **/
	function procNproductAdminDeleteDisplayCategory() 
	{
		$category_srl = Context::get('category_srl');
		$args->category_srl = $category_srl;
		$output = executeQuery('nproduct.deleteDisplayCategory', $args);
		if(!$output->toBool()) return $output;

		$args->category_srl = $category_srl;
		$output = executeQuery('nproduct.deleteDisplayItems', $args);
		if(!$output->toBool()) return $output;
	}

	/**
	 * @brief delete extra item
	 **/
	function procNproductAdminDeleteItemExtra() 
	{
		$oNproductModel = &getModel('nproduct');
		$module_srl = Context::get('module_srl');
		
		//DB 삭제부분
		$args->extra_srl = Context::get('extra_srl');
		$output = executeQuery('nproduct.deleteItemExtra', $args);
		if(!$output->toBool()) return $output;
		
		//dynamic ruleset 재생성
		$extra_vars = $oNproductModel->getItemExtraFormList($module_srl);
		$this->_createInsertItemRuleset($extra_vars);

		$this->setMessage('success_deleted');
		
		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) 
		{
			$this->setRedirectUrl(getNotencodedUrl("","module","admin","act","dispNproductAdminItemExtraSetup","module_srl",$module_srl));
		}
	}

	/**
	 * @brief insert display item
	 **/
	function procNproductAdminInsertDisplayItem() 
	{
		$oNstore_coreModel = &getModel('nproduct');

		$module_srl = Context::get('module_srl');
		$item_srl = Context::get('item_srl');
		$category_srl = Context::get('category_srl');
		if(!$item_srl) return new Object(-1,'msg_no_items');

		$item_srl = explode(',', $item_srl);
		$args->item_srl = $item_srl;
		$args->module_srl = $module_srl;

		// get node_route
		$category_info = $oNstore_coreModel->getCategoryInfo($category_srl);
		if(!$category_info)
		{
			$args->category_srl = 0;
			$args->node_route = 'f.';
		}
		else
		{
			$args->category_srl = $category_srl;
			$args->node_route = $category_info->node_route . $category_info->node_id . '.';
		}
		
		foreach($item_srl as $key => $val)
		{
			$args->item_srl = $val;
			$output = executeQuery('nproduct.insertDisplayItem', $args);
		}
		if(!$output->toBool()) return $output;
	}

	/**
	 * @brief delete display item
	 **/
	function procNproductAdminDeleteDisplayItem() 
	{
		$args->category_srl = Context::get('category_srl');
		$args->item_srl = Context::get('item_srl');
		$output = executeQuery('nproduct.deleteDisplayItem', $args);
		if(!$output->toBool()) return $output;
	}

	/**
	 * update display category list order
	 */
	function procNproductAdminUpdateDCListOrder() 
	{
		$order = Context::get('order');
		parse_str($order);
		$idx = 1;
		if(!is_array($record)) return;

		foreach ($record as $category_srl) 
		{
			$args->category_srl = $category_srl;
			$args->list_order = $idx;
			$output = executeQuery('nproduct.updateDisplayCategoryListOrder', $args);
			if (!$output->toBool()) return $output;
			$idx++;
		}
	}

	/**
	 * update display item list order
	 */
	function procNproductAdminUpdateDIListOrder() 
	{
		$order = Context::get('order');
		parse_str($order);
		$idx = 1;
		if(!is_array($record)) return;

		foreach ($record as $item_srl) 
		{
			$args->item_srl = $item_srl;
			$args->list_order = $idx;
			$output = executeQuery('nproduct.updateDisplayItemListOrder', $args);
			if(!$output->toBool()) return $output;
			$idx++;
		}
	}

	/**
	 * @brief update extra item order
	 **/
	function procNproductAdminUpdateItemExtraOrder() 
	{
		$order = Context::get('order');
		parse_str($order);
		$idx = 1;
		if(!is_array($record)) return;

		foreach ($record as $extra_srl) 
		{
			$args->extra_srl = $extra_srl;
			$args->list_order = $idx;
			$output = executeQuery('nproduct.updateItemExtraOrder', $args);
			if(!$output->toBool()) return $output;
			$idx++;
		}
	}

	/**
	 * @brief update item list order
	 **/
	function procNproductAdminUpdateItemListOrder() 
	{
		$order = Context::get('order');
		parse_str($order);
		$idx = 1;
		if(!is_array($record)) return;

		foreach ($record as $item_srl) 
		{
			$args->item_srl = $item_srl;
			$args->list_order = $idx;
			$output = executeQuery('nproduct.updateItemListOrder', $args);
			if(!$output->toBool()) return $output;
			$idx++;
		}
	}

	/**
	 * @brief update display category
	 **/
	function procNproductAdminUpdateDisplayCategory() 
	{
		$args->category_srl = Context::get('category_srl');
		$args->category_name = Context::get('category_name');
		$args->thumbnail_width = Context::get('thumbnail_width');
		$args->thumbnail_height = Context::get('thumbnail_height');
		$args->num_columns = Context::get('num_columns');
		$args->num_rows = Context::get('num_rows');
		return executeQuery('nproduct.updateDisplayCategory', $args);
	}


	/**
	 * @brief insert extra item
	 **/
	function insertItemExtra($args)
	{
		$oNproductModel = &getModel('nproduct');
		// Default values
		$args->default_value = '';
		if(in_array($args->column_type, array('checkbox','select','radio')) && count($args->default_value) ) 
		{
			$args->default_value = serialize($args->default_value);
		} 

		// Update if extra_srl exists, otherwise insert.
		$isInsert;
		if(!$args->extra_srl)
		{
			$isInsert = true;
			$args->list_order = $args->extra_srl = getNextSequence();
			$output = executeQuery('nproduct.insertItemExtra', $args);
			$this->setMessage('success_registed');
		}
		else
		{
			$output = executeQuery('nproduct.updateItemExtra', $args);
			$this->setMessage('success_updated');
		}

		if(!$output->toBool()) return $output;

		// create dynamic ruleset
		$extra_vars = $oNproductModel->getItemExtraFormList($args->module_srl);
		$this->_createInsertItemRuleset($extra_vars);
	}

	/**
	 * @brief 추가등록폼 필드가 DB필드 혹은 이미추가된 항목가 중복되는지 체크
	 * @return 중복되면 TRUE, 아니면 FALSE
	 */
	function checkColumnName($module_srl, $column_name)
	{
		// check in reserved keywords
		if(in_array($column_name, array('module','act','module_srl','category_id', 'document_srl','description', 'delivery_info', 'item_srl','proc_module','category_depth1','category_depth2','category_depth3','category_depth4','thumbnail_image','contents_file'))) return TRUE;
		// check in extra keys
		$args->module_srl = $module_srl;
		$args->column_name = $column_name;
		$output = executeQuery('nproduct.isExistsExtraKey', $args);
		if($output->data->count) return TRUE;
		// check in db fields
		$oDB = &DB::getInstance();
		if($oDB->isColumnExists('nproduct_items', $column_name)) return TRUE;
		return FALSE;
	}

	/**
	 * @brief insert extra item
	 **/
	function procNproductAdminInsertItemExtra() 
	{
		$oNstore_coreModel = &getModel('nproduct');

		$args->module_srl = Context::get('module_srl');
		$args->extra_srl = Context::get('extra_srl');
		$args->column_type = Context::get('column_type');
		$args->column_name = strtolower(Context::get('column_name'));
		$args->column_title = Context::get('column_title');
		$args->default_value = explode("\n", str_replace("\r", '', Context::get('default_value')));
		$args->required = Context::get('required');
		$args->is_active = (isset($args->required));
		if(!in_array(strtoupper($args->required), array('Y','N')))
		{
			$args->required = 'N';
		}
		$args->description = Context::get('description');

		// check column_name
		if(!$args->extra_srl && $this->checkColumnName($args->module_srl, $args->column_name)) return new Object(-1, 'msg_invalid_column_name');

		$this->insertItemExtra($args);

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) 
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispNproductAdminItemExtraSetup');
			$this->setRedirectUrl($returnUrl);
			return;
		}
	}

	/**
	 * @brief insert delivery info
	 **/
	function procNproductAdminInsertDeliveryInfo() 
	{
		$args->module_srl = Context::get('module_srl');
		$args->item_srl = Context::get('item_srl');
		$args->delivery_info = Context::get('delivery_info');
		// update if item_srl exists, otherwise return error.
		if(!$args->item_srl)
		{
			return new Object(-1, 'msg_invalid_item');
		}
		else
		{
			$output = executeQuery('nproduct.updateItem', $args);
			if(!$output->toBool()) return $output;
		}

		$this->setMessage('success_registed');

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) 
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispNproductAdminUpdateItem');
			$this->setRedirectUrl($returnUrl);
			return;
		}
	}

	/**
	 * @brief delete item
	 **/
	function procNproductAdminDeleteItem() 
	{
		$oDocumentController = &getController('document');
		$oFileController = &getController('file');
		$oNproductModel = &getModel('nproduct');
		$oStore_reviewAdminController = &getAdminController('store_review');

		// get item info.
		$item_srl = Context::get('item_srl');
		$item_info = $oNproductModel->getItemInfo($item_srl);
		if(!$item_info) return new Object(-1, 'msg_invalid_request');

		// Call a trigger (before)
		$output = ModuleHandler::triggerCall('nproduct.deleteItem', 'before', $item_info);
		if(!$output->toBool()) return $output;

		// delete thumbnail file
		$oFileController->deleteFile($item_info->file_srl);

		// delete review
		$output = $oStore_reviewAdminController->deleteReviewList($item_srl);
		if(!$output->toBool()) return $output;
		
		// delete document
		$oDocumentController->deleteDocument($item_info->document_srl);
		
		// delete db record
		$args->item_srl = $item_srl;
		$output = executeQuery('nproduct.deleteItem', $args);
		if(!$output->toBool()) return $output;

		$output = executeQuery('nproduct.deleteNproductExtraVars', $args);
		if(!$output->toBool()) return $output;

		// Call a trigger (after)
		$output = ModuleHandler::triggerCall('nproduct.deleteItem', 'after', $item_info);
		if(!$output->toBool()) return $output;
		
		$this->setMessage('success_deleted');

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON')))
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispNproductAdminItemList','module_srl',Context::get('module_srl'));
			$this->setRedirectUrl($returnUrl);
			return;
		}
	}

	/**
	 * @brief update item list
	 **/
	function procNproductAdminUpdateItemList() 
	{
		$oNproductController = &getController('nproduct');
		$oNproductModel = &getModel('nproduct');
		$oModuleModel = &getModel('module');

		$item_srls = Context::get('item_srls');
		$item_name_arr = Context::get('item_name');
		$taxfree_arr = Context::get('taxfree');
		$display_arr = Context::get('display');
		$list_order_arr = Context::get('list_order');
		$item_code_arr = Context::get('item_code');
		$price_arr = Context::get('price');
		$cart = Context::get('cart');

		$updated_itemlist = array();
		$args->module_srl = Context::get('module_srl');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);
		$args->proc_module = $module_info->proc_module;

		if(count($item_srls))
		{
			foreach ($item_srls as $key=>$item_srl) 
			{

				$args->item_srl = $item_srl;
				$args->item_name = $item_name_arr[$key];
				$args->taxfree = $taxfree_arr[$key];
				$args->display = $display_arr[$key];
				$args->item_code = $item_code_arr[$key];
				$args->list_order = $list_order_arr[$key];
				$args->price = $price_arr[$key];
				
				if(!$item_srl) 
				{
					// inert
					$output = $oNproductController->insertItem($args);
					if(!$output->toBool()) return $output;
				} 
				else 
				{
					// update
					$item_info = $oNproductModel->getItemInfo($item_srl);
					$update_flag = FALSE;
					if($args->item_name != $item_info->item_name) $update_flag = TRUE;
					if($args->taxfree != $item_info->taxfree) $update_flag = TRUE;
					if($args->display != $item_info->display) $update_flag = TRUE;
					if($args->item_code != $item_info->item_code)  $update_flag = TRUE;
					if($args->price != $item_info->price) $update_flag = TRUE;
					if($update_flag) $args->updatetime = date('YmdHis');
					if($args->list_order != $item_info->list_order) $update_flag = TRUE;
					if($update_flag)
					{
						$output = executeQuery('nproduct.updateItem', $args);
						if(!$output->toBool()) return $output;
						$updated_itemlist[] = $item_info;
					}
				}
			}
		}

		// after
		$output = ModuleHandler::triggerCall('nproduct.updateItem', 'after', $updated_itemlist);
		if (!$output->toBool()) return $output;

		$this->setMessage('success_updated');
		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) 
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module',Context::get('module')
				,'act', 'dispNproductAdminItemList'
				,'module_srl',Context::get('module_srl')
				,'category',Context::get('category')
				,'s_item_name',Context::get('s_item_name'));
			$this->setRedirectUrl($returnUrl);
			return;
		}
	}

	/**
	 * @brief insert detail list config
	 **/
	function insertDetailListConfig()
	{
		$oModuleController = &getController('module');

		$module_srl = Context::get('module_srl');
		$list = explode(',',Context::get('list'));
		if(!count($list)) return new Object(-1, 'msg_invalid_request');

		$list_arr = array();
		foreach($list as $val) 
		{
			$val = trim($val);
			if(!$val) continue;

			if(substr($val,0,10)=='extra_vars') $val = substr($val,10);
			$list_arr[] = $val;
		}

		$output = $oModuleController->insertModulePartConfig('nproduct.detail', $module_srl, $list_arr);
		if(!$output->toBool()) return $output;
		return new Object();
	}

	/**
	 * @brief insert list config
	 **/
	function insertListConfig()
	{
		$oModuleController = &getController('module');

		$module_srl = Context::get('module_srl');
		$list = explode(',',Context::get('list'));
		if(!count($list)) return new Object(-1, 'msg_invalid_request');

		$list_arr = array();
		foreach($list as $val) 
		{
			$val = trim($val);
			if(!$val) continue;

			if(substr($val,0,10)=='extra_vars') $val = substr($val,10);
			$list_arr[] = $val;
		}

		$output = $oModuleController->insertModulePartConfig('nproduct', $module_srl, $list_arr);
		if(!$output->toBool()) return $output;
		return new Object();
	}

	/**
	 * @brief 상품 목록 설정 저장
	 */
	function procNproductAdminInsertListConfig()
	{
		$output = $this->insertListConfig();
		if(!$output->toBool()) return $output;
		$this->setMessage('success_registed');
		$this->setRedirectUrl(Context::get('success_return_url'));
	}

	/**
	 * @brief 상품상세 목록 설정 저장
	 */
	function procNproductAdminInsertDetailListConfig()
	{
		$output = $this->insertDetailListConfig();
		if(!$output->toBool()) return $output;
		$this->setMessage('success_registed');
		$this->setRedirectUrl(Context::get('success_return_url'));
	}

	/**
	 * @brief insert item
	 **/
	function procNproductAdminInsertItem() 
	{
		$oFileController = &getController('file');
		$oNproductController = &getController('nproduct');
		$oNproductModel = &getModel('nproduct');

		$args = Context::getRequestVars();
		$args_check = $args;

		// before
		$output = ModuleHandler::triggerCall('nproduct.insertItem', 'before', $args);
		if (!$output->toBool()) return $output;

		/*
		 *  save item info , get item_srl
		 */
		$output = $oNproductController->insertItem($args);
		if (!$output->toBool()) return $output;

		$item_srl = $output->get('item_srl');
		$this->add('item_srl', $item_srl);

		if($item_stock > 0) $args->item_stock = $iem_stock;

		/*
		 *  save file
		 */
		$args = Context::gets('module_srl','thumbnail_image','contents_file');
		$args->item_srl = $item_srl;
		if (is_uploaded_file($args->thumbnail_image['tmp_name']))
		{
			$output = $oFileController->insertFile($args->thumbnail_image, $args->module_srl, $args->item_srl);
			if(!$output || !$output->toBool()) return $output;

			$args->thumb_file_srl = $output->get('file_srl');
		}

		if(is_uploaded_file($args->contents_file['tmp_name']))
		{
			$output = $oFileController->insertFile($args->contents_file, $args->module_srl, $item_srl);
			if(!$output || !$output->toBool()) return $output;
			$args->file_srl = $output->get('file_srl');
		}

		if($args->file_srl || $args->thumb_file_srl)
		{
			$output = executeQuery('nproduct.updateItemFile', $args);
			if(!$output->toBool()) return $output;
		}

		$oFileController->setFilesValid($item_srl);

		/*
		 * extra_vars insert
		 */
		// extras
		$extra_vars = $oNproductModel->getExtraVars($args->module_srl);
		$extra_vars = delObjectVars($extra_vars, $args);
		unset($args);

		foreach($extra_vars as $k => $v)
		{
			$ex_args->item_srl = $item_srl;
			$ex_args->name = $k;
			$ex_args->value = $v->getValuePlain();

			$output = executeQuery('nproduct.deleteNproductExtraVars', $ex_args);
			if(!$output->toBool()) return $output;

			$output = executeQuery('nproduct.insertNproductExtraVars', $ex_args);
			if(!$output->toBool()) return $output;
		}

		// before
		$output = ModuleHandler::triggerCall('nproduct.insertItem', 'after', $args);
		if (!$output->toBool()) return $output;

		$this->setMessage('success_registed');

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) 
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module',Context::get('module'),'act','dispNproductAdminUpdateItem','module_srl',Context::get('module_srl'),'item_srl',$item_srl);
			$this->setRedirectUrl($returnUrl);
			return;
		}
	}

	/**
	 * @brief update item
	 **/
	function procNproductAdminUpdateItem() 
	{
		$oMemberModel = &getModel('member');
		$oDocumentModel = &getModel('document');
		$oNproductModel = &getModel('nproduct');
		$oDocumentController = &getController('document');

		$item_srl = Context::get('item_srl');
		$module_srl = Context::get('disp_module_srl');
		$item_name = Context::get('item_name');
		$item_code = Context::get('item_code');
		$category_id = Context::get('category_id');
		$document_srl = Context::get('document_srl');
		$description = Context::get('description');
		$price = Context::get('price');
		$discount_amount = Context::get('discount_amount');
		$discount_info = Context::get('discount_info');
		$taxfree = Context::get('taxfree');
		$display = Context::get('display');
		$delivery_info = Context::get('delivery_info');
		$group_srl_list = Context::get('group_srl_list');

		// update document
		$doc_args->document_srl = $document_srl;
		//$doc_args->category_srl = $category_id;
		$doc_args->module_srl = $module_srl;
		$doc_args->content = $description;
		$doc_args->title = $item_name;
		$doc_args->list_order = $doc_args->document_srl*-1;
		$doc_args->tags = Context::get('tag');
		$doc_args->allow_comment = 'Y';
		$output = $oDocumentController->updateDocument($oDocumentModel->getDocument($document_srl), $doc_args);
		if (!$output->toBool()) return $output;

		if(Context::get('delete_file'))
		{
			$delete_file_srl = Context::get('delete_file');
			$this->deleteNproductFile($delete_file_srl, $item_srl);
		}

		// update item
		$args->item_srl = $item_srl;
		$args->item_name = $item_name;
		$args->item_code = $item_code;
		$args->module_srl = $module_srl;
		$args->node_route = 'f.';
		if ($category_id)
		{
			$args->category_id = $category_id;
			$category_info = $oNproductModel->getCategoryInfo($category_id);
			$args->node_route = 'f.';
			if ($category_info) $args->node_route = $category_info->node_route . $category_info->node_id . '.';
		}
		$args->proc_module = Context::get('proc_module');
		$args->document_srl = $document_srl;
		$args->price = $price;
		$args->discount_amount = $discount_amount;
		$args->discount_info = $discount_info;
		$args->taxfree = $taxfree;
		$args->display = $display;
		$args->delivery_info = $delivery_info;
		$args->group_srl_list = serialize($group_srl_list);
		$args->related_items = Context::get('related_items');
		$args->minimum_order_quantity = Context::get('minimum_order_quantity');
		$args->item_stock = Context::get('item_stock');
		if($args->item_stock < 0) $args->item_stock = -1;

		// extras
		$extra_vars = $oNproductModel->getExtraVars($module_srl);
		$extra_vars = delObjectVars($extra_vars, $args);
		$args->extra_vars = serialize($extra_vars);

		// before
		$output = ModuleHandler::triggerCall('nproduct.updateItem', 'before', $args);
		if (!$output->toBool()) return $output;

		/*
		 *  extra_vars update
		 */
		foreach($extra_vars as $key => $val)
		{
			$ex_args->item_srl = $item_srl;
			$ex_args->name = $key;
			$ex_args->value = $val->getValuePlain();

			$output = executeQuery('nproduct.deleteNproductExtraVars', $ex_args);
			if(!$output->toBool()) return $output;

			$output = executeQuery('nproduct.insertNproductExtraVars', $ex_args);
			if(!$output->toBool()) return $output;
		}

		/* 
		 * end
		 */
		$output = executeQuery('nproduct.updateItemAdmin', $args);
		if (!$output->toBool()) return $output;

		// update group discount
		$args->item_srl = $item_srl;
		$output = executeQuery('nproduct.deleteGroupDiscount', $args);
		if (!$output->toBool()) return $output;
		unset($args);

		$group_list = $oMemberModel->getGroups();
		foreach ($group_list as $key=>$val) 
		{
			if (Context::get('group_discount_'.$val->group_srl)) 
			{
				$opt = Context::get('group_opt_'.$val->group_srl);
				if (!$opt) $opt='1';
				$args->item_srl = $item_srl;
				$args->module_srl = $module_srl;
				$args->group_srl = $val->group_srl;
				$args->opt = $opt;
				$args->price = Context::get('group_discount_'.$val->group_srl);
				$output = executeQuery('nproduct.insertGroupDiscount', $args);
				if (!$output->toBool()) return $output;
				unset($args);
			}
		}

		$this->procNproductAdminUpdateItemFile();

		// after
		$output = ModuleHandler::triggerCall('nproduct.updateItem', 'after', $args);
		if (!$output->toBool()) return $output;

		$this->setRedirectUrl(getNotEncodedUrl('', 'module', Context::get('module'),'act','dispNproductAdminUpdateItem','module_srl',Context::get('module_srl'),'item_srl',$item_srl,'s_item_name',Context::get('s_item_name'),'category',Context::get('category')));
	}

	/**
	 * @brief update item file
	 **/
	function procNproductAdminUpdateItemFile() 
	{
		$oFileController = &getController('file');

		$args = Context::getRequestVars();

		if(is_uploaded_file($args->contents_file['tmp_name']) || $args->thumbnail_image['tmp_name']) 
		{
			$args->item_srl = Context::get('item_srl');
			$output = executeQuery('nproduct.getItemInfo', $args);
			if (!$output->toBool()) return $output;

			$item_info = $output->data;
			// process for uploaded contents file.
			if(is_uploaded_file($args->contents_file['tmp_name'])) 
			{
				// delete contents file
				if($item_info->file_srl) $oFileController->deleteFile($item_info->file_srl);
				
				// attach thumbnail
				$output = $oFileController->insertFile($args->contents_file, $args->module_srl, $args->item_srl);
				if(!$output || !$output->toBool()) return $output;

				$args->file_srl = $output->get('file_srl');
			}

			// process for uplaoded thumbnail image.
			if(is_uploaded_file($args->thumbnail_image['tmp_name'])) 
			{
				// delete thumbnail
				if($item_info->thumb_file_srl) $oFileController->deleteFile($item_info->thumb_file_srl);

				// attach thumbnail
				$output = $oFileController->insertFile($args->thumbnail_image, $args->module_srl, $args->item_srl);
				if(!$output || !$output->toBool()) return $output;

				$args->thumb_file_srl = $output->get('file_srl');
			}

			$output = executeQuery('nproduct.updateItemFile', $args);
			if(!$output->toBool()) return $output;

			$oFileController->setFilesValid($args->item_srl);
		}
	}

	/**
	 * @brief insert discount member
	 **/
	function procNproductAdminMemberDiscount()
	{
		$oMemberMedel = &getModel('member');
		$vars = Context::getRequestVars();

		if(!$vars->member_id) return new Object(-1,'아이디를 입력해주세요.');
		$member_srl = $oMemberMedel->getMemberSrlByUserID($vars->member_id);

		if(!$member_srl) return new Object(-1,'존재하지 않는 ID입니다.');

		$args->member_srl = $member_srl;
		if(!$vars->discount)
		{
			// delete member_discount by no '$vars->discount'
			$output = executeQuery('nproduct.deleteMemberDiscount', $args);
			if(!$output->toBool()) return $output;
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', Context::get('module'),'act','dispNproductAdminMemberDiscount','module_srl',Context::get('module_srl')));
			return;
		}

		// check if member_srl is on discount
		$output = executeQuery('nproduct.getMemberDiscount', $args);
		if(!$output->toBool()) return $output;
		// delete member_discount
		if($output->data) return new Object(-1, '할인적용중인 ID입니다. 삭제후 등록 해주세요.');

		$args->discount = $vars->discount;
		$args->opt = $vars->member_opt;

		// insert member_discount
		$output = executeQuery('nproduct.insertMemberDiscount', $args);
		if(!$output->toBool()) return $output;

		$this->setRedirectUrl(getNotEncodedUrl('', 'module', Context::get('module'),'act','dispNproductAdminMemberDiscount','module_srl',Context::get('module_srl')));
	}

	/**
	 * @brief insert discount quntity
	 **/
	function procNproductAdminQuantityDiscount()
	{
		$oNproductModel = &getModel('nproduct');

		$vars = Context::getRequestVars();
		if(!$vars->item_code || !$vars->quantity || !$vars->discount) return new Object(-1,'상품코드와 수량 그리고 할인가를 입력해주세요.');

		$item_info = $oNproductModel->getItemByCode($vars->item_code);
		$item_srl = $item_info->item_srl;
		if(!$item_srl) return new Object(-1, '상품이 없습니다.');

		$args->item_srl = $item_srl;
		$output = executeQuery('nproduct.getQuantityDiscount', $args);
		if(!$output->toBool()) return $output;
		if($output->data) return new Object(-1, '할인적용중인 상품입니다. 삭제후 등록 해주세요.');

		$args->quantity = $vars->quantity;
		$args->discount = $vars->discount;
		$args->opt = $vars->quantity_opt;

		// insert member_discount
		$output = executeQuery('nproduct.insertQuantityDiscount', $args);
		if(!$output->toBool()) return $output;

		$this->setRedirectUrl(getNotEncodedUrl('', 'module', Context::get('module'),'act','dispNproductAdminQuantityDiscount','module_srl',Context::get('module_srl')));
	}

	/**
	 * @brief delete discount member
	 **/
	function procNproductAdminDeleteMemberDiscount()
	{
		$vars = Context::getRequestVars();
		if(!$vars->member_srls)
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', Context::get('module'),'act','dispNproductAdminMemberDiscount','module_srl',Context::get('module_srl'))); 
			return;
		}

		foreach($vars->member_srls as $key => $val)
		{
			$args->member_srl = $val;
			$output = executeQuery('nproduct.deleteMemberDiscount', $args);
			if(!$output->toBool()) return $output;
		}

		$this->setRedirectUrl(getNotEncodedUrl('', 'module', Context::get('module'),'act','dispNproductAdminMemberDiscount','module_srl',Context::get('module_srl'))); 
	}

	/**
	 * @brief delete discount qunatity
	 **/
	function procNproductAdminDeleteQuantityDiscount()
	{
		$vars = Context::getRequestVars();
		if(!$vars->item_srls)
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', Context::get('module'),'act','dispNproductAdminQuantityDiscount','module_srl',Context::get('module_srl')));
			return;
		}

		foreach($vars->item_srls as $key => $val)
		{
			$args->item_srl = $val;
			$output = executeQuery('nproduct.deleteQuantityDiscount', $args);
			if(!$output->toBool()) return $output;
		}

		$this->setRedirectUrl(getNotEncodedUrl('', 'module', Context::get('module'),'act','dispNproductAdminQuantityDiscount','module_srl',Context::get('module_srl')));
	}

	/**
	 * @brief delete file and update item info with null file_srl
	 **/
	function deleteNproductFile($file_srl, $item_srl)
	{
		$oFileController = &getController('file');
		$oFileController->deleteFile($file_srl);

		$args->file_srl = null;
		$args->item_srl = $item_srl;
		$output = executeQuery('nproduct.updateItemFile', $args);
		if (!$output->toBool()) return $output;
		return;
	}

	/**
	 * @brief create insert item ruleset
	 **/
	function _createInsertItemRuleset($extra_vars)
	{
		$xml_file = './files/ruleset/nproduct_insertItem.xml';
		$buff = '<?xml version="1.0" encoding="utf-8"?>'
				.'<ruleset version="1.5.0">'
				.'<customrules>'
				.'</customrules>'
				.'<fields>%s</fields>'						
				.'</ruleset>';

		$fields = array();
		$fields[] = '<field name="module_srl" required="true" />';
		$fields[] = '<field name="item_name" required="true" length="1:60" />';
		$fields[] = '<field name="document_srl" required="true" rule="number" />';
		$fields[] = '<field name="price" required="true" rule="number" />';
		$fields[] = '<field name="description" required="true" />';
		
		if(count($extra_vars))
		{
			foreach($extra_vars as $formInfo){
				if($formInfo->required=='Y')
				{
					if($formInfo->type == 'tel' || $formInfo->type == 'kr_zip')
					{
						$fields[] = sprintf('<field name="%s[]" required="true" />', $formInfo->column_name);
					}
					else if($formInfo->type == 'email_address')
					{
						$fields[] = sprintf('<field name="%s" required="true" rule="email"/>', $formInfo->column_name);
					}
					else if($formInfo->type == 'user_id')
					{
						$fields[] = sprintf('<field name="%s" required="true" rule="userid" length="3:20" />', $formInfo->column_name);
					}
					else
					{
						$fields[] = sprintf('<field name="%s" required="true" />', $formInfo->column_name);
					}
				}
			}
		}

		$xml_buff = sprintf($buff, implode('', $fields));
		FileHandler::writeFile($xml_file, $xml_buff);
		unset($xml_buff);

		$validator   = new Validator($xml_file);
		$validator->setCacheDir('files/cache');
		$validator->getJsPath();
	}
}
/* End of file nproduct.admin.controller.php */
/* Location: ./modules/nproduct/nproduct.admin.controller.php */
