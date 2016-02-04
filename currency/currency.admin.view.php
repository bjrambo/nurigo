<?php
/**
 * @class currencyAdminView 
 */
class currencyAdminView extends currency 
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
		$this->setTemplatePath($this->module_path.'tpl');

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
	 * comment
	 */
	function dispCurrencyAdminContent()
	{
		$oCurrencyModel = &getModel('currency');
		$config = $oCurrencyModel->getModuleConfig();
		Context::set('config', $config);
		$this->setTemplateFile('index');
	}

}
/* End of file currency.admin.view.php */
/* Location: ./modules/currency/currency.admin.view.php */
