<?php
/**
 * vi:set ts=4 sw=4 noexpandtab fileencoding=utf-8:
 * @class epay
 * @author wiley@nurigo.net
 * @brief epay class
 **/
define('STATE_NOTCOMPLETED', '1');
define('STATE_COMPLETED', '2');
define('STATE_FAILURE', '3');

class epay extends ModuleObject 
{
	/**
	 * @brief Object를 텍스트의 %...% 와 치환.
	 **/
	function mergeKeywords($text, &$obj)
	{
		if (!is_object($obj)) return $text;
		foreach ($obj as $key => $val) {
			if (is_array($val)) $val = join($val);
			if (is_string($key) && is_string($val)) {
				if (substr($key,0,10)=='extra_vars') $val = str_replace('|@|', '-', $val);
				$text = preg_replace("/%" . preg_quote($key) . "%/", $val, $text);
			}
		}
		return $text;
	}

	/**
	 * @brief module uninstall
	 */
	function moduleInstall() 
	{
		$oModuleController = &getController('module');    
		return new Object();
	}

	/**
	 * @breif check to see if update is necessary
	 */
	function checkUpdate() 
	{
		$oModuleModel = &getModel('module');
		$oDB = &DB::getInstance();

		// 2012.02.07 add target_module
		if(!$oDB->isColumnExists("epay_transactions","target_module")) return true;

		// 2012-04-06 regdate index added.
		if (!$oDB->isIndexExists('epay_transactions', 'idx_regdate')) return true;

		// 2012-04-24 order_title column added.
		if (!$oDB->isColumnExists('epay_transactions','order_title')) return true;

		// 2013-07-28 regdate index added.
		if (!$oDB->isIndexExists('epay_transactions', 'idx_member_srl')) return true;

		// added on 2015/6/13
		if (!$oModuleModel->getTrigger('cympusadmin.getManagerMenu', 'epay', 'model', 'triggerGetManagerMenu', 'before')) return true;


		return false;
	}

	/**
	 * @breif module update
	 */
	function moduleUpdate() 
	{
		$oModuleController = &getController('module');    
		$oModuleModel = &getModel('module');
		$oDB = &DB::getInstance();	

		if (!$oDB->isColumnExists('epay_transactions','target_module')) 
		{
			$oDB->addColumn('epay_transactions','target_module', 'varchar','80');
		}

		// 2012-04-06 regdate index added.
		if (!$oDB->isIndexExists('epay_transactions', 'idx_regdate'))
		{
			$oDB->addIndex('epay_transactions', 'idx_regdate', 'regdate');
		}

		// 2012-04-24 order_title column added.
		if (!$oDB->isColumnExists('epay_transactions','order_title')) 
		{
			$oDB->addColumn('epay_transactions','order_title', 'varchar','250');
		}

		// 2013-07-28 p_member_srl index added.
		if (!$oDB->isIndexExists('epay_transactions', 'idx_member_srl'))
		{
			$oDB->addIndex('epay_transactions', 'idx_member_srl', 'p_member_srl');
		}

		// added on 2015/06/13
		if (!$oModuleModel->getTrigger('cympusadmin.getManagerMenu', 'epay', 'model', 'triggerGetManagerMenu', 'before')) {
			$oModuleController->insertTrigger('cympusadmin.getManagerMenu', 'epay', 'model', 'triggerGetManagerMenu', 'before');
		}


		return new Object(0, 'success_updated');
	}

	/**
	 * @brief module uninstall
	 */
	function moduleUninstall()
	{
		$oModuleController = &getController('module');
	}

	/**
	 * @brief recompile the cache after module install or update
	 */
	function recompileCache() 
	{
	}
}
/* End of file epay.class.php */
/* Location: ./modules/epay/epay.class.php */
