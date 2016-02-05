<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  paynotyView
 * @author NURIGO(contact@nurigo.net)
 * @brief  paynotyView
 */
class paynotyView extends paynoty 
{
	function init() 
	{
		// 템플릿 설정
		$this->setTemplatePath($this->module_path.'tpl');
	}
}
?>
