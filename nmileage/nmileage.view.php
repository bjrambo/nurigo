<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nmileageView
 * @author NURIGO(contact@nurigo.net)
 * @brief  nmileageView
 */
class nmileageView extends nmileage
{
	function init()
	{
		if($this->module_info->module != 'nmileage') $this->module_info->skin = 'default';
		if(!$this->module_info->skin) $this->module_info->skin = 'default';
		$skin = $this->module_info->skin;
		$oModuleModel = &getModel('module');
		// 템플릿 경로 설정
		$this->setTemplatePath(sprintf('%sskins/%s', $this->module_path, $skin));

		$oLicenseModel = &getModel('license');
		if(!$oLicenseModel || ($oLicenseModel && !$oLicenseModel->getLicenseConfirm()))
		{
			Context::addHtmlHeader("<script>jQuery(document).ready(function() { jQuery('<div style=\"background:#fff; padding:6px; position:fixed; right:6px; bottom:6px; z-index:999999; \">Powered by <a href=\"http://www.xeshoppingmall.com\">NURIGO</a></div>').appendTo('body'); });</script>");
		}
	}

	function dispNmileageMileageHistory() 
	{
		$oNmileageModel = &getModel('nmileage');

		$logged_info = Context::get('logged_info');

		if(!$logged_info) return new Object(-1, "msg_login_required");

		$args->member_srl = $logged_info->member_srl;
		$args->page = Context::get('page');
		$args->regdate_more = $start_date;

		$output = executeQueryArray('nmileage.getMileageHistory', $args);

		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);

		Context::set('list', $output->data);
		Context::set('colorset', $this->module_info->colorset);

		$mileage = $oNmileageModel->getMileage($logged_info->member_srl);
		Context::set('mileage', $mileage);
		
		$this->setTemplateFile('mileagehistory');
	}

	function dispNmileageMyMileage() 
	{
		$oNmileageModel = &getModel('nmileage');

		$logged_info = Context::get('logged_info');

		$args->member_srl = $logged_info->member_srl;
		$output = executeQueryArray('nmileage.getMileageHistory', $args);
		Context::set('list', $output->data);
		Context::set('colorset', 'transparent');

		$mileage = $oNmileageModel->getMileage($logged_info->member_srl);
		Context::set('mileage', $mileage);
		
		$this->setTemplateFile('mymileage');
	}
}
/* End of file nmileage.view.php */
/* Location: ./modules/nmileage/nmileage.view.php */
