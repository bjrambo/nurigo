<?php
/**
 * @class currencyAdminModel 
 */
class currencyAdminModel extends currency 
{
	var $module_srl = 0;
	var $list_count = 20;
	var $page_count = 10;

	/**
	 * @brief Initialization
	 */
	function init()
	{
		// Get a template path (page in the administrative template tpl putting together)
		$this->setTemplatePath($this->module_path.'tpl');
	}

}
/* End of file currency.admin.model.php */
/* Location: ./modules/currency/currency.admin.model.php */
