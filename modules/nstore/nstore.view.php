<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nstoreView
 * @author NURIGO(contact@nurigo.net)
 * @brief  nstoreView
 */
class nstoreView extends nstore
{
	function init()
	{
		if ($this->module_info->module == 'nstore')
		{
			if (!$this->module_info->skin) $this->module_info->skin = 'default';
			$skin = $this->module_info->skin;
		}
		else
		{
			$oModuleModel = &getModel('module');
			$this->nstore_config = $oModuleModel->getModuleConfig('nstore');
			$skin = $this->nstore_config->skin;
		}

		// 템플릿 경로 설정
		$this->setTemplatePath(sprintf('%sskins/%s', $this->module_path, $skin));

		$logged_info = Context::get('logged_info');

		if($logged_info) 
		{
			Context::set('login_chk','Y');
		}
		else if(!$logged_info)
		{
			Context::set('login_chk','N');
		}
	}

	// 주문내역 보기 (날짜별)
	function dispNstoreOrderList() 
	{
		$oFileModel = &getModel('file');
		$oNstoreModel = &getModel('nstore');

		$config = $oNstoreModel->getModuleConfig();
		Context::set('config',$config);

		$logged_info = Context::get('logged_info');

		// 비회원 구매가 활성화되어 있지 않고 로그인 되있지 않다면
		if(!$logged_info && $config->guest_buy=='N') return new Object(-1, 'msg_login_required');

		// 로그인되어 있지 않다면 비회원 주문상품 조회 페이지로
		if(!$logged_info) 	
		{
			$this->dispNstoreNonLoginOrder();
			return; 
		}

		$startdate = Context::get('startdate');
		$enddate = Context::get('enddate');
		if (!$startdate)
		{
			$startdate = date('Ymd', time() - (60*60*24*30));
		}
		if (!$enddate)
		{
			$enddate = date('Ymd');
		}

		Context::set('startdate', $startdate);
		Context::set('enddate', $enddate);

		$args->member_srl = $logged_info->member_srl;
		$args->startdate = $startdate . '000000';
		$args->enddate = $enddate . '235959';
		$output = executeQueryArray('nstore.getOrderItems', $args);
		$item_list = $output->data;
		$order_list = array();
		if ($item_list) {
			foreach ($item_list as $key=>$val) {
				$item = new nproductItem($val, $config->currency, $config->as_sign, $config->decimals);
				if ($item->option_srl)
				{
					$item->price += ($item->option_price);
				}
				$item_list[$key] = $item;

				if (!isset($order_list[$val->order_srl])) $order_list[$val->order_srl] = array();

				$order_list[$val->order_srl][] = $item;

			}
		}

		Context::set('list', $item_list);
		Context::set('order_list', $order_list);
		Context::set('order_status', $this->getOrderStatus());
		Context::set('delivery_inquiry_urls', $this->delivery_inquiry_urls);

		$this->setTemplateFile('orderlist');
	}

	function dispNstoreOrderDetail() 
	{
		$oFileModel = &getModel('file');
		$oEpayModel = &getModel('epay');
		$oNstoreModel = &getModel('nstore');

		$logged_info = Context::get('logged_info');
		$order_srl = Context::get('order_srl');

		// 주문번호가 없다면
		if(!$order_srl) return new Object(-1, 'msg_invalid_order_number');

		$order_info = $oNstoreModel->getOrderInfo($order_srl);

		// 주문정보가 없다면
		if(!$order_info) return new Object(-1, 'msg_invalid_order_number');

		// 권한 체크
		if($logged_info) 
		{
			// 로그인되어 있다면 member_srl 과 order_srl을 비교
			if($order_info->member_srl != $logged_info->member_srl) return new Object(-1, 'msg_not_permitted');
		}
		else  // 로그인 되어있지 않다면
		{
			$config = $oNstoreModel->getModuleConfig();

			// 설정에서 비회원 구매를 N으로 해놨다면 return
			if($config->guest_buy != 'Y') return new Object(-1, 'msg_not_permitted');

			// 설정에서 비회원 구매를 Y로 해놨다면 PermissionCheck
			$oNstoreController = &getController('nstore');
			$non_password = Context::get("non_password");
			$compare_password = $order_info->non_password;
			$output = $oNstoreController->checkOrderPermission($compare_password, $non_password);
			if(!$output->toBool()) return $output;
			unset($vars);
		}

		Context::set('order_info', $order_info);
		Context::set('order_status', $this->getOrderStatus());

		$payment_info = $oEpayModel->getTransactionByOrderSrl($order_srl);
		Context::set('payment_info',$payment_info);
		Context::set('payment_method',$this->getPaymentMethods());

		Context::set('delivery_inquiry_urls', $this->delivery_inquiry_urls);
		Context::set('delivery_companies', $oNstoreModel->getDeliveryCompanies());
		Context::set('soldout_process', $this->soldout_process);

		$this->setTemplateFile('orderdetail');
	}

