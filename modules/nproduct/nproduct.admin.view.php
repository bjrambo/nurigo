<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nproductAdminView
 * @author NURIGO(contact@nurigo.net)
 * @brief  nproductAdminView
 */ 
require_once(_XE_PATH_.'modules/nproduct/nucommon.class.php');
class nproductAdminView extends nproduct
{
	/**
	 * @brief Contructor
	 **/
	function init() 
	{
		$oModuleModel = &getModel('module');

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

		// module_srl이 있으면 미리 체크하여 존재하는 모듈이면 module_info 세팅
		$module_srl = Context::get('module_srl');
		if(!$module_srl && $this->module_srl)
		{
			$module_srl = $this->module_srl;
			Context::set('module_srl', $module_srl);
		}

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
		if($module_info && !in_array($module_info->module, array('nproduct')))
		{
			return $this->stop("msg_invalid_request");
		}

		// set template file
		$tpl_path = $this->module_path.'tpl';
		$this->setTemplatePath($tpl_path);
		$this->setTemplateFile('index');
		Context::set('tpl_path', $tpl_path);
	}

	/**
	 * @brief 제품등록인증
	 */
	function getLicenseFromAgency()
	{
		$oNproductModel = &getModel('nproduct');
		$config = $oNproductModel->getModuleConfig();
		return nucommon::getLicenseFromAgency('nproduct', $config->user_id, $config->serial_number);
	}

	/**
	 * @brief 
	 */
	function dispNproductAdminCategoryManagement() 
	{
		$this->setTemplateFile('categorymanagement');
    }

	/**
	 * @brief 
	 */
	function dispNproductAdminInsertItem() 
	{
		$oEditorModel = &getModel('editor');
		$oNproductAdminController = &getAdminController('nproduct');
		$oNproductModel = &getModel('nproduct');

		//dynamic ruleset 재생성
		$extra_vars = $oNproductModel->getItemExtraFormList($this->module_info->module_srl);
		$oNproductAdminController->_createInsertItemRuleset($extra_vars);

		$document_srl = getNextSequence();
		Context::set('document_srl', $document_srl);
		Context::set('editor', $oEditorModel->getModuleEditor('document', $this->module_info->module_srl, $document_srl, 'document_srl', 'description'));
		//Context::set('editor2', $oEditorModel->getModuleEditor('document', $this->module_info->module_srl, 0, 0, 'delivery_info'));

		// extra vars
		$item_info = new stdclass();
		$item_info->module_srl = $this->module_info->module_srl;
		Context::set('extra_vars', NExtraItemList::getList($item_info));

		$module_list = array();
		$output = ModuleHandler::triggerCall('nproduct.getProcModules', 'before', $module_list);
		if(!$output->toBool()) return $output;

		//$module_name = Context::get('proc_module');

		Context::set('module_list', $module_list);
	}

