<?php

/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nstoreAdminView
 * @author NURIGO(contact@nurigo.net)
 * @brief  nstoreAdminView
 */
class nstoreAdminView extends nstore
{
	/**
	 * @brief Contructor
	 **/

	function init()
	{
		// module_srl이 있으면 미리 체크하여 존재하는 모듈이면 module_info 세팅
		$module_srl = Context::get('module_srl');
		if(!$module_srl && $this->module_srl)
		{
			$module_srl = $this->module_srl;
			Context::set('module_srl', $module_srl);
		}

		$oModuleModel = getModel('module');

		// module_srl이 넘어오면 해당 모듈의 정보를 미리 구해 놓음
		if($module_srl)
		{
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
			if(!$module_info)
			{
				Context::set('module_srl', '');
				$this->act = 'list';
			}
			else
			{
				ModuleModel::syncModuleToSite($module_info);
				$this->module_info = $module_info;
				Context::set('module_info', $module_info);
			}
		}
		if($module_info && !in_array($module_info->module, array('nstore', 'nstore_digital', 'elearning')))
		{
			return $this->stop("msg_invalid_request");
		}

		// set template file
		$tpl_path = $this->module_path . 'tpl';
		$this->setTemplatePath($tpl_path);
		$this->setTemplateFile('index');
		Context::set('tpl_path', $tpl_path);

		// module이 cympusadmin일때 관리자 레이아웃으로
		if(Context::get('module') == 'cympusadmin')
		{
			$classfile = _XE_PATH_ . 'modules/cympusadmin/cympusadmin.class.php';
			if(file_exists($classfile))
			{
				require_once($classfile);
				cympusadmin::init($this);
			}
		}
	}

	function dispNstoreAdminDashboard()
	{
		$output = executeQueryArray('nstore.getOrderStat', $args);
		if(!$output->toBool())
		{
			return $output;
		}
		$list = $output->data;
		if(!is_array($list))
		{
			$list = array();
		}

		$stat_arr = array();
		$keys = array_keys($this->order_status);

		foreach($keys as $key)
		{
			$stat_arr[$key] = 0;
		}
		foreach($list as $key => $val)
		{
			$stat_arr[$val->order_status] = $val->count;
		}
		Context::set('order_status', $this->getOrderStatus());
		Context::set('orderstat', $stat_arr);

		// get module srls
		$module_srls = array();
		$output = executeQueryArray('nproduct.getModInstList', $args);
		if(!$output->toBool())
		{
			return $output;
		}
		$modinst_list = $output->data;
		if(!is_array($modinst_list))
		{
			$modinst_list = array();
		}
		foreach($modinst_list as $modinst)
		{
			$module_srls[] = $modinst->module_srl;
		}

		// newest comment
		$oCommentModel = getModel('comment');
		$columnList = array('comment_srl', 'module_srl', 'document_srl', 'content', 'nick_name', 'member_srl');
		$args->module_srl = $module_srls;
		$args->list_count = 20;
		$comment_list = $oCommentModel->getNewestCommentList($args, $columnList);
		if(!is_array($comment_list))
		{
			$comment_list = array();
		}

		foreach($comment_list AS $key => $value)
		{
			$value->content = strip_tags($value->content);
		}
		Context::set('comment_list', $comment_list);
		unset($args, $comment_list, $columnList);


		// newest review
		$review_list = array();
		require_once(_XE_PATH_ . 'modules/store_review/store_review.item.php');
		$args = new stdClass();
		$args->module_srl = $module_srls;
		$output = executeQueryArray('nstore.getNewestReviewList', $args);
		if(!is_array($output->data))
		{
			$output->data = array();
		}
		foreach($output->data as $key => $val)
		{
			if(!$val->review_srl)
			{
				continue;
			}
			$oReview = new store_reviewItem();
			$oReview->setAttribute($val);
			$review_list[$key] = $oReview;
		}
		Context::set('review_list', $review_list);

		$this->getNewsFromAgency();
	}

