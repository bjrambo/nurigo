<?php
function getCympusStatus()
{
	$logged_info = Context::get('logged_info');
	$cympusadmin_menu = Context::get('cympusadmin_menu');
	$args = new stdClass();
	$args->date = date("Ymd000000", time() - 60 * 60 * 24);
	$today = date("Ymd");
	$status = new stdClass();
	if($logged_info->is_admin == 'Y')
	{
		// Member Status
		$oMemberAdminModel = getAdminModel('member');
		$status->member = new stdClass();
		$status->member->todayCount = $oMemberAdminModel->getMemberCountByDate($today);
		$status->member->totalCount = $oMemberAdminModel->getMemberCountByDate();

		// Document Status
		$oDocumentAdminModel = getAdminModel('document');
		$statusList = array('PUBLIC', 'SECRET');
		$status->document = new stdClass();
		$status->document->todayCount = $oDocumentAdminModel->getDocumentCountByDate($today, array(), $statusList);
		$status->document->totalCount = $oDocumentAdminModel->getDocumentCountByDate('', array(), $statusList);

		// Comment Status
		$oCommentModel = getModel('comment');
		$status->comment = new stdClass();
		$status->comment->todayCount = $oCommentModel->getCommentCountByDate($today);
		$status->comment->totalCount = $oCommentModel->getCommentCountByDate();
	}

	// shoppping-mall
	$oNstoreAdminModel = getAdminModel('nstore');
	if($oNstoreAdminModel && ($logged_info->is_admin == 'Y' || $cympusadmin_menu['nstore']))
	{
		$salesInfoToday = $oNstoreAdminModel->getSalesInfo($today);
		$salesInfoTotal = $oNstoreAdminModel->getSalesInfo();
		$status->nstore = new stdClass();
		$status->nstore->todayCount = $salesInfoToday->count;
		$status->nstore->todayAmount = $salesInfoToday->amount;
		$status->nstore->totalCount = $salesInfoTotal->count;
		$status->nstore->totalAmount = $salesInfoTotal->amount;
		$status->nstore->orderStatus = $oNstoreAdminModel->getTotalStatus();
	}

	// contents-mall
	$oNstore_digitalAdminModel = getAdminModel('nstore_digital');
	if($oNstore_digitalAdminModel && ($logged_info->is_admin == 'Y' || $cympusadmin_menu['nstore_digital']))
	{
		$salesInfoToday = $oNstore_digitalAdminModel->getSalesInfo($today);
		$salesInfoTotal = $oNstore_digitalAdminModel->getSalesInfo();
		$status->nstore_digital = new stdClass();
		$status->nstore_digital->todayCount = $salesInfoToday->count;
		$status->nstore_digital->todayAmount = $salesInfoToday->amount;
		$status->nstore_digital->totalCount = $salesInfoTotal->count;
		$status->nstore_digital->totalAmount = $salesInfoTotal->amount;
		$status->nstore_digital->orderStatus = $oNstore_digitalAdminModel->getTotalStatus();
	}

	// elearning
	$oElearningAdminModel = getAdminModel('elearning');
	if($oElearningAdminModel && ($logged_info->is_admin == 'Y' || $cympusadmin_menu['elearning']))
	{
		$salesInfoToday = $oElearningAdminModel->getSalesInfo($today);
		$salesInfoTotal = $oElearningAdminModel->getSalesInfo();
		$status->elearning = new stdClass();
		$status->elearning->todayCount = $salesInfoToday->count;
		$status->elearning->todayAmount = $salesInfoToday->amount;
		$status->elearning->totalCount = $salesInfoTotal->count;
		$status->elearning->totalAmount = $salesInfoTotal->amount;
		$status->elearning->lessonStatus = $oElearningAdminModel->getTotalStatus();
	}

	// freepass
	$oFreepassAdminModel = getAdminModel('freepass');
	if($oFreepassAdminModel && ($logged_info->is_admin == 'Y' || $cympusadmin_menu['freepass']))
	{
		$salesInfoToday = $oFreepassAdminModel->getSalesInfo($today);
		$salesInfoTotal = $oFreepassAdminModel->getSalesInfo();
		$status->freepass = new stdClass();
		$status->freepass->todayCount = $salesInfoToday->count;
		$status->freepass->todayAmount = $salesInfoToday->amount;
		$status->freepass->totalCount = $salesInfoTotal->count;
		$status->freepass->totalAmount = $salesInfoTotal->amount;
		$status->freepass->lessonStatus = $oFreepassAdminModel->getTotalStatus();
	}

	// offline
	$oOfflineAdminModel = getAdminModel('offline');
	if($oOfflineAdminModel && ($logged_info->is_admin == 'Y' || $cympusadmin_menu['offline']))
	{
		$salesInfoToday = $oOfflineAdminModel->getSalesInfo($today);
		$salesInfoTotal = $oOfflineAdminModel->getSalesInfo();
		$status->offline = new stdClass();
		$status->offline->todayCount = $salesInfoToday->count;
		$status->offline->todayAmount = $salesInfoToday->amount;
		$status->offline->totalCount = $salesInfoTotal->count;
		$status->offline->totalAmount = $salesInfoTotal->amount;
		$status->offline->lessonStatus = $oOfflineAdminModel->getTotalStatus();
	}

	// for layer
	$oScmsAdminModel = getAdminModel('scms');
	if($oScmsAdminModel)
	{
		$status->player = new stdClass();
		$status->player->currentPlayCount = $oScmsAdminModel->getCurrentPlayCount();
	}

	return $status;
}

function getNewsFromAgency()
{
	//Retrieve recent news and set them into context
	$newest_news_url = sprintf("http://www.xeshoppingmall.com/?module=newsagency&act=getNewsagencyArticle&inst=notice&top=6&loc=%s", _XE_LOCATION_);

	$cache_file = sprintf("%sfiles/cache/nstore_news.%s.cache.php", _XE_PATH_, _XE_LOCATION_);
	if(!file_exists($cache_file) || filemtime($cache_file) + 60 * 60 < time())
	{
		// Considering if data cannot be retrieved due to network problem, modify filemtime to prevent trying to reload again when refreshing textmessageistration page
		// Ensure to access the textmessageistration page even though news cannot be displayed
		FileHandler::writeFile($cache_file, '');
		FileHandler::getRemoteFile($newest_news_url, $cache_file, null, 1, 'GET', 'text/html', array('REQUESTURL' => getFullUrl('')));
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
				$obj = new stdClass();
				$obj->title = $val->body;
				$obj->date = $val->attrs->date;
				$obj->url = $val->attrs->url;
				$news[] = $obj;
			}
			return $news;
		}
	}
}
