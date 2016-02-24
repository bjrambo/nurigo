<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  cympuserAdminView
 * @author billy(contact@nurigo.net)
 * @brief  cympuserAdminView
 */ 
class cympuserAdminView extends cympuser 
{
	function init() 
	{
		// module이 cympusadmin일때 관리자 레이아웃으로
		if(Context::get('module') == 'cympusadmin')
		{
			$classfile = _XE_PATH_ . 'modules/cympusadmin/cympusadmin.class.php';
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

		$oModuleModel = getModel('module');

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
				$oModuleModel->syncModuleToSite($module_info);
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
		$this->setTemplateFile('member_list');
		Context::set('tpl_path', $tpl_path);
	}

	function dispCympuserAdminModList()
	{
		$args = new stdClass();
		$args->sort_index = "module_srl";
		$args->page = Context::get('page');
		$args->list_count = 20;
		$args->page_count = 10;
		$output = executeQueryArray('cympuser.getModuleList', $args);
		if(!$output->toBool()) return $output;


		// 템플릿에 쓰기 위해서 context::set
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('list', $output->data);
		Context::set('page_navigation', $output->page_navigation);

		// 템플릿 파일 지정
		$this->setTemplateFile('mod_list');
	}

	function dispCympuserAdminModInsert()
	{
		// 스킨 목록을 구해옴
		$oModuleModel = &getModel('module');
		$skin_list = $oModuleModel->getSkins($this->module_path);
		Context::set('skin_list',$skin_list);

		// 레이아웃 목록을 구해옴
		$oLayoutMode = &getModel('layout');
		$layout_list = $oLayoutMode->getLayoutList();
		Context::set('layout_list', $layout_list);

		$module_srl = Context::get('module_srl');
		$args = new stdClass();
		if($module_srl)
		{
			$args->module_srl = $module_srl;
			$output = executeQuery('cympuser.getModuleInfo', $args);
			if(!$output->toBool()) return $output;
			Context::set('module_info', $output->data);
		}
		$this->setTemplateFile('mod_insert');
	}

	function dispCympuserAdminMemberList()
	{
		$oCympuserAdminModel = getAdminModel('cympuser');
		$oMemberModel = getModel('member');
		$output = $oCympuserAdminModel->getCympuserMemberList();

		$filter = Context::get('filter_type');
		global $lang;
		switch($filter)
		{
		case 'super_admin' : 
			Context::set('filter_type_title', $lang->cmd_show_super_admin_member);
			break;
		case 'site_admin' : 
			Context::set('filter_type_title', $lang->cmd_show_site_admin_member);
			break;
		default : 
			Context::set('filter_type_title', $lang->cmd_show_all_member);
			break;
		}
		// retrieve list of groups for each member
		if($output->data)
		{
			foreach($output->data as $key => $member)
			{
				$output->data[$key]->group_list = $oMemberModel->getMemberGroups($member->member_srl,0);
			}
		}
		$config = $this->memberConfig;
		$memberIdentifiers = array(
			'user_id'=>'user_id',
			'user_name'=>'user_name',
			'nick_name'=>'nick_name'
		);
		$usedIds = array();	

		if(is_array($config->signupForm))
		{
			foreach($config->signupForm as $signupItem)
			{
				if(!count($memberIdentifiers)) break;
				if(in_array($signupItem->name, $memberIdentifiers) 
					&& ($signupItem->required || $signupItem->isUse))
				{
					unset($memberIdentifiers[$signupItem->name]);
					$usedIds[$signupItem->name] = $lang->{$signupItem->name};
				}
			}
		}
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('member_list', $output->data);
		Context::set('usedIdentifiers', $usedIds);
		Context::set('page_navigation', $output->page_navigation);

		$security = new Security();
		$security->encodeHTML('member_list..user_name', 'member_list..nick_name', 'member_list..group_list..');

		$this->setTemplateFile('member_list');
	}

	/**
	 * display member insert form
	 *
	 * @return void
	 */
	function dispCympuserAdminMemberInsert()
	{
		// retrieve extend form
		$oMemberModel = getModel('member');

		$memberInfo = Context::get('member_info');
		if(isset($memberInfo))
		{
			$member_srl = $this->memberInfo->member_srl;
			$signature = $oMemberModel->getSignature($member_srl);
			$memberInfo->signature = $signature;
		}
		Context::set('member_info', $memberInfo);

		// get an editor for the signature
		if($memberInfo->member_srl)
		{
			$oEditorModel = getModel('editor');
			$option = new stdClass();
			$option->primary_key_name = 'member_srl';
			$option->content_key_name = 'signature';
			$option->allow_fileupload = false;
			$option->enable_autosave = false;
			$option->enable_default_component = true;
			$option->enable_component = false;
			$option->resizable = false;
			$option->height = 200;
			$editor = $oEditorModel->getEditor($member_srl, $option);
			Context::set('editor', $editor);
		}

		$formTags = $this->_getMemberInputTag($memberInfo, true);
		Context::set('formTags', $formTags);
		$member_config = $this->memberConfig;

		global $lang;
		$identifierForm = new stdClass();
		$identifierForm->title = $lang->{$member_config->identifier};
		$identifierForm->name = $member_config->identifier;
		$identifierForm->value = $memberInfo->{$member_config->identifier};
		Context::set('identifierForm', $identifierForm);
		$this->setTemplateFile('insert_member');
	}
}
