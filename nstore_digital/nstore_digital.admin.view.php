<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nstore_digitalAdminView
 * @author NURIGO(contact@nurigo.net)
 * @brief  nstore_digitalAdminView
 */ 
class nstore_digitalAdminView extends nstore_digital
{
	function init() 
	{
		// module_srl이 있으면 미리 체크하여 존재하는 모듈이면 module_info 세팅
		$module_srl = Context::get('module_srl');
		if(!$module_srl && $this->module_srl)
		{
			$module_srl = $this->module_srl;
			Context::set('module_srl', $module_srl);
		}

		$oModuleModel = &getModel('module');

		// module_srl이 넘어오면 해당 모듈의 정보를 미리 구해 놓음
		if($module_srl) 
		{
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
			if(!$module_info) 
			{
				Context::set('module_srl','');
				$this->act = 'list';
			} else {
				ModuleModel::syncModuleToSite($module_info);
				$this->module_info = $module_info;
				Context::set('module_info',$module_info);
			}
		}
		if($module_info && !in_array($module_info->module, array('nstore','nstore_digital','elearning')))
		{
			return $this->stop("msg_invalid_request");
		}

		// epay plugin list
		$oEpayModel = &getModel('epay');
		$modules = $oEpayModel->getEpayList();
		Context::set('epay_modules', $modules);

		// set template file
		$tpl_path = $this->module_path.'tpl';
		$this->setTemplatePath($tpl_path);
		Context::set('tpl_path', $tpl_path);

		// module이 cympusadmin일때 관리자 레이아웃으로
        if(Context::get('module')=='cympusadmin')
        {
            $classfile = _XE_PATH_.'modules/cympusadmin/cympusadmin.class.php';
            if(file_exists($classfile))
            {
                    require_once($classfile);
                    cympusadmin::init();
            }
        }
	}

	/**
	 * @brief Contructor
	 **/
	function dispNstore_digitalAdminDashboard() 
	{
		$output = executeQueryArray('nstore_digital.getOrderStat', $args);
		if(!$output->toBool()) return $output;
		$list = $output->data;
		if(!is_array($list)) $list = array();

		$stat_arr = array();
		$keys = array_keys($this->order_status);

		foreach ($keys as $key) {
			$stat_arr[$key] = 0;
		}
		foreach ($list as $key=>$val) {
			$stat_arr[$val->order_status] = $val->count;
		}
		Context::set('order_status', $this->getOrderStatus());
		Context::set('orderstat', $stat_arr);

		// get module srls
		$module_srls = array();
		$output = executeQueryArray('nproduct.getModInstList', $args);
		if(!$output->toBool())
		{
			return $output;
		}
		$modinst_list = $output->data;
		if(!is_array($modinst_list))
		{
			$modinst_list = array();
		}
		foreach ($modinst_list as $modinst)
		{
			$module_srls[] = $modinst->module_srl;
		}

		// newest comment
		$oCommentModel = &getModel('comment');
		$columnList = array('comment_srl', 'module_srl', 'document_srl', 'content', 'nick_name', 'member_srl');
		$args->module_srl = $module_srls;
		$args->list_count = 20;
		$comment_list = $oCommentModel->getNewestCommentList($args, $columnList);
		if(!is_array($comment_list)) 
		{
			$comment_list = array();
		}

		foreach($comment_list AS $key=>$value)
		{
			$value->content = strip_tags($value->content);
		}
		Context::set('comment_list', $comment_list);
		unset($args, $comment_list, $columnList);


		// newest review
		$review_list = array();
		require_once(_XE_PATH_.'modules/store_review/store_review.item.php');
		$args->module_srl = $module_srls;
		$output = executeQueryArray('nstore_digital.getNewestReviewList', $args);
		if(!is_array($output->data)) 
		{
			$output->data = array();
		}
		foreach ($output->data as $key=>$val)
		{
			if(!$val->review_srl)
			{
				continue;
			}
			$oReview = new store_reviewItem();
			$oReview->setAttribute($val);
			$oReview->storeItem = new nproductItem($oReview->get('item_srl'));
			debugprint($oReview->storeItem);
			$review_list[$key] = $oReview;
		}
		Context::set('review_list', $review_list);

		$this->getNewsFromAgency();

		$this->setTemplateFile('dashboard');
	}

