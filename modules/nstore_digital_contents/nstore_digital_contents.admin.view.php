<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nstore_digital_contentsAdminView
 * @author hosy(hosy@nurigo.net)
 * @brief  nstore_digital_contentsAdminView
 */ 
class nstore_digital_contentsAdminView extends nstore_digital_contents
{
	function nstore_digital_contentsAdminView()
	{
		$tpl_path = $this->module_path.'tpl';
		$this->setTemplatePath($tpl_path);

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

	function dispNstore_digital_contentsAdminItemList() 
	{
		$output = executeQueryArray('module.getMidList',$args);
		foreach($output->data as $k => $v)
		{
			if($v->module == 'nproduct') $nproduct_modules[] = $v;
		}

		Context::set('nproduct_modules', $nproduct_modules);

		if(Context::get('nproduct_srl')) Context::set('nproduct_srl', Context::get('nproduct_srl'));
		if(Context::get('item_name')) Context::set('item_name', Context::get('item_name'));

		$args->module_srl = Context::get('nproduct_srl');
		$args->item_name = Context::get('item_name');
		$args->page = Context::get('page');
		$args->proc_module = 'nstore_digital';

		$output = executeQueryArray('nproduct.getItemsByNodeRoute', $args);

		$oNstore_digitalModel = &getModel('nstore_digital_contents');

		if($output->data)
		{
			$item_list = $output->data;
			
			foreach ($item_list as $key=>$val) {
				$val->thumbnail = $oNstore_digitalModel->getThumbnail($val->thumb_file_srl,50);
			}

			Context::set('list', $item_list);
		}

		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);

		$this->setTemplateFile('itemlist');
	}

	/**
	 * 콘텐츠 관리
	 */
	function dispNstore_digital_contentsAdminManageContents()
	{
		$oModuleModel = &getModel('module');
		$oFileModel = &getModel('file');
		$oNproductModel = &getModel('nproduct');

		// get item info
		$item_info = $oNproductModel->getItemInfo(Context::get('item_srl'));
		if(!$item_info) return new Object(-1, 'msg_item_not_found');
		Context::set('item_info', $item_info);

		// get content list
		$args->item_srl = Context::get('item_srl');
		$output = executeQueryArray('nstore_digital_contents.getContentList', $args);
		$content_list = $output->data;
		foreach($content_list as $k => $v)
		{
			if($v->file_srl) 
			{
				$file = $oFileModel->getFile($v->file_srl);
				if($file) $v->download_file = $file;
			}
		}
		Context::set('content_list', $content_list);

		$output = executeQuery('nstore_digital_contents.getConfig', $args);
		if(!$output->toBool()) return $output;
		if($output->data)
		{
			Context::set('config', $output->data);
			if($output->data->extra_vars) Context::set('extra_vars', unserialize($output->data->extra_vars));
		}

		$this->setTemplateFile('managecontents');
	}

	function dispNstore_digital_contentsAdminInsertConfig() 
	{ 
		$oModuleModel = &getModel('module');
		$nstore_digital_contents_config = $oModuleModel->getModuleConfig('nstore_digital_contents');
		if($nstore_digital_contents_config) Context::set('config', $nstore_digital_contents_config);
			
		$this->setTemplateFile('insertconfig');	
	}
}
/* End of file nstore_digital_contents.admin.view.php */
/* Location: ./modules/nstore_digital_contents/nstore_digital_contents.admin.view.php */