	/**
	 * @breif supports item modification form.
	 */
	function dispNproductAdminUpdateItem() 
	{
		// get the references of modules.
		$oFileModel = &getModel('file');
		$oEditorModel = &getModel('editor');
		$oDocumentModel = &getModel('document');
		$oNproductModel = &getModel('nproduct');

		// get form parameters
		$item_srl = Context::get('item_srl');

		// query item record
		$item_info = $oNproductModel->getItemInfo($item_srl);
		if(!$item_info) return new Object(-1, 'msg_item_not_found');

		// category infos.
		$node_route_arr = preg_split('/\./', $item_info->node_route);
		$avoid_last = array_pop($node_route_arr);
		$node_route_arr[] = $item_info->category_id;
		$category_data = new StdClass();
		$category_data->list = array();
		$count=0;
		$node_route = '';
		foreach ($node_route_arr as $node_id) 
		{
			if(!$node_id) continue;
			if(Context::get('module_srl')) 
			{
				$node_route = $node_route . $node_id .'.';
				$args->node_route = $node_route;
				$args->module_srl = Context::get('module_srl');
				$output = executeQueryArray('nproduct.getCategoryList', $args);
				if(!$output->toBool()) return $output;
				unset($args);
				$category_data->list[] = $output->data;
			}
			eval("\$category_data->depth{$count} = $node_id;");
			$count+=1;
		}
		Context::set('category_data', $category_data);

		$item_info->group_srl_list = unserialize($item_info->group_srl_list);
		if (!is_array($item_info->group_srl_list)) $item_info->group_srl_list = array();

		// 콘텐츠 파일 (앞으로 사용하지 않을 필드)
		if($item_info->file_srl) 
		{
			$file = $oFileModel->getFile($item_info->file_srl);
			if($file) $item_info->download_file = $file;
		}
		// get thumbnail URL
		if($item_info->thumb_file_srl) 
		{
			$file = $oFileModel->getFile($item_info->thumb_file_srl);
			if($file) $item_info->thumbnail_url = getFullUrl().$file->download_url;
		}

		// check if related_items data is json formatted or not
		// 기존 related_items에 값이 CSV(comma seperated values) 형식으로 되어 있어서 csv를 json으로 변환해 준다.
		if(!$this->isJson($item_info->related_items)) $item_info->related_items = $this->convertCsvToJson($item_info->related_items);

		// pass variables to html
		Context::set('oDocument', $oDocumentModel->getDocument($item_info->document_srl));
		Context::set('editor', $oEditorModel->getModuleEditor('document', $this->module_info->module_srl, $item_info->document_srl, 'document_srl', 'description'));
		Context::set('item_info', $item_info);
		Context::set('extra_vars', NExtraItemList::getList($item_info));

		// get groups
		$oMemberModel = &getModel('member');
		$group_list = $oMemberModel->getGroups();
		Context::set('group_list', $group_list);

		// group discount
		$args->item_srl = $item_srl;
		$output = executeQueryArray('nproduct.getGroupDiscount', $args);
		if(!$output->toBool()) return $output;
		$output_data = $output->data;
		$group_discount = array();
		if($output_data) 
		{
			foreach ($output_data as $key=>$val)
			{
				$group_discount[$val->group_srl] = $val;
			}
		}
		Context::set('group_discount', $group_discount);

		// get options
		$args->item_srl = $item_srl;
		$output = executeQueryArray('nproduct.getOptions', $args);
		if(!$output->toBool()) return $output;
		Context::set('options', $output->data);

		// dynamic ruleset 재생성
		$oNproductAdminController = &getAdminController('nproduct');
		$oNproductModel = &getModel('nproduct');
		$extra_vars = $oNproductModel->getItemExtraFormList($this->module_info->module_srl);
		$oNproductAdminController->_createInsertItemRuleset($extra_vars);

		// get module instance list
		$args->list_count = 1000;
		$output = executeQueryArray('nproduct.getModInstList', $args);
		$list = $output->data;
		Context::set('modinst', $list);

		// get proc_modules
		$module_list = array();
		$output = ModuleHandler::triggerCall('nproduct.getProcModules', 'before', $module_list);
		if(!$output->toBool()) return $output;
		Context::set('module_list', $module_list);
	}

	/**
	 * @brief 
	 */
	function dispNproductAdminItemList() 
	{
		$oNproductModel = &getModel('nproduct');
		$oNproductView = &getView('nproduct');

		$oNproductView->getCategoryTree($this->module_info->module_srl);

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
			$sort_index = "list_order";
		}
		if(!$order_type) 
		{
			$order_type = 'asc';
		}

		if(!$category)
		{
			$s_item_name = Context::get('s_item_name');
			$args->module_srl = Context::get('module_srl');
			//$args->node_route = 'f.';
			$args->page = Context::get('page');
			$args->list_count = $list_count;
			$args->sort_index = $sort_index;
			$args->order_type = $order_type;
			$args->item_name = $s_item_name;
			$output = executeQueryArray('nproduct.getItemsByNodeRoute', $args);
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
		else if($category) 
		{
			$category_info = $oNproductModel->getCategoryInfo($category);

			$args->module_srl = Context::get('module_srl');
			$args->node_route = $category_info->node_route . $category_info->node_id . '.';
			$args->page = Context::get('page');
			$args->list_count = $list_count;
			$args->sort_index = $sort_index;
			$args->order_type = $order_type;
			$output = executeQueryArray('nproduct.getItemsByNodeRoute', $args);
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
			foreach ($item_list as $key=>$val) 
			{
				$item_list[$key] = new nproductItem($val);
			}
		}

		Context::set('list', $item_list);

		// front display
		$display_categories = $oNproductModel->getFrontDisplayItems($this->module_info->module_srl);
		Context::set('display_categories', $display_categories);
		$this->setTemplateFile('itemlist');
	}