	function dispNstoreAdminOrderManagement()
	{
		$oNstoreModel = getModel('nstore');
		$oMemberModel = getModel('member');

		$classfile = _XE_PATH_ . 'modules/cympusadmin/cympusadmin.class.php';
		if(file_exists($classfile))
		{
			require_once($classfile);
			$output = cympusadmin::init($this);
			if(!$output->toBool())
			{
				return $output;
			}
		}

		$config = $oNstoreModel->getModuleConfig();
		Context::set('config', $config);

		if(!Context::get('status'))
		{
			Context::set('status', '1');
		}
		$args = new stdClass();
		$args->order_status = Context::get('status');
		$args->page = Context::get('page');
		if(Context::get('search_key'))
		{
			$search_key = Context::get('search_key');
			$search_value = Context::get('search_value');
			if($search_key == 'nick_name' && $search_value == '비회원')
			{
				$search_key = 'member_srl';
				$search_value = 0;
			}
			$args->{$search_key} = $search_value;
		}
		// 년도 미 지정 시 년도에 상광없이 월 검색이 가능하도록 regdate 검색어에 single character wildcard('_') 4자리 지정
		$args->regdate = '____';
		if(Context::get('s_year'))
		{
			$args->regdate = Context::get('s_year');
		}
		if(Context::get('s_month'))
		{
			$args->regdate = $args->regdate . Context::get('s_month');
		}
		$output = executeQueryArray('nstore.getOrderListByStatus', $args);
		if(!$output->toBool())
		{
			return $output;
		}
		$order_list = $output->data;
		if(!is_array($order_list))
		{
			$order_list = array();
		}

		$member_config = $oMemberModel->getMemberConfig();
		$memberIdentifiers = array('user_id' => 'user_id', 'user_name' => 'user_name', 'nick_name' => 'nick_name');
		$usedIdentifiers = array();

		if(is_array($member_config->signupForm))
		{
			foreach($member_config->signupForm as $signupItem)
			{
				if(!count($memberIdentifiers))
				{
					break;
				}
				if(in_array($signupItem->name, $memberIdentifiers) && ($signupItem->required || $signupItem->isUse))
				{
					unset($memberIdentifiers[$signupItem->name]);
					$usedIdentifiers[$signupItem->name] = $lang->{$signupItem->name};
				}
			}
		}
		Context::set('list', $order_list);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('page_navigation', $output->page_navigation);
		Context::set('delivery_companies', $this->delivery_companies);
		Context::set('order_status', $this->getOrderStatus());
		Context::set('delivery_inquiry_urls', $this->delivery_inquiry_urls);
		Context::set('usedIdentifiers', $usedIdentifiers);
		$this->setTemplateFile('ordermanagement');
	}

	function dispNstoreAdminOrderDetail()
	{
		$oNstoreModel = getModel('nstore');
		$oEpayModel = getModel('epay');

		$order_srl = Context::get('order_srl');
		$order_info = $oNstoreModel->getOrderInfo($order_srl);

		$payment_info = $oEpayModel->getTransactionByOrderSrl($order_srl);
		Context::set('payment_info', $payment_info);
		Context::set('order_info', $order_info);
		Context::set('order_status', $this->getOrderStatus());
		Context::set('delivery_companies', $this->delivery_companies);
		Context::set('payment_method', $this->getPaymentMethods());
		Context::set('delivery_inquiry_urls', $this->delivery_inquiry_urls);
		$this->setTemplateFile('orderdetail');
	}

	function dispNstoreAdminOrderSheet()
	{
		$oNstore_coreModel = getModel('nstore');

		$order_srl = Context::get('order_srl');
		$order_info = $oNstore_coreModel->getOrderInfo($order_srl);
		Context::set('order_info', $order_info);

		$this->setTemplateFile('ordersheet');
		$this->setLayoutPath('./common/tpl');
		$this->setLayoutFile('default_layout');
	}

	function dispNstoreAdminModInstList()
	{
		$args = new stdClass();
		$args->sort_index = "module_srl";
		$args->page = Context::get('page');
		$args->list_count = 20;
		$args->page_count = 10;
		$args->s_module_category_srl = Context::get('module_category_srl');
		$output = executeQueryArray('nstore.getModInstList', $args);
		$list = $output->data;
		Context::set('list', $list);

		$oModuleModel = getModel('module');
		$module_category = $oModuleModel->getModuleCategories();
		Context::set('module_category', $module_category);
		$this->setTemplateFile('modinstlist');
	}