	function dispNstore_digitalAdminOrderManagement() 
	{
		$oNstore_coreModel = &getModel('nstore_digital');

		if(!Context::get('status'))
		{
			Context::set('status','1');
		}
		$args->order_status = Context::get('status');
		$args->page = Context::get('page');
		if(Context::get('search_key'))
		{
			$search_key = Context::get('search_key');
			$search_value = Context::get('search_value');
			if($search_key == 'nick_name' && $search_value == '비회원')
			{
				$search_key = 'member_srl';
				$search_value = 0;
			}
			$args->{$search_key} = $search_value;
		}
		$output = executeQueryArray('nstore_digital.getOrderListByStatus', $args);
		if(!$output->toBool()) return $output;
		$order_list = $output->data;
		if(!is_array($order_list)) $order_list = array();
		Context::set('list', $order_list);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);
		Context::set('order_status', $this->getOrderStatus());
		Context::set('delivery_inquiry_urls', $this->delivery_inquiry_urls);

		$this->setTemplateFile('ordermanagement');
	}

	function dispNstore_digitalAdminIndividualOrderManagement() 
	{

		$oNstore_coreModel = &getModel('nstore_digital');
		$oEpayModel = &getModel('epay');

		$config = $oNstore_coreModel->getModuleConfig();

/*
		$order_srl = Context::get('order_srl');

		$order_info = $oNstore_coreModel->getOrderInfo($order_srl);

		$payment_info = $oEpayModel->getTransactionByOrderSrl($order_srl);
		Context::set('payment_info',$payment_info);
		Context::set('order_info', $order_info);
		*/

		if(!Context::get('status'))
		{
			Context::set('status','1');
		}

		if(Context::get('search_key'))
		{
			$search_key = Context::get('search_key');
			$search_value = Context::get('search_value');
			if($search_key == 'nick_name' && $search_value == '비회원')
			{
				$search_key = 'member_srl';
				$search_value = 0;
			}
			$args->{$search_key} = $search_value;
		}

		$args->order_status = Context::get('status');
		$args->page = Context::get('page');

		$output = executeQueryArray('nstore_digital.getPurchasedItemsByStatus', $args);

		if(!$output->toBool()) return $output;

		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);


		$order_list = $output->data;
		if(!is_array($order_list)) $order_list = array();

		foreach($order_list as $k => $v)
		{
			if(!$v)continue;

			$item = new nproductItem($v, $config->currency, $config->as_sign, $config->decimals);
			debugprint($item);
			if ($item->option_srl)
			{
				$item->price += ($item->option_price);
			}
			$v->item = $item;

			$vars->order_srl = $v->order_srl;
			$output = executeQuery('nstore_digital.getOrderInfo', $vars);
			if(!$output->toBool()) return $output;
			$v->order_info = $output->data;
			unset($vars);
		}

		Context::set('order_list', $order_list);
		Context::set('order_status', $this->getOrderStatus());
		Context::set('delivery_inquiry_urls', $this->delivery_inquiry_urls);

		$this->setTemplateFile('individual_ordermanagement');
	}

	function dispNstore_digitalAdminPeriodManagement() 
	{
		$oNstore_coreModel = &getModel('nstore_digital');
		$oNproduct_Model = &getModel('nproduct');

		if(!Context::get('status'))
		{
			Context::set('status','1');
		}

		$args = new Object();
		$args->order_status = Context::get('status');
		$args->page = Context::get('page');
		if(Context::get('search_key'))
		{
			$search_key = Context::get('search_key');
			$search_value = Context::get('search_value');
			if($search_key == 'nick_name' && $search_value == '비회원')
			{
				$search_key = 'member_srl';
				$search_value = 0;
			}
			$args->{$search_key} = $search_value;
		}

		$output = executeQueryArray('nstore_digital.getPeriodListByStatus', $args);
		if(!$output->toBool()) return $output;

		$order_list = $output->data;
		if(!is_array($order_list)) $order_list = array();

		foreach($order_list as $k => $v)
		{
			if($v->item_srl)
			{
				$output = $oNproduct_Model->getItemInfo($v->item_srl);
				$v->item_name = $output->item_name;

			}
		}

		Context::set('list', $order_list);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);
		Context::set('order_status', $this->getOrderStatus());

		$this->setTemplateFile('periodmanagement');
	}

	function dispNstore_digitalAdminOrderDetail() 
	{
		$oNstore_coreModel = &getModel('nstore_digital');
		$oEpayModel = &getModel('epay');

		$config = $oNstore_coreModel->getModuleConfig();

		$order_srl = Context::get('order_srl');

		$order_info = $oNstore_coreModel->getOrderInfo($order_srl);

		$payment_info = $oEpayModel->getTransactionByOrderSrl($order_srl);
		Context::set('payment_info',$payment_info);
		Context::set('order_info', $order_info);
		Context::set('order_status', $this->getOrderStatus());
		Context::set('payment_method', $this->getPaymentMethods());
		Context::set('delivery_inquiry_urls', $this->delivery_inquiry_urls);

		$this->setTemplateFile('orderdetail');
	}

	function dispNstore_digitalAdminOrderExcelDownload() 
	{
		$args->order_status = Context::get('status');
		$output = executeQueryArray('nstore_digital.getOrderItemsByStatus', $args);
		if(!$output->toBool())
		{
			return $output;
		}
		Context::set('list', $output->data);
		$this->setLayoutPath('./common/tpl');
		$this->setLayoutFile('default_layout');
		$this->setTemplateFile('excel_download');
		header("Content-Type: Application/octet-stream;");
		header("Content-Disposition: attachment; filename=\"ORDERITEMS-" . date('Ymd') . ".xls\"");
	}

	function dispNstore_digitalAdminItemListExcelDownload() 
	{
		$oNstore_coreModel = &getModel('nstore_digital');
		$oStoreView = &getView('nstore_digital');

		$oStoreView->getCategoryTree($this->module_info->module_srl);

		$category = Context::get('category');
		$list_count = Context::get('disp_numb');
		$sort_index = Context::get('sort_index');
		$order_type = Context::get('order_type');

		if(!$list_count) 
		{
			$list_count = 30;
		}
		if(!$sort_index) 
		{
			$sort_index = "item_srl";
		}
		if(!$order_type) 
		{
			$order_type = 'asc';
		}
		if($category) 
		{
			$category_info = $oNstore_coreModel->getCategoryInfo($category);

			$args->module_srl = Context::get('module_srl');
			$args->node_route = $category_info->node_route . $category_info->node_id . '.';
			$args->page = Context::get('page');
			$args->list_count = $list_count;
			$args->sort_index = $sort_index;
			$args->order_type = $order_type;
			$output = executeQueryArray('nstore_digital.getItemsByNodeRoute', $args);
			if(!$output->toBool()) 
			{
				return $output;
			}
			$item_list = $output->data;
			Context::set('total_count', $output->total_count);
			Context::set('total_page', $output->total_page);
			Context::set('page', $output->page);
			Context::set('page_navigation', $output->page_navigation);
		} 
		else 
		{
			$args->module_srl = Context::get('module_srl');
			$args->page = Context::get('page');
			$args->list_count = $list_count;
			$args->sort_index = $sort_index;
			$args->order_type = $order_type;
			$output = executeQueryArray('nstore_digital.getItemsByNodeRoute', $args);
			if(!$output->toBool()) 
			{
				return $output;
			}
			$item_list = $output->data;
			Context::set('total_count', $output->total_count);
			Context::set('total_page', $output->total_page);
			Context::set('page', $output->page);
			Context::set('page_navigation', $output->page_navigation);
		}

		if($item_list) 
		{
			foreach ($item_list as $key=>$val) {
				$item_list[$key] = new nproductItem($val);
			}
		}
		Context::set('list', $item_list);

		$this->setLayoutPath('./common/tpl');
		$this->setLayoutFile('default_layout');
		$this->setTemplateFile('itemlist_exceldown');
		header("Content-Type: Application/octet-stream;");
		header("Content-Disposition: attachment; filename=\"ITEMLIST-" . date('Ymd') . ".xls\"");
	}

	function dispNstore_digitalAdminReceipt() 
	{
		$args->order_srl = Context::get('order_srl');
		$output = executeQuery('nstore_digital.getOrderInfo', $args);
		if(!$output->toBool()) 
		{
			return $output;
		}
		$order_info = $output->data;
		Context::set('order_info', $order_info);

		$this->setTemplateFile('receipt');
		$this->setLayoutPath('./common/tpl');
		$this->setLayoutFile('default_layout');
	}

	function dispNstore_digitalAdminOrderSheet() 
	{
		$oNstore_digitalModel = &getModel('nstore_digital');

		$order_srl = Context::get('order_srl');
		$order_info = $oNstore_digitalModel->getOrderInfo($order_srl);
		Context::set('order_info', $order_info);

		$this->setTemplateFile('ordersheet');
		$this->setLayoutPath('./common/tpl');
		$this->setLayoutFile('default_layout');
	}

	function dispNstore_digitalAdminModInstList() 
	{
		$args->sort_index = "module_srl";
		$args->page = Context::get('page');
		$args->list_count = 20;
		$args->page_count = 10;
		$args->s_module_category_srl = Context::get('module_category_srl');
		$output = executeQueryArray('nstore_digital.getModInstList', $args);
		$list = $output->data;
		Context::set('list', $list);

		$oModuleModel = &getModel('module');
		$module_category = $oModuleModel->getModuleCategories();
		Context::set('module_category', $module_category);
		$this->setTemplateFile('modinstlist');
	}

	function dispNstore_digitalAdminConfig() 
	{
		$oNstore_coreModel = &getModel('nstore_digital');
		$oModuleModel = &getModel('module');

		$config = $oNstore_coreModel->getModuleConfig();
		Context::set('config',$config);

		// list of skins for member module
		$skin_list = $oModuleModel->getSkins($this->module_path);
		Context::set('skin_list', $skin_list);

		$mskin_list = $oModuleModel->getSkins($this->module_path, "m.skins");
		Context::set('mskin_list', $mskin_list);

		// 레이아웃 목록을 구해옴
		$oLayoutModel = &getModel('layout');
		$layout_list = $oLayoutModel->getLayoutList();
		Context::set('layout_list', $layout_list);

		$mobile_layout_list = $oLayoutModel->getLayoutList(0,"M");
		Context::set('mlayout_list', $mobile_layout_list);

		// epay plugin list
		$oEpayModel = &getModel('epay');
		$modules = $oEpayModel->getEpayList();
		Context::set('epay_modules', $modules);

		$oNcartModel = &getModel('ncart');
		if($oNcartModel)
		{
			$ncart_insts = $oNcartModel->getModInstList();
			Context::set('ncart_insts', $ncart_insts);
		}

		$this->setTemplateFile('config');
	}

	function dispNstore_digitalAdminInsertModInst() 
	{
		$oNstore_coreModel = &getModel('nstore_digital');

		// 스킨 목록을 구해옴
		$oModuleModel = &getModel('module');
		$skin_list = $oModuleModel->getSkins($this->module_path);
		Context::set('skin_list',$skin_list);

		$mskin_list = $oModuleModel->getSkins($this->module_path, "m.skins");
		Context::set('mskin_list', $mskin_list);

		// 레이아웃 목록을 구해옴
		$oLayoutModel = &getModel('layout');
		$layout_list = $oLayoutModel->getLayoutList();
		Context::set('layout_list', $layout_list);

		$mobile_layout_list = $oLayoutModel->getLayoutList(0,"M");
		Context::set('mlayout_list', $mobile_layout_list);

		// epay plugin list
		$oEpayModel = &getModel('epay');
		$modules = $oEpayModel->getEpayList();
		Context::set('epay_modules', $modules);

		$oEditorModel = &getModel('editor');
		$config = $oEditorModel->getEditorConfig(0);
		// 에디터 옵션 변수를 미리 설정
		$option->skin = $config->editor_skin;
		$option->content_style = $config->content_style;
		$option->content_font = $config->content_font;
		$option->content_font_size = $config->content_font_size;
		$option->colorset = $config->sel_editor_colorset;
		$option->allow_fileupload = true;
		$option->enable_default_component = true;
		$option->enable_component = true;
		$option->disable_html = false;
		$option->height = 200;
		$option->enable_autosave = false;
		$option->primary_key_name = 'module_srl';
		$option->content_key_name = 'delivery_info';
		$editor = $oEditorModel->getEditor($this->module_info->module_srl, $option);
		Context::set('editor', $editor);

		$module_category = $oModuleModel->getModuleCategories();
		Context::set('module_category', $module_category);
		$this->setTemplateFile('insertmodinst');
	}

	function dispNstore_digitalAdminAdditionSetup() 
	{
		// content는 다른 모듈에서 call by reference로 받아오기에 미리 변수 선언만 해 놓음
		$content = '';

		$oEditorView = &getView('editor');
		$oEditorView->triggerDispEditorAdditionSetup($content);
		Context::set('setup_content', $content);
	}

	function dispNstore_digitalAdminMailSetup() 
	{
		// content는 다른 모듈에서 call by reference로 받아오기에 미리 변수 선언만 해 놓음
		$content = '';
		$status = Context::get('status');
		if(!$status) $status = '1';

		$oAutomailModel = &getModel('automail');
		if($oAutomailModel) $oAutomailModel->getSetup('nstore_digital', $status, $content);
		Context::set('setup_content', $content);
		$order_status = $this->getOrderStatus();
		unset($order_status[0]);
		Context::set('order_status', $order_status);
		$this->setTemplateFile('additionsetup');
	}
	/**
	 * @brief 스킨 정보 보여줌
	 **/
	function dispNstore_digitalAdminSkinInfo() 
	{
		// 공통 모듈 권한 설정 페이지 호출
		$oModuleAdminModel = &getAdminModel('module');
		$skin_content = $oModuleAdminModel->getModuleSkinHTML($this->module_info->module_srl);
		Context::set('skin_content', $skin_content);
		$this->setTemplateFile('skininfo');
	}

	/**
	 * @brief 스킨 정보 보여줌
	 **/
	function dispNstore_digitalAdminMobileSkinInfo() 
	{
		// 공통 모듈 권한 설정 페이지 호출
		$oModuleAdminModel = &getAdminModel('module');
		$skin_content = $oModuleAdminModel->getModuleMobileSkinHTML($this->module_info->module_srl);
		Context::set('skin_content', $skin_content);
		$this->setTemplateFile('skininfo');
	}


	function getNewsFromAgency()
	{
		//Retrieve recent news and set them into context
		$newest_news_url = sprintf("http://www.xeshoppingmall.com/?module=newsagency&act=getNewsagencyArticle&inst=notice&top=6&loc=%s", _XE_LOCATION_);
		$cache_file = sprintf("%sfiles/cache/nstore_news.%s.cache.php", _XE_PATH_, _XE_LOCATION_);
		if(!file_exists($cache_file) || filemtime($cache_file)+ 60*60 < time())
		{
			// Considering if data cannot be retrieved due to network problem, modify filemtime to prevent trying to reload again when refreshing textmessageistration page
			// Ensure to access the textmessageistration page even though news cannot be displayed
			FileHandler::writeFile($cache_file,'');
			FileHandler::getRemoteFile($newest_news_url, $cache_file, null, 1, 'GET', 'text/html', array('REQUESTURL'=>getFullUrl('')));
		}

		if(file_exists($cache_file)) 
		{
			$oXml = new XmlParser();
			$buff = $oXml->parse(FileHandler::readFile($cache_file));

			$item = $buff->zbxe_news->item;
			if($item) 
			{
				if(!is_array($item)) 
				{
					$item = array($item);
				}

				foreach($item as $key => $val) {
					$obj = null;
					$obj->title = $val->body;
					$obj->date = $val->attrs->date;
					$obj->url = $val->attrs->url;
					$news[] = $obj;
				}
				Context::set('news', $news);
			}
			Context::set('released_version', $buff->zbxe_news->attrs->released_version);
			Context::set('download_link', $buff->zbxe_news->attrs->download_link);
		}
	}
}
/* End of file nstore_digital.admin.view.php */
/* Location: ./modules/nstore_digital/nstore_digital.admin.view.php */
