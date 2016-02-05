<?php
/**
 * @class  currencyAdminController
 */
class currencyAdminController extends currency 
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	/**
	 * @save config setting 
	 */
	function procCurrencyAdminConfig()
	{
		$args = Context::getRequestVars();
		$oModuleControll = getController('module');
		$output = $oModuleControll->insertModuleConfig('currency', $args);
		if(!$output->toBool()) return $output;
		$this->setMessage('success_updated');

		$returnUrl = getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispCurrencyAdminContent');
		$this->setRedirectUrl($returnUrl);
	}
}
/* End of file currency.admin.controller.php */
/* Location: ./modules/currency/currency.admin.controller.php */
