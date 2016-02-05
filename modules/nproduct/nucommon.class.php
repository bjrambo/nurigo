<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nucommon
 * @author NURIGO(contact@nurigo.net)
 * @brief  nucommon
 */ 
class nucommon
{
	/**
	 * @brief parse an xml file and generate administrator's menus.
	 */
	function getMenu(&$in_xml_obj, $depth=0,&$parent_item=null) 
	{
		if(!is_array($in_xml_obj)) 
		{
			$xml_obj = array($in_xml_obj);
		}
		else 
		{
			$xml_obj = $in_xml_obj;
		}
		$act = Context::get('act');

		$menus = array();
		foreach ($xml_obj as $it) 
		{
			$obj = new StdClass();
			$obj->id = $it->id->body;
			if($parent_item) $obj->parent_id = $parent_item->id;

			$obj->title = $it->title->body;
			$obj->action = array();
			if(is_array($it->action))
			{
				foreach ($it->action as $action)
				{
					$obj->action[] = $action->body;
				}
			}
			else
			{
				$obj->action[] = $it->action->body;
			}
			$obj->description = $it->description->body;
			$obj->selected = false;
			if(in_array($act, $obj->action)) 
			{
				$obj->selected = true;
				if($parent_item) 
				{
					$parent_item->selected = true;
				}
			}

			if($it->item && ($it->attrs->modinst != 'true'||Context::get('module_srl'))) 
			{
				$obj->submenu = nucommon::getMenu($it->item, $depth+1, $obj);
				if($obj->selected && $parent_item) $parent_item->selected= true;

				if($obj->selected) Context::set('selected_menu', $obj);
			}
			$menus[$obj->id] = $obj;
			unset($obj);
		}
		return $menus;
	}

	/**
	 * @brief read the license info from the server, write a cache file.
	 */
	function checkLicense($prodid, $user_id, $serial_number, $force=FALSE)
	{
		$oModuleModel = &getModel('module');
		$hostinfo = array($_SERVER['SERVER_ADDR'], $_SERVER['SERVER_NAME'], $_SERVER['HTTP_HOST']);
		$agency_url = sprintf("http://www.xeshoppingmall.com/?module=drmagency&act=getDrmagencyLicense&prodid=%s&hostinfo=%s&user=%s&serial=%s&version=%s", $prodid, implode(',',$hostinfo), $user_id, $serial_number, '1.3');
		$cache_file = sprintf("%sfiles/cache/license_%s.cache.php", _XE_PATH_, $prodid);
		if(!file_exists($cache_file) || filemtime($cache_file)+ 60*60 < time() || $force == TRUE)
		{
			FileHandler::writeFile($cache_file,'');
			FileHandler::getRemoteFile($agency_url, $cache_file, null, 1, 'GET', 'text/html', array('REQUESTURL'=>getFullUrl('')));
		}

		return $cache_file;
	}

	/**
	 * @brief parse the license info file, print whether the user has the right to use.
	 */
	function getLicenseFromAgency($module, $user_id, $serial_number)
	{
		$cache_file = nucommon::checkLicense($user_id, $module, $serial_number);

		if(file_exists($cache_file)) 
		{
			$oXml = new XmlParser();
			$buff = $oXml->parse(FileHandler::readFile($cache_file));

			$userObj = $buff->drm->user;
			if($userObj)
			{
				$user = $userObj->body;
				if($user != $user_id)
				{
					$this->checkLicense($user_id, $module, $serial_number, TRUE);
					return TRUE;
				}
			}

			$serialObj = $buff->drm->serial;
			if($serialObj)
			{
				$serial = $serialObj->body;
				if($serial != $serial_number)
				{
					$this->checkLicense($user_id, $module, $serial_number, TRUE);
					return TRUE;
				}
			}

			$licenseObj = $buff->drm->license;
			if($licenseObj)
			{
				$license = $licenseObj->body;
				if($license == 'none')
				{
					// print nothing
				}
			}
		}
		return FALSE;
	}

	/**
	 * @brief get news 
	 */
	function getNewsFromAgency()
	{
		//Retrieve recent news and set them into context
		$newest_news_url = sprintf("http://store.nurigo.net/?module=newsagency&act=getNewsagencyArticle&inst=notice&top=6&loc=%s", _XE_LOCATION_);
		$cache_file = sprintf("%sfiles/cache/nstore_news.%s.cache.php", _XE_PATH_, _XE_LOCATION_);
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

				foreach($item as $key => $val) 
				{
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
/* End of file nucommon.class.php */
/* Location: ./modules/nproduct/nucommon.class.php */
