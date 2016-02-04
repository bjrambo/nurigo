<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nproductAdminModel
 * @author NURIGO(contact@nurigo.net)
 * @brief  nproductAdminModel
 */ 
class nproductAdminModel extends nproduct
{
	/**
	 * @brief get insert extra item form
	 *
	 */
	function getNproductAdminInsertItemExtra() 
	{
		$extra_srl = Context::get('extra_srl');

		$args->extra_srl = $extra_srl;
		$output = executeQuery('nproduct.getItemExtra', $args);

		if($output->toBool() && $output->data)
		{
			$formInfo = $output->data;
			$default_value = $formInfo->default_value;
			if($default_value)
			{
				$default_value = unserialize($default_value);
				Context::set('default_value', $default_value);
			}
			Context::set('formInfo', $output->data);
		}

		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path.'tpl', 'form_insert_item_extra');

		$this->add('tpl', str_replace("\n"," ",$tpl));
	}

	/**
	 * @brief get insert delivery info form
	 *
	 */
	function getNproductAdminInsertDeliveryInfo() 
	{
		$item_srl = Context::get('item_srl');

		$args->item_srl = $item_srl;
		$output = executeQuery('nproduct.getItemInfo', $args);

		if($output->toBool() && $output->data)
		{
			$formInfo = $output->data;
			$default_value = $formInfo->default_value;
			if($default_value)
			{
				$default_value = unserialize($default_value);
				Context::set('default_value', $default_value);
			}
			Context::set('formInfo', $output->data);
		}

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
		$option->primary_key_name = 'item_srl';
		$option->content_key_name = 'delivery_info';
		$editor = $oEditorModel->getEditor(0, $option);
		Context::set('editor', $editor);

		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path.'tpl', 'form_insert_delivery_info');

		$this->add('tpl', str_replace("\n"," ",$tpl));
	}

	/**
	 * @brief get display category info
	 *
	 */
	function getNproductAdminDisplayCategory() {
		$args->category_srl = Context::get('category_srl');
		$output = executeQuery('nproduct.getDisplayCategoryInfo', $args);
		if(!$output->toBool()) return $output;

		$this->add('data', $output->data);
	}

	/**
	 * @brief get delete module instance form
	 *
	 */
	function getNproductAdminDeleteModInst() {
		$oModuleModel = &getModel('module');

		$module_srl = Context::get('module_srl');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
		Context::set('module_info', $module_info);

		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path.'tpl', 'form_delete_modinst');
		$this->add('tpl', str_replace("\n"," ",$tpl));
	}

	/**
	 * @brief get mileage plus form
	 *
	 */
	function getNproductAdminPlusMileage() {
		$oModuleModel = &getModel('module');

		$module_srl = Context::get('module_srl');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
		Context::set('module_info', $module_info);

		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path.'tpl', 'form_plus_mileage');
		$this->add('tpl', str_replace("\n"," ",$tpl));
	}

	/**
	 * @brief get item delete form
	 *
	 */
	function getNproductAdminDeleteItem() 
	{
		$oNstore_coreModel = &getModel('nproduct');
		$item_srl = Context::get('item_srl');
		$item_info = $oNstore_coreModel->getItemInfo($item_srl);
		Context::set('item_info', $item_info);

		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path.'tpl', 'form_delete_item');
		$this->add('tpl', str_replace("\n"," ",$tpl));
	}

	/**
	 * @brief get insert option form
	 *
	 */
	function getNproductAdminInsertOptions() 
	{
		$oNstore_coreModel = &getModel('nproduct');

		$item_srl = Context::get('item_srl');
		$options = $oNstore_coreModel->getOptions($item_srl);
		Context::set('options', $options);

		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path.'tpl', 'form_insert_options');
		$this->add('tpl', str_replace("\n"," ",$tpl));
	}

	/**
	 * @brief get all categories
	 *
	 */
	function getNproductAdminAllCategories() 
	{
		$args->module_srl = Context::get('module_srl');
		$output = executeQueryArray('nproduct.getAllCategories', $args);
		$this->add('data', $output->data);
	}
}
/* End of file nproduct.admin.model.php */
/* Location: ./modules/nproduct/nproduct.admin.model.php */
