<?php
/**
 * cympusadminAdminView class
 * Admin view class of cympusadmin module
 *
 * @author NURIGO (contact@nurigo.net)
 * @package /modules/cympusadmin
 * @version 0.1
 */
class cympusadminAdminView extends cympusadmin 
{
	/**
	 * @brief initialize
	 **/
	function init() 
	{
		if(Context::get('module') != 'admin') parent::init();
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
			}
			else
			{
				ModuleModel::syncModuleToSite($module_info);
				$this->module_info = $module_info;
				Context::set('module_info',$module_info);
			}
		}
		if($module_info && !in_array($module_info->module, array('cympusadmin')))
		{
			return $this->stop("msg_invalid_request");
		}

		// set template file
		$tpl_path = $this->module_path.'tpl';
		$this->setTemplatePath($tpl_path);
	}

	function dispCympusadminAdminIndex()
	{
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile(_CYMPUSADMIN_INDEX_);

		$status = getCympusStatus();
		Context::set('status', $status);
	}

	function dispCympusadminAdminModInstList() 
	{
		$oModuleModel = &getModel('module');

		$args->sort_index = "module_srl";
		$args->page = Context::get('page');
		$args->list_count = 20;
		$args->page_count = 10;
		$args->s_module_category_srl = Context::get('module_category_srl');
		$output = executeQueryArray('cympusadmin.getModInstList', $args);
		$list = $output->data;
		$list = $oModuleModel->addModuleExtraVars($list);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);
		Context::set('list', $list);

		$oModuleModel = &getModel('module');
		$module_category = $oModuleModel->getModuleCategories();
		Context::set('module_category', $module_category);

		$this->setTemplateFile('modinstlist');
	}

	function dispCympusadminAdminInsertModInst() 
	{
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


	/**
	 * @brief display the grant information
	 **/
	function dispCympusadminAdminGrantInfo() {
		// get the grant infotmation from admin module
		$oModuleAdminModel = &getAdminModel('module');
		$grant_content = $oModuleAdminModel->getModuleGrantHTML($this->module_info->module_srl, $this->xml_info->grant);
		Context::set('grant_content', $grant_content);

		$this->setTemplateFile('grantinfo');
	}

}
