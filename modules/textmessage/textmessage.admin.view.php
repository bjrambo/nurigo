<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  textmessageAdminView
 * @author wiley (wiley@nurigo.net)
 * @brief  textmessage view class of textmessage module
 **/

class textmessageAdminView extends textmessage 
{
	/**
	 * @brief Initilization
	 * @return none
	 **/
	function init() 
	{
		// 템플릿 경로 지정 (board의 경우 tpl에 관리자용 템플릿 모아놓음)
		$template_path = sprintf("%stpl/",$this->module_path);
		$this->setTemplatePath($template_path);
	}

	/**
	 * @brief Display Super Admin Dashboard
	 * @return none
	 **/
	function dispTextmessageAdminIndex() 
	{
		$oTextmessageModel = &getModel('textmessage');
		$config = $oTextmessageModel->getConfig();
		if (!$config) Context::set('isSetupCompleted', false);
		else Context::set('isSetupCompleted', true);
		Context::set('config',$config);

		//Retrieve recent news and set them into context
		$newest_news_url = sprintf("http://www.coolsms.co.kr/?module=newsagency&act=getNewsagencyArticle&inst=notice&loc=%s", _XE_LOCATION_);
		$cache_file = sprintf("%sfiles/cache/cool_news.%s.cache.php", _XE_PATH_, _XE_LOCATION_);
		if(!file_exists($cache_file) || filemtime($cache_file)+ 60*60 < time()) 
		{
			// Considering if data cannot be retrieved due to network problem, modify filemtime to prevent trying to reload again when refreshing textmessageistration page
			// Ensure to access the textmessage registration page even though news cannot be displayed
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
				if(!is_array($item)) $item = array($item);

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

		$this->setTemplateFile('index');
	}

	/**
	 * 기본설정 페이지
	 */
	function dispTextmessageAdminConfig() 
	{
		$oTextmessageModel = &getModel('textmessage');
		$config = $oTextmessageModel->getConfig();

		$callback_url = Context::getDefaultUrl();
		$callback_url_style = "";
		if ($config->callback_url) $callback_url = $config->callback_url;
		else $callback_url_style = 'style="color:red;"';

		Context::set('callback_url', $callback_url);
		Context::set('callback_url_style', $callback_url_style);
		Context::set('config', $config);

		// 템플릿 파일 지정
		$this->setTemplateFile('config');
	}

	//발송내역 페이지 
	function dispTextmessageAdminUsageStatement() 
	{
		$oTextmessageModel = &getModel('textmessage');
		$config = $oTextmessageModel->getModuleConfig();
		$sms = $oTextmessageModel->getCoolSMS();

		$count = Context::get('page_no');
		$search_code = Context::get('search_code');
		$msg_type = Context::get('msg_type');
		$rcpt_no = Context::get('rcpt_no');
		if(!$count) $count = 20;
		if($msg_type != 'all') $options->type = $msg_type;
		if(is_numeric($search_code))
			$options->s_resultcode = $search_code;
		if($rcpt_no)
			$options->rcpt = $rcpt_no;
		
		$options->count = $count;
		$options->page = Context::get('page');
		$output = $sms->sent($options);
	
		$output->total_page = ceil($output->total_count / $count);
		$page = new PageHandler($output->total_count, $output->total_page, 1, $count);
		$output->page_navigation = $page;

		// 템플릿에 쓰기 위해서 context::set
		Context::set('page_no', $count);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('list_count', $output->list_count);
		Context::set('message_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);
		require_once('textmessage.utility.php');
		$csutil = new CSUtility();
		Context::set('csutil', $csutil);
		Context::set('config', $config);

		$this->setTemplateFile('usagestatement_list');
	}
}
/* End of file textmessage.admin.view.php */
/* Location: ./modules/textmessage.admin.view.php */
