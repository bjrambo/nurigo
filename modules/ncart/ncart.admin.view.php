<?php
    /**
     * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
     * @class  ncartAdminView
     * @author NURIGO(contact@nurigo.net)
     * @brief  ncartAdminView
     */ 
class ncartAdminView extends ncart
{
	/**
	 * @brief Contructor
	 **/
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
		if($module_info && !in_array($module_info->module, array('ncart')))
		{
			return $this->stop("msg_invalid_request");
		}

		// set template file
		$tpl_path = $this->module_path.'tpl';
		$this->setTemplatePath($tpl_path);
		$this->setTemplateFile('index');
		Context::set('tpl_path', $tpl_path);


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

	function getNewsFromAgency()
	{
		//Retrieve recent news and set them into context
		$newest_news_url = sprintf("http://store.nurigo.net/?module=newsagency&act=getNewsagencyArticle&inst=notice&top=6&loc=%s", _XE_LOCATION_);
		$cache_file = sprintf("%sfiles/cache/ncart_news.%s.cache.php", _XE_PATH_, _XE_LOCATION_);
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

	function getLicenseFromAgency()
	{
		$hostinfo = array($_SERVER['SERVER_ADDR'], $_SERVER['SERVER_NAME'], $_SERVER['HTTP_HOST']);
		$agency_url = sprintf("http://store.nurigo.net/?module=drmagency&act=getDrmagencyLicense&prodid=%s&hostinfo=%s", $this->getExtMod(), implode(',',$hostinfo));
		$cache_file = sprintf("%sfiles/cache/ncart_drm.%s.cache.php", _XE_PATH_, _XE_LOCATION_);
		if(!file_exists($cache_file) || filemtime($cache_file)+ 60*60 < time())
		{
			FileHandler::writeFile($cache_file,'');
			FileHandler::getRemoteFile($agency_url, $cache_file, null, 1, 'GET', 'text/html', array('REQUESTURL'=>getFullUrl('')));
		}
	}

	function dispNcartAdminModInstList() 
	{
		$args->sort_index = "module_srl";
		$args->page = Context::get('page');
		$args->list_count = 20;
		$args->page_count = 10;
		$args->s_module_category_srl = Context::get('module_category_srl');
		$output = executeQueryArray('ncart.getModInstList', $args);
		$store_list = $output->data;

		if(!is_array($store_list)) 
		{
			$store_list = array();
		}
		Context::set('store_list', $store_list);

		$oModuleModel = &getModel('module');
		$module_category = $oModuleModel->getModuleCategories();
		Context::set('module_category', $module_category);
		$this->setTemplateFile('modinstlist');
	}

	function dispNcartAdminConfig() 
	{
		$oNcartModel = &getModel('ncart');
		$oModuleModel = &getModel('module');

		$config = $oNcartModel->getModuleConfig();
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

		Context::set('delivery_companies', $oNcartModel->getDeliveryCompanies());
		$this->setTemplateFile('config');
	}

	function dispNcartAdminInsertModInst() 
	{
		$oNcartModel = &getModel('ncart');

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

		//Context::set('delivery_companies', $oNcartModel->getDeliveryCompanies());

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

	function dispNcartAdminAdditionSetup() 
	{
		// content는 다른 모듈에서 call by reference로 받아오기에 미리 변수 선언만 해 놓음
		$content = '';

		$oEditorView = &getView('editor');
		$oEditorView->triggerDispEditorAdditionSetup($content);
		Context::set('setup_content', $content);
		$this->setTemplateFile('additionsetup');
	}

	/**
	 * @brief 스킨 정보 보여줌
	 **/
	function dispNcartAdminSkinInfo() 
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
	function dispNcartAdminMobileSkinInfo() 
	{
		// 공통 모듈 권한 설정 페이지 호출
		$oModuleAdminModel = &getAdminModel('module');
		$skin_content = $oModuleAdminModel->getModuleMobileSkinHTML($this->module_info->module_srl);
		Context::set('skin_content', $skin_content);
		$this->setTemplateFile('skininfo');
	}

	function dispNcartAdminOrderForm()
	{
		$oNcartModel = &getModel('ncart');
		$oNproductModel = &getModel('nproduct');
		$fieldset_list = $oNcartModel->getFieldSetList($this->module_info->module_srl);
		Context::set('fieldset_list', $fieldset_list);
		$proc_modules = $oNproductModel->getProcModules();
		Context::set('proc_modules', $proc_modules);
		$this->setTemplateFile('orderform');
	}

	function dispNcartAdminOrderDetail() 
	{
		$oNcartModel = &getModel('ncart');
		$oEpayModel = &getModel('epay');

		$order_srl = Context::get('order_srl');
		$order_info = $oNcartModel->getOrderInfo($order_srl);

		$payment_info = $oEpayModel->getTransactionByOrderSrl($order_srl);
		Context::set('payment_info',$payment_info);
		Context::set('order_info', $order_info);
		Context::set('order_status', $this->getOrderStatus());
		Context::set('delivery_companies', $oNcartModel->getDeliveryCompanies());
		Context::set('payment_method', $this->getPaymentMethods());
		Context::set('delivery_inquiry_urls', $this->delivery_inquiry_urls);
		$this->setTemplateFile('orderdetail');
	}

	function dispNcartAdminOrderManagement() 
	{
		$oNstoreModel = &getModel('nstore');
		$oMemberModel = &getModel('member');

		$config = $oNstoreModel->getModuleConfig();
		Context::set('config', $config);

		if(Context::get('status')===NULL) Context::set('status','1');
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
		if(!Context::get('s_year')) Context::set('s_year', date('Y'));
		$args->regdate = Context::get('s_year');
	   	if(Context::get('s_month')) $args->regdate = $args->regdate . Context::get('s_month');
		$output = executeQueryArray('ncart.getOrderListByStatus', $args);
		if(!$output->toBool()) 
		{
			return $output;
		}
		$order_list = $output->data;
		if(!is_array($order_list)) 
		{
			$order_list = array();
		}

		$member_config = $oMemberModel->getMemberConfig();
		$memberIdentifiers = array('user_id'=>'user_id', 'user_name'=>'user_name', 'nick_name'=>'nick_name');
		$usedIdentifiers = array();	

		if(is_array($member_config->signupForm))
		{
			foreach($member_config->signupForm as $signupItem)
			{
				if(!count($memberIdentifiers)) break;
				if(in_array($signupItem->name, $memberIdentifiers) && ($signupItem->required || $signupItem->isUse))
				{
					unset($memberIdentifiers[$signupItem->name]);
					$usedIdentifiers[$signupItem->name] = $lang->{$signupItem->name};
				}
			}
		}
		Context::set('list', $order_list);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);
		Context::set('delivery_companies', $this->delivery_companies);
		Context::set('order_status', $this->getOrderStatus());
		Context::set('delivery_inquiry_urls', $this->delivery_inquiry_urls);
		Context::set('usedIdentifiers', $usedIdentifiers);
		$this->setTemplateFile('ordermanagement');
	}
}
/* End of file ncart.admin.view.php */
/* Location: ./modules/ncart/ncart.admin.view.php */