	function dispNstoreReplyComment() 
	{
		// 권한 체크
		if(!$this->grant->write_comment) return new Object(-1,'msg_not_permitted');

		// 목록 구현에 필요한 변수들을 가져온다
		$parent_srl = Context::get('comment_srl');

		// 지정된 원 댓글이 없다면 오류
		if(!$parent_srl) return new Object(-1, 'msg_invalid_request');

		// 해당 댓글를 찾아본다
		$oCommentModel = &getModel('comment');
		$oSourceComment = $oCommentModel->getComment($parent_srl, $this->grant->manager);

		// 댓글이 없다면 오류
		if(!$oSourceComment->isExists()) return new Object(-1, 'msg_invalid_request');
		if(Context::get('document_srl') && $oSourceComment->get('document_srl') != Context::get('document_srl')) return new Object(-1, 'msg_invalid_request');

		// 대상 댓글을 생성
		$oComment = $oCommentModel->getComment();
		$oComment->add('parent_srl', $parent_srl);
		$oComment->add('document_srl', $oSourceComment->get('document_srl'));

		// 필요한 정보들 세팅
		Context::set('oSourceComment',$oSourceComment);
		Context::set('oComment',$oComment);
		Context::set('module_srl',$this->module_info->module_srl);

		/** 
		 * 사용되는 javascript 필터 추가
		 **/
		//Context::addJsFilter($this->module_path.'tpl/filter', 'insert_comment.xml');

		$this->setTemplateFile('commentform');
	}

	function dispNstoreLogin() 
	{
		$oNstoreModel = &getModel('nstore');
		// get module config
		$config = $oNstoreModel->getModuleConfig();
		Context::set('config',$config);

		$this->setTemplateFile('login_form');
	}

	function dispNstoreNonLoginOrder()
	{
		$this->setTemplateFile('orderlistlogin');
	}

	function dispNstoreEscrowConfirm()
	{
		$oNstoreModel = &getModel('nstore');
		$oEpayModel = &getModel('epay');

		$order_srl = Context::get('order_srl');
		$order_info = $oNstoreModel->getOrderInfo($order_srl);
		$payment_info = $oEpayModel->getTransactionByOrderSrl($order_srl);
		$args->order_srl = $order_srl;
		$output = executeQuery('nstore.getEscrowInfo', $args);
		$escrow_info = $output->data;

		$deny_order = Context::get('deny_order');
		if(!$deny_order)
		{
			$this->setLayoutFile('default_layout');
			$this->setTemplateFile('escrow_confirm');
		}
		else
		{
			$args->order_srl = $order_srl;
			$args->deny_order = $deny_order;
			$output = executeQuery('nstore.updateEscrow', $args);
			if(!$output->toBool()) return $output;
			$plugin = $oEpayModel->getPlugin($payment_info->plugin_srl);
			$output = $plugin->dispEscrowConfirm($order_info, $payment_info, $escrow_info);
			Context::set('content', $output);
			$this->setLayoutFile('default_layout');
			$this->setTemplateFile('extra');
		}
	}
}

/* End of file nstore.view.php */
/* Location: ./modules/nstore/nstore.view.php */