	function dispNstoreAdminInsertModInst()
	{
		// 스킨 목록을 구해옴
		$oModuleModel = getModel('module');
		$skin_list = $oModuleModel->getSkins($this->module_path);
		Context::set('skin_list', $skin_list);

		$mskin_list = $oModuleModel->getSkins($this->module_path, "m.skins");
		Context::set('mskin_list', $mskin_list);

		// 레이아웃 목록을 구해옴
		$oLayoutModel = getModel('layout');
		$layout_list = $oLayoutModel->getLayoutList();
		Context::set('layout_list', $layout_list);

		$mobile_layout_list = $oLayoutModel->getLayoutList(0, "M");
		Context::set('mlayout_list', $mobile_layout_list);

		$module_category = $oModuleModel->getModuleCategories();
		Context::set('module_category', $module_category);
		$this->setTemplateFile('insertmodinst');
	}


	function dispNstoreAdminConfig()
	{
		$oNstore_coreModel = getModel('nstore');
		$oModuleModel = getModel('module');

		$config = $oNstore_coreModel->getModuleConfig();
		Context::set('config', $config);

		// list of skins for member module
		$skin_list = $oModuleModel->getSkins($this->module_path);
		Context::set('skin_list', $skin_list);

		$mskin_list = $oModuleModel->getSkins($this->module_path, "m.skins");
		Context::set('mskin_list', $mskin_list);

		// 레이아웃 목록을 구해옴
		$oLayoutModel = getModel('layout');
		$layout_list = $oLayoutModel->getLayoutList();
		Context::set('layout_list', $layout_list);

		$mobile_layout_list = $oLayoutModel->getLayoutList(0, "M");
		Context::set('mlayout_list', $mobile_layout_list);

		/*
		// epay plugin list
		$oEpayModel = getModel('epay');
		$modules = $oEpayModel->getEpayList();
		Context::set('epay_modules', $modules);
		 */

		$oNcartModel = getModel('ncart');
		if($oNcartModel)
		{
			$ncart_insts = $oNcartModel->getModInstList();
			Context::set('ncart_insts', $ncart_insts);
		}

		Context::set('delivery_companies', $this->delivery_companies);
		$this->setTemplateFile('config');
	}

	function dispNstoreAdminEscrowDelivery()
	{
		$oNstoreModel = getModel('nstore');
		$oEpayModel = getModel('epay');

		$order_srl = Context::get('order_srl');
		$order_info = $oNstoreModel->getOrderInfo($order_srl);
		$payment_info = $oEpayModel->getTransactionByOrderSrl($order_srl);
		$args = new stdClass();
		$args->order_srl = $order_srl;
		$output = executeQuery('nstore.getEscrowInfo', $args);
		$escrow_info = $output->data;

		preg_match("/\(.*\)/", implode(unserialize($order_info->recipient_address)), $postcode_arr);
		if(count($postcode_arr))
		{
			$order_info->recipient_postcode = preg_replace('/[\-\(\)]/', '', $postcode_arr[0]);
		}

		$plugin = $oEpayModel->getPlugin($payment_info->plugin_srl);
		$output = $plugin->dispEscrowDelivery($order_info, $payment_info, $escrow_info);
		Context::set('content', $output);

		$this->setLayoutPath(_XE_PATH_ . 'common/tpl');
		$this->setLayoutFile('default_layout');
		$this->setTemplateFile('extra');
	}

