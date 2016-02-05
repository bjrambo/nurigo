<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  licenseModel
 * @author NURIGO(contact@nurigo.net)
 * @brief  licenseModel
 */
class licenseModel extends license
{
	function getModuleConfig()
	{
		$oModuleModel = &getModel('module');
		$config = $oModuleModel->getModuleConfig('license');
		return $config;
	}

	function getCopyright()
	{
		return "<script>jQuery(document).ready(function() { jQuery('<div style=\"background:#fff; padding:6px; position:fixed; right:6px; bottom:6px; \">Powered by <a href=\"http://www.xeshoppingmall.com\">NURIGO</a></div>').appendTo('body'); });</script>";
	}

	function getLicenseConfirm($products=array('nstore','nstore_digital'))
	{
		$has_license = TRUE;
		$expiration = NULL;
		if(!is_array($products)) $products = array($products);
		foreach($products as $prodid)
		{
			if($this->getLicenseFromAgency($prodid, $has_license, $expiration)) $this->getLicenseFromAgency($prodid, $has_license, $expiration);
			if($has_license) break;
		}
		return $has_license;
	}

	function checkLicense($prodid, $user_id, $serial_number, $force=FALSE)
	{
		$hostinfo = array($_SERVER['SERVER_ADDR'], $_SERVER['SERVER_NAME'], $_SERVER['HTTP_HOST']);
		$agency_url = sprintf("http://www.xeshoppingmall.com/?module=drmagency&act=getDrmagencyLicense&prodid=%s&hostinfo=%s&user=%s&serial=%s", $prodid, implode(',',$hostinfo), $user_id, $serial_number);
		$cache_file = sprintf("%sfiles/cache/license_%s.cache.php", _XE_PATH_, $prodid);
		if(!file_exists($cache_file) || filemtime($cache_file)+ 60*60 < time() || $force == TRUE)
		{
			FileHandler::writeFile($cache_file,'');
			FileHandler::getRemoteFile($agency_url, $cache_file, null, 1, 'GET', 'text/html', array('REQUESTURL'=>getFullUrl('')));
		}

		return $cache_file;
	}

	function getLicenseFromAgency($prodid, &$has_license = TRUE, &$expiration = NULL)
	{
		$has_license = TRUE;
		$oLicenseModel = &getModel('license');
		$config = $oLicenseModel->getModuleConfig();
		if($prodid == 'nstore')
		{
			$user_id = $config->user_id;
			$serial_number = $config->serial_number;
		}
		else if($prodid == 'nstore_digital')
		{
			$user_id = $config->d_user_id;
			$serial_number = $config->d_serial_number;
		}
		else
		{
			$user_id = $config->e_user_id;
			$serial_number = $config->e_serial_number;
		}

		$cache_file = $this->checkLicense($prodid, $user_id, $serial_number);

		if(file_exists($cache_file)) 
		{
			$oXml = new XmlParser();
			$buff = $oXml->parse(FileHandler::readFile($cache_file));

			// user
			$userObj = $buff->drm->user;
			if($userObj)
			{
				$user = $userObj->body;
				if($user != $user_id)
				{
					$this->checkLicense($prodid, $user_id, $serial_number, TRUE);
					return TRUE;
				}
			}

			// serial
			$serialObj = $buff->drm->serial;
			if($serialObj)
			{
				$serial = $serialObj->body;
				if($serial != $serial_number)
				{
					$this->checkLicense($prodid, $user_id, $serial_number, TRUE);
					return TRUE;
				}
			}

			// license
			$licenseObj = $buff->drm->license;
			if($licenseObj)
			{
				$license = $licenseObj->body;
				if($license == 'none')
				{
					$url = getUrl('act','dispLicenseAdminConfig');
					Context::set(sprintf('%s_MESSAGE_TYPE', strtoupper($prodid)), 'error');
					Context::set(sprintf('%s_MESSAGE', strtoupper($prodid)), Context::getLang('not_registered'));
					$has_license = FALSE;
				}
			}

			// expiration
			$expirationObj = $buff->drm->expiration;
			if($expirationObj)
			{
				$expiration = $expirationObj->body;
			}
		}
		return FALSE;
	}
}
/* End of file license.model.php */
/* Location: ./modules/license/license.model.php */