	/**
	 * @brief display module instance list
	 */
	function dispNproductAdminModInstList() 
	{
		$oModuleModel = &getModel('module');

		$args->sort_index = "module_srl";
		$args->page = Context::get('page');
		$args->list_count = 20;
		$args->page_count = 10;
		$args->s_module_category_srl = Context::get('module_category_srl');
		$output = executeQueryArray('nproduct.getModInstList', $args);
		if(!$output->toBool()) return $output;
		$list = $output->data;
		$list = $oModuleModel->addModuleExtraVars($list);

		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);
		Context::set('list', $list);

		// 상품타입 정보 가져오기
		$module_list = array();
		$output = ModuleHandler::triggerCall('nproduct.getProcModules', 'before', $module_list);
		if(!$output->toBool()) return $output;
		Context::set('module_list', $module_list);


		$oModuleModel = &getModel('module');
		$module_category = $oModuleModel->getModuleCategories();
		Context::set('module_category', $module_category);
	}

	/**
	 * @brief display module config info
	 */
	function dispNproductAdminConfig() 
	{
		$oNproductModel = &getModel('nproduct');
		$oModuleModel = &getModel('module');

		$config = $oNproductModel->getModuleConfig();
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

	/**
	 * @brief display insert module instance page
	 **/
	function dispNproductAdminInsertModInst() 
	{
		$oNcartModel = &getModel('ncart');
		$oModuleModel = &getModel('module');
		$oLayoutModel = &getModel('layout');
		$oEditorModel = &getModel('editor');

		// 스킨 목록을 구해옴
		$skin_list = $oModuleModel->getSkins($this->module_path);
		Context::set('skin_list',$skin_list);
		// 모바일 스킨 목록
		$mskin_list = $oModuleModel->getSkins($this->module_path, "m.skins");
		Context::set('mskin_list', $mskin_list);

		// 레이아웃 목록을 구해옴
		$layout_list = $oLayoutModel->getLayoutList();
		Context::set('layout_list', $layout_list);
		// 모바일 레이아웃 목록
		$mobile_layout_list = $oLayoutModel->getLayoutList(0,"M");
		Context::set('mlayout_list', $mobile_layout_list);

		$module_list = array();
		$output = ModuleHandler::triggerCall('nproduct.getProcModules', 'before', $module_list);
		if(!$output->toBool()) return $output;
		Context::set('module_list', $module_list);

		if($oNcartModel)
		{
			$ncart_insts = $oNcartModel->getModInstList();
			Context::set('ncart_insts', $ncart_insts);
		}

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
	}

	/**
	 * @brief display addition setup page
	 **/
	function dispNproductAdminAdditionSetup() 
	{
		// content는 다른 모듈에서 call by reference로 받아오기에 미리 변수 선언만 해 놓음
		$content = '';

		// get the addtional setup trigger
		// the additional setup triggers can be used in many modules
		$output = ModuleHandler::triggerCall('module.dispAdditionSetup', 'before', $content);
		$output = ModuleHandler::triggerCall('module.dispAdditionSetup', 'after', $content);

		//$oEditorView = &getView('editor');
		//$oEditorView->triggerDispEditorAdditionSetup($content);
		Context::set('setup_content', $content);
	}

	/**
	 * @brief display category list page
	 **/
	function dispNproductAdminDisplayCategories() 
	{
		$args->module_srl = Context::get('module_srl');
		$output = executeQueryArray('nproduct.getDisplayCategoryList', $args);
		if(!$output->toBool()) return $output;
		Context::set('list', $output->data);
	}

	/**
	 * @brief display extra item setup page
	 **/
	function dispNproductAdminItemExtraSetup() 
	{
		$args->module_srl = Context::get('module_srl');
		$output = executeQueryArray('nproduct.getItemExtraList', $args);
		if(!$output->toBool()) return $output;
		$ExtraList = $output->data;
		$oModel = &getModel($this->module_info->proc_module);

		foreach($ExtraList as $key => $val)
		{
			$check_name = $oModel->checkNproductExtraName($val->column_name);
			if($check_name == true) $val->index_extra = "true";
		}

		Context::set('list', $ExtraList);
	}

	/**
	 * @brief 스킨 정보 보여줌
	 **/
	function dispNproductAdminSkinInfo() 
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
	function dispNproductAdminMobileSkinInfo() 
	{
		// 공통 모듈 권한 설정 페이지 호출
		$oModuleAdminModel = &getAdminModel('module');
		$skin_content = $oModuleAdminModel->getModuleMobileSkinHTML($this->module_info->module_srl);
		Context::set('skin_content', $skin_content);
		$this->setTemplateFile('skininfo');
	}

	/**
	 * @brief display bulk item page
	 **/
	function dispNproductAdminBulkItems() 
	{
		$oNproductModel = &getModel('nproduct');

		$item_list = Context::get('item_list');
		$lines = explode("\n", $item_list);
		Context::set('item_list','');
		$update_list = array();
		$original_list = array();
		foreach ($lines as $line) 
		{
			$line = trim($line);
			$columns = explode("\t", $line);
			if(count($columns) != 5)
			{
				continue;
			}
			$obj = new StdClass();
			$obj->item_code = $columns[0];
			$obj->item_name = $columns[1];
			$obj->taxfree = ($columns[2] == '비과세') ? 'Y' : 'N';
			$obj->display = ($columns[3] == '진열함') ? 'Y' : 'N';
			$obj->price = $columns[4];
			$update_list[$obj->item_code] = $obj;

			$item_info = $oNproductModel->getItemByCode($obj->item_code);
			$original_list[$obj->item_code] = $item_info;
		}

		Context::set('original_list', $original_list);
		Context::set('update_list', $update_list);
	}

	/**
	 * @brief display list setup page
	 **/
	function dispNproductAdminListSetup()
	{
		$oNproductModel = &getModel('nproduct');
		$oModuleController = &getController('module');
		
		// 설정 항목 추출 (설정항목이 없을 경우 기본 값을 세팅)
		$args->module_srl = $this->module_info->module_srl;
		$args->module = 'nproduct';
		$output = executeQuery('module.getModulePartConfig', $args);
		if(!$output->data->config)
			$oModuleController->insertModulePartConfig('nproduct',$this->module_info->module_srl, $config);

		if($oNproductModel->getListConfig($this->module_info->module_srl))
			Context::set('list_config', $oNproductModel->getListConfig($this->module_info->module_srl));

		Context::set('extra_vars', $oNproductModel->getDefaultListConfig($this->module_info->module_srl));

		$security = new Security();
		$security->encodeHTML('list_config');
	}

	/**
	 * @brief display detail list setup page
	 **/
	function dispNproductAdminDetailListSetup()
	{
		$oNproductModel = &getModel('nproduct');
		$oModuleController = &getController('module');
		
		// 설정 항목 추출 (설정항목이 없을 경우 기본 값을 세팅)
		$args->module_srl = $this->module_info->module_srl;
		$args->module = 'nproduct.detail';
		$output = executeQuery('module.getModulePartConfig', $args);
		if(!$output->data->config)
			$oModuleController->insertModulePartConfig('nproduct.detail',$this->module_info->module_srl, $config);

		if($oNproductModel->getDetailListConfig($this->module_info->module_srl))
			Context::set('list_config', $oNproductModel->getDetailListConfig($this->module_info->module_srl));

		Context::set('extra_vars', $oNproductModel->getDefaultListConfig($this->module_info->module_srl));

		$security = new Security();
		$security->encodeHTML('detail_list_config');

		$this->setTemplateFile('detaillistsetup');
	}

	/**
	 * @brief display group discount page
	 **/
	function dispNproductAdminGroupDiscount()
	{
		// get groups
		$oMemberModel = &getModel('member');
		$group_list = $oMemberModel->getGroups();
		Context::set('group_list', $group_list);

		$args->module_srl = $this->module_info->module_srl;
		$output = executeQueryArray('nproduct.getGlobalGroupDiscount', $args);
		if(!$output->toBool()) return $output;

		$output_data = $output->data;
		$group_discount = array();
		if($output_data) 
		{
			foreach ($output_data as $key=>$val) 
			{
				$group_discount[$val->group_srl] = $val;
			}
		}
		Context::set('group_discount', $group_discount);

		$this->setTemplateFile('groupdiscount');
	}

	/**
	 * @brief display the grant information
	 **/
	function dispNproductAdminGrantInfo() {
		// get the grant infotmation from admin module
		$oModuleAdminModel = &getAdminModel('module');
		$grant_content = $oModuleAdminModel->getModuleGrantHTML($this->module_info->module_srl, $this->xml_info->grant);
		Context::set('grant_content', $grant_content);

		$this->setTemplateFile('grantinfo');
	}

	/**
	 * @brief display item list to download excel
	 **/
	function dispNproductAdminItemListExcelDownload() {
		$oNproductModel = &getModel('nproduct');
		$oNproductView = &getView('nproduct');

		$oNproductView->getCategoryTree($this->module_info->module_srl);

		$category = Context::get('category');
		$list_count = Context::get('disp_numb');
		$sort_index = Context::get('sort_index');
		$order_type = Context::get('order_type');

		if(!$list_count) $list_count = 30;
		if(!$sort_index) $sort_index = "item_srl";
		if(!$order_type) $order_type = 'asc';

		if($category) 
		{
			$category_info = $oNproductModel->getCategoryInfo($category);

			$args->module_srl = Context::get('module_srl');
			$args->node_route = $category_info->node_route . $category_info->node_id . '.';
			$args->page = Context::get('page');
			$args->list_count = $list_count;
			$args->sort_index = $sort_index;
			$args->order_type = $order_type;
			$output = executeQueryArray('nproduct.getItemsByNodeRoute', $args);
			if(!$output->toBool()) return $output;

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
			$output = executeQueryArray('nproduct.getItemsByNodeRoute', $args);
			if(!$output->toBool()) return $output;

			$item_list = $output->data;
			Context::set('total_count', $output->total_count);
			Context::set('total_page', $output->total_page);
			Context::set('page', $output->page);
			Context::set('page_navigation', $output->page_navigation);
		}

		if($item_list) 
		{
			foreach ($item_list as $key=>$val) 
			{
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

	/**
	 * @brief display member discount page
	 **/
	function dispNproductAdminMemberDiscount()
	{
		$oMemberModel = &getModel('member');

		$args->page = Context::get('page');

		$output = executeQueryArray('nproduct.getMemberDiscount', $args);
		if(!$output->toBool()) return $output;

		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);

		if($output->data)
		{
			$member_discount_list = $output->data;

			foreach($member_discount_list as $key => $val)
			{
				$member_info = $oMemberModel->getMemberInfoByMemberSrl($val->member_srl);
				$val->user_id = $member_info->user_id;
				$val->regdate = substr($val->regdate, 0, 8);
			}

			Context::set('member_discount_list', $member_discount_list);
		}

		$this->setTemplateFile('memberdiscount');
	}

	/**
	 * @brief display quantity discount page
	 **/
	function dispNproductAdminQuantityDiscount()
	{
		$oNproductModel = &getModel('nproduct');

		$args->page = Context::get('page');
		$output = executeQueryArray('nproduct.getQuantityDiscount', $args);
		if(!$output->toBool()) return $output;

		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);

		if($output->data) 
		{
			$quantity_discount_list = $output->data;
			
			foreach($quantity_discount_list as $key => $val)
			{
				$item_info = $oNproductModel->getItemInfo($val->item_srl);
				$val->item_name = $item_info->item_name;
				$val->regdate = substr($val->regdate, 0, 8);
			}
			Context::set('quantity_discount_list', $quantity_discount_list);
		}

		$this->setTemplateFile('quantitydiscount');
	}

}
/* End of file nproduct.admin.view.php */
/* Location: ./modules/nproduct/nproduct.admin.view.php */
