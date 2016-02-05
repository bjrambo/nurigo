<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nmileageAdminView
 * @author NURIGO(contact@nurigo.net)
 * @brief  nmileageAdminView
 */ 
class nmileageAdminView extends nmileage
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
		if($module_info && !in_array($module_info->module, array('nmileage')))
		{
			return $this->stop("msg_invalid_request");
		}

		// set template file
		$tpl_path = $this->module_path.'tpl';
		$this->setTemplatePath($tpl_path);
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

	function dispNmileageAdminConfig() 
	{
		$oNmileageModel = &getModel('nmileage');
		$oModuleModel = &getModel('module');

		$config = $oNmileageModel->getModuleConfig();
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

		$this->setTemplateFile('config');
	}


	function dispNmileageAdminModInstList() 
	{
		$args->sort_index = "module_srl";
		$args->page = Context::get('page');
		$args->list_count = 20;
		$args->page_count = 10;
		$args->s_module_category_srl = Context::get('module_category_srl');
		$output = executeQueryArray('nmileage.getModInstList', $args);
		$list = $output->data;

		if(!is_array($list)) 
		{
			$list = array();
		}

		Context::set('list', $list);

		$oModuleModel = &getModel('module');
		$module_category = $oModuleModel->getModuleCategories();
		Context::set('module_category', $module_category);

		$this->setTemplateFile('modinstlist');
	}

	function dispNmileageAdminInsertModInst() 
	{
		$oNmileageModel = &getModel('nmileage');

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

		$module_category = $oModuleModel->getModuleCategories();
		Context::set('module_category', $module_category);

		$this->setTemplateFile('insertmodinst');
	}

	function dispNmileageAdminAdditionSetup() 
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
	function dispNmileageAdminSkinInfo() 
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
	function dispNmileageAdminMobileSkinInfo() 
	{
		// 공통 모듈 권한 설정 페이지 호출
		$oModuleAdminModel = &getAdminModel('module');
		$skin_content = $oModuleAdminModel->getModuleMobileSkinHTML($this->module_info->module_srl);
		Context::set('skin_content', $skin_content);
		$this->setTemplateFile('skininfo');
	}

	function dispNmileageAdminMileageList() 
	{
		$oNmileageModel = &getModel('nmileage');
		$config = $oNmileageModel->getModuleConfig();
		Context::set('config', $config);

		if($config->mileage_method == 'nmileage')
		{
			$args->page = Context::get('page');
			$search_target = Context::get('search_target');
			$search_keyword = Context::get('search_keyword');
			switch($search_target)
			{
				case 'user_id':
					$args->user_id = $search_keyword;
					break;
				case 'nick_name':
					$args->nick_name = $search_keyword;
					break;
				case 'email_address':
					$args->email_address = $search_keyword;
					break;
			}
			$output = executeQueryArray('nmileage.getMileageList', $args);
		}
		else
		{
			$args->page = Context::get('page');
			$oPointModel = &getModel('point');
			$output = $oPointModel->getMemberList($args);
		}


		Context::set('list', $output->data);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);

		$this->setTemplateFile('mileagelist');
	}

	function dispNmileageAdminMileageHistory() 
	{
		$args->page = Context::get('page');
		$args->member_srl = Context::get('member_srl');
		$output = executeQueryArray('nmileage.getMileageHistory', $args);
		Context::set('list', $output->data);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);

		$oMemberModel = &getModel('member');
		$member_info = $oMemberModel->getMemberInfoByMemberSrl($args->member_srl);
		Context::set('member_info', $member_info);

		//
		$this->setTemplateFile('mileagehistory');
	}

	function dispNmileageAdminAllMileageHistory() 
	{
		$args->page = Context::get('page');
		$output = executeQueryArray('nmileage.getMileageHistory', $args);
		Context::set('list', $output->data);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);

		$oMemberModel = &getModel('member');
		$member_info = $oMemberModel->getMemberInfoByMemberSrl($args->member_srl);
		Context::set('member_info', $member_info);

		//
		$this->setTemplateFile('mileagehistory');
	}
}
/* End of file nmileage.admin.view.php */
/* Location: ./modules/nmileage/nmileage.admin.view.php */