	function dispNstoreAdminEscrowDenyConfirm()
	{
		$oNstoreModel = getModel('nstore');
		$oEpayModel = getModel('epay');

		$order_srl = Context::get('order_srl');
		$order_info = $oNstoreModel->getOrderInfo($order_srl);
		$payment_info = $oEpayModel->getTransactionByOrderSrl($order_srl);
		$args = new stdClass();
		$args->order_srl = $order_srl;
		$output = executeQuery('nstore.getEscrowInfo', $args);
		$escrow_info = $output->data;

		preg_match("/\(.*\)/", implode(unserialize($order_info->recipient_address)), $postcode_arr);
		if(count($postcode_arr))
		{
			$order_info->recipient_postcode = preg_replace('/[\-\(\)]/', '', $postcode_arr[0]);
		}

		$plugin = $oEpayModel->getPlugin($payment_info->plugin_srl);
		$output = $plugin->dispEscrowDenyConfirm($order_info, $payment_info, $escrow_info);
		Context::set('content', $output);

		$this->setLayoutPath(_XE_PATH_ . 'common/tpl');
		$this->setLayoutFile('default_layout');
		$this->setTemplateFile('extra');
	}

	function dispNstoreAdminPurchaserInfo()
	{
		$oMemberModel = getModel('member');
		$oMemberView = getView('member');

		$member_config = $oMemberModel->getMemberConfig();
		$member_srl = Context::get('member_srl');

		$site_module_info = Context::get('site_module_info');
		$columnList = array('member_srl', 'user_id', 'email_address', 'user_name', 'nick_name', 'homepage', 'blog', 'birthday', 'regdate', 'last_login', 'extra_vars');
		$member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl, $site_module_info->site_srl, $columnList);
		unset($member_info->password);
		unset($member_info->email_id);
		unset($member_info->email_host);


		Context::set('memberInfo', get_object_vars($member_info));

		$extendForm = $oMemberModel->getCombineJoinForm($member_info);
		unset($extendForm->find_member_account);
		unset($extendForm->find_member_answer);
		Context::set('extend_form_list', $extendForm);

		$oMemberView->_getDisplayedMemberInfo($member_info, $extendForm, $member_config);

		/*
		$member_info = $oMemberModel->getMemberInfoByMemberSrl(Context::get('member_srl'));
		if(!$member_info) return new Object(-1, 'msg_invalid_request');
		Context::set('member_info', $member_info);
		 */

		$args = new stdClass();
		$args->member_srl = Context::get('member_srl');
		$args->order_status = '2';
		$output = executeQueryArray('nstore.getOrderList', $args);
		$order_list = $output->data;
		Context::set('order_list', $order_list);
		Context::set('order_status', $this->getOrderStatus());
		Context::set('delivery_inquiry_urls', $this->delivery_inquiry_urls);

		$this->setTemplateFile('purchaser_info');
	}

	/**
	 * @brief 스킨 정보 보여줌
	 **/
	function dispNstoreAdminSkinInfo()
	{
		// 공통 모듈 권한 설정 페이지 호출
		$oModuleAdminModel = getAdminModel('module');
		$skin_content = $oModuleAdminModel->getModuleSkinHTML($this->module_info->module_srl);
		Context::set('skin_content', $skin_content);
		$this->setTemplateFile('skininfo');
	}

	/**
	 * @brief 스킨 정보 보여줌
	 **/
	function dispNstoreAdminMobileSkinInfo()
	{
		// 공통 모듈 권한 설정 페이지 호출
		$oModuleAdminModel = getAdminModel('module');
		$skin_content = $oModuleAdminModel->getModuleMobileSkinHTML($this->module_info->module_srl);
		Context::set('skin_content', $skin_content);
		$this->setTemplateFile('skininfo');
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
				Context::set('news', $news);
			}
			Context::set('released_version', $buff->zbxe_news->attrs->released_version);
			Context::set('download_link', $buff->zbxe_news->attrs->download_link);
		}
	}

	/**
	 * @brief display the grant information
	 **/
	function dispNstoreAdminGrantInfo()
	{
		// get the grant infotmation from admin module
		$oModuleAdminModel = getAdminModel('module');
		$grant_content = $oModuleAdminModel->getModuleGrantHTML($this->module_info->module_srl, $this->xml_info->grant);
		Context::set('grant_content', $grant_content);

		$this->setTemplateFile('grantinfo');
	}
}

/* End of file nstore.admin.view.php */
/* Location: ./modules/nstore/nstore.admin.view.php */
