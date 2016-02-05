<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  licenseAdminView
 * @author NURIGO(contact@nurigo.net)
 * @brief  licenseAdminView
 */ 
class licenseAdminView extends license
{
	/**
	 * @brief Contructor
	 **/

	function init() 
	{
		// set template file
		$tpl_path = $this->module_path.'tpl';
		$this->setTemplatePath($tpl_path);

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

	function dispLicenseAdminConfig() 
	{
		$oLicenseModel = &getModel('license');
		$oModuleModel = &getModel('module');
		$config = $oLicenseModel->getModuleConfig();
		Context::set('config',$config);

		$products = array(); // 'nstore', 'nstore_digital', 'elearning');
		if(getClass('nstore')) $products[] = 'nstore';
		if(getClass('nstore_digital')) $products[] = 'nstore_digital';
		if(getClass('elearning')) $products[] = 'elearning';
		Context::set('products', $products);

		foreach($products as $key=>$prodid)
		{
			$has_license = TRUE;
			$expiration = NULL;
			if($oLicenseModel->getLicenseFromAgency($prodid, $has_license, $expiration)) $oLicenseModel->getLicenseFromAgency($prodid, $has_license, $expiration);
			Context::set(sprintf('%s_expiration', $prodid), $expiration);
		}

		$this->setTemplateFile('index');
	}

	function getNewsFromAgency()
	{
		//Retrieve recent news and set them into context
		$newest_news_url = sprintf("http://store.nurigo.net/?module=newsagency&act=getNewsagencyArticle&inst=notice&top=6&loc=%s", _XE_LOCATION_);
		$cache_file = sprintf("%sfiles/cache/license_news.%s.cache.php", _XE_PATH_, _XE_LOCATION_);
		if(!file_exists($cache_file) || filemtime($cache_file)+ 60*60 < time())
		{
			// Considering if data cannot be retrieved due to network problem, modify filemtime to prevent trying to reload again when refreshing textmessageistration page
			// Ensure to access the textmessageistration page even though news cannot be displayed
			FileHandler::writeFile($cache_file,'');
			FileHandler::getRemoteFile($newest_news_url, $cache_file, null, 1, 'GET', 'text/html', array('REQUESTURL'=>getFullUrl('')));
		}

		if(file_exists($cache_file)) 
		{
			$oXml = new XmlParser();
			$buff = $oXml->parse(FileHandler::readFile($cache_file));

			$item = $buff->zbxe_news->item;
			if($item) 
			{
				if(!is_array($item)) 
				{
					$item = array($item);
				}

				foreach($item as $key => $val) {
					$obj = null;
					$obj->title = $val->body;
					$obj->date = $val->attrs->date;
					$obj->url = $val->attrs->url;
					$news[] = $obj;
				}
				Context::set('news', $news);
			}
			Context::set('released_version', $buff->zbxe_news->attrs->released_version);
			Context::set('download_link', $buff->zbxe_news->attrs->download_link);
		}
	}
}

/* End of file license.admin.view.php */
/* Location: ./modules/license/license.admin.view.php */
