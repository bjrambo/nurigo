<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  cympuserView
 * @author billy(contact@nurigo.net)
 * @brief  cympuserView
 */
class cympuserView extends cympuser 
{
	function init() 
	{
		// 템플릿 경로 설정
		if(!$this->module_info->skin) $this->module_info->skin = 'default';

		$path = "skins/{$this->module_info->skin}";
		$this->setTemplatePath($this->module_path.$path);
		Context::set('module_info',$this->module_info);
	}



}
?>
