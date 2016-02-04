<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nstore_digitalController
 * @author NURIGO(contact@nurigo.net)
 * @brief  nstore_digitalController
 */
class nstore_digitalController extends nstore_digital
{

	function updateSalesCount($item_srl, $quantity) 
	{
		$oNproductController = &getController('nproduct');
		$oNproductController->updateSalesCount($item_srl, $quantity);
	}

	function addGroups($member_srl, $group_srl_list) 
	{
		$oMemberModel = &getModel('member');
		$oMemberController = &getController('member');

		$groups = $oMemberModel->getMemberGroups($member_srl);
		foreach ($group_srl_list as $group_srl) {
			if (!in_array($group_srl, array_keys($groups))) {
				$oMemberController->addMemberToGroup($member_srl, $group_srl);
			}
		}
	}


	function updateOrderStatus($order_srl, $order_status) 
	{
		$oNstore_digitalModel = &getModel('nstore_digital');
		$oModuleModel = &getModel('module');
		        
		$order_info = $oNstore_digitalModel->getOrderInfo($order_srl);
		if(!$order_info) return Object(-1, 'order info not found');

		// give mileage
		if ($order_status==nstore_digital::ORDER_STATE_COMPLETE)
		{
			//$order_info = $oNstore_digitalModel->getOrderInfo($order_srl);
			if ($order_info->member_srl && $order_info->mileage && $order_info->mileage_save=='N')
			{
				$oNmileageController = &getController('nmileage');
				$oNmileageController->plusMileage($order_info->member_srl, $order_info->mileage, $order_info->title, $order_srl);
				$args->mileage_save = 'Y';
			}
			if ($order_info->item_list)
			{
				foreach ($order_info->item_list as $key=>$item)
				{
					$item_srl = $item->item_srl;
					$quantity = $item->quantity;
					$this->updateSalesCount($item_srl, $quantity);
				}
			}
			if ($order_info->group_srl_list)
			{
				$group_srl_list = unserialize($order_info->group_srl_list);
				if (is_array($group_srl_list)) $this->addGroups($order_info->member_srl, $group_srl_list);
			}
		}

		// for order table
		$args->order_srl = $order_srl;
		$args->order_status = $order_status;
		$args->purdate = date("YmdHiS");

		$output = executeQuery('nstore_digital.updateOrderStatus', $args);
		if (!$output->toBool()) return $output;

		// for cart table
		$args->order_srl = $order_srl;
		$args->order_status = $order_status;
		$args->purdate = date("YmdHiS");
		$output = executeQuery('nstore_digital.updateCartOrderStatus', $args);
		if (!$output->toBool()) return $output;

/*
		$nstore_digital_contents_config = $oModuleModel->getModuleConfig('nstore_digital_contents');

		if($nstore_digital_contents_config->period)
		{
			$oNdc_admin_controller->insertPeriod($order_srl);
		}
*/

		$config = $oNstore_digitalModel->getModuleConfig();
		$oNcartController = &getController('ncart');
		$args->state = $order_status;
		$output = $oNcartController->updateOrderStatus($order_srl, $args);

		unset($order_info->item_list);
		$oAutomailController = &getController('automail');
		if($oAutomailController) $oAutomailController->sendMail('nstore_digital', $order_status, $order_info->email_address, $order_info);

		return new Object();
	}

	/*
		$args->member_srl = $member_srl;
		$args->amount = $amount;
		$args->action = $action; // 1: plus, 2: minus
		$args->title = $title;
		$args->balance = $balance;
	*/

	function triggerUpdateDownloadedCount($obj) 
	{
		$oModuleModel = &getModel('module');
		$oNstore_digitalModel = &getModel('nstore_digital');

		// check whether this module's file.
		if (!$obj->module_srl) return;
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($obj->module_srl);
		if ($module_info->module != 'nproduct') return;

		$oNproductController = &getController('nproduct');
		$output = $oNproductController->updateDownloadCount($obj->upload_target_srl);

		return new Object();
	}

	/**
	 * @brief 다운로드권한 체크
	 */
	function triggerCheckPermission($obj) 
	{
		$oModuleModel = &getModel('module');
		$oNstore_digitalModel = &getModel('nstore_digital');

		// check whether this module's file.
		if (!$obj->module_srl) return;
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($obj->module_srl);

		$config = $oNstore_digitalModel->getModuleConfig();
		$oNproductModel = &getModel('nproduct');
		$item_info = $oNproductModel->getItemInfo($obj->upload_target_srl);
		if ($item_info->proc_module != 'nstore_digital') return;

/*
		// admin 이면 OK
		$logged_info = Context::get('logged_info');
		if ($logged_info && $logged_info->is_admin == 'Y')
		{
			return;
		}
*/

		// procNstore_digitalFileDownload 에서 체크한대로...
		if ($_SESSION['nstore_digital_downloadable'] != TRUE)
		{
			return new Object(-1, 'msg_not_permitted_download');
		}
		// FALSE로 바꿔놔야 다른 파일들을 마구 받지 않는다.
		$_SESSION['nstore_digital_downloadable'] = FALSE;
	}

	function procNstore_digitalFileDownload()
	{
		$oFileModel = &getModel('file');
		$oFileController = &getController('file');
		$oModuleModel = &getModel('module');
		$oNstore_digitalModel = &getModel('nstore_digital');
		$oNdcModel = &getModel('nstore_digital_contents');

		$nstore_digital_contents_config = $oModuleModel->getModuleConfig('nstore_digital_contents');

		$cart_srl = Context::get('cart_srl');

		// initialize variables.
		$downloadable = FALSE;
		$logged_info = Context::get('logged_info');

		// check whether there is a purchased item.
		$config = $oNstore_digitalModel->getModuleConfig();
		$args->member_srl = $logged_info->member_srl;
		$args->cart_srl = $cart_srl;
		$output = executeQuery('nstore_digital.getPurchasedItem', $args);
		$purchased_item = $output->data;

		if (in_array($purchased_item->order_status, array('2','3'))) 
		{
			$downloadable = TRUE;
		}

		$file_srl = $purchased_item->file_srl;
		if(Context::get('file_srl')) $file_srl = Context::get('file_srl');
		$file = $oFileModel->getFile($file_srl, array('file_srl','upload_target_srl','sid'));
		Context::set('file_srl',$file->file_srl);
		Context::set('sid',$file->sid);

		if ($downloadable)
		{
			$_SESSION['nstore_digital_downloadable'] = TRUE;
			if ($purchased_item && $purchased_item->order_status == '2') 
			{
				$this->updateOrderStatus($purchased_item->order_srl, '3');
			}
		}
		else
		{
			$_SESSION['nstore_digital_downloadable'] = FALSE;
		}

		// 다운로드 기한 체크 (상품에 만기일이 있을 경우에만)

		$vars->cart_srl = $cart_srl;
		$output = executeQuery('nstore_digital.getCartItem', $vars);

		if(!$output->toBool()) return $output;

		if($output->data->period)
		{
			$this->checkPeriod($purchased_item);
		}

		/*
		// admin, always allow
		if ($logged_info->is_admin == 'Y')
		{
			$downloadable = TRUE;
		}
		 */

		// file_srl, sid 로 파일다운로드 실행, triggerCheckPermission 을 호출하여 다운로드권한을 체크한다.
		return $oFileController->procFileDownload();
	}

	function procNstore_digitalFreebieDownload()
	{
		$oFileModel = &getModel('file');
		$oFileController = &getController('file');
		$oModuleModel = &getModel('module');
		$oNstore_digitalModel = &getModel('nstore_digital');

		$item_srl = Context::get('item_srl');

		// initialize variables.
		$downloadable = FALSE;
		$logged_info = Context::get('logged_info');
		if (!$logged_info)
		{
			return new Object(-1, '로그인 후 다운로드 하세요.');
		}

		// check whether there is a purchased item.
		$item_info = $oNstore_digitalModel->getItemInfo($item_srl);
		if ($item_info->price == 0) 
		{
			$downloadable = TRUE;
		}

		$file = $oFileModel->getFile($item_info->file_srl, array('file_srl','upload_target_srl','sid'));
		Context::set('file_srl',$file->file_srl);
		Context::set('sid',$file->sid);

/*
		// admin, always allow
		if ($logged_info->is_admin == 'Y')
		{
			$downloadable = TRUE;
		}
*/

		if ($downloadable)
		{
			$_SESSION['nstore_digital_downloadable'] = TRUE;
		}
		else
		{
			$_SESSION['nstore_digital_downloadable'] = FALSE;
		}
	
		return $oFileController->procFileDownload();
	}

	function insertOrder($in_args, &$cart) 
	{
		$oNstore_digitalModel = &getModel('nstore_digital');

		// make group_srl_list
		$group_srl_list = array();
		foreach ($cart->item_list as $key=>$val)
		{
			$tmp_srl_list = unserialize($val->group_srl_list);
			if (!is_array($tmp_srl_list)) $tmp_srl_list = array();
			foreach ($tmp_srl_list as $srl)
			{
				if (!in_array($srl, $group_srl_list)) $group_srl_list[] = $srl;
			}
		}
		$in_args->group_srl_list = serialize($group_srl_list);

		$output = executeQuery('nstore_digital.insertOrder', $in_args);
		if (!$output->toBool()) return $output;

		// update cart items.
		$args->order_srl = $in_args->order_srl;
		$args->member_srl = $in_args->member_srl;
		$args->module_srl = $in_args->module_srl;
		foreach ($cart->item_list as $key=>$val) {
			$args->cart_srl = $val->cart_srl;
			$args->discount_amount = $val->discount_amount;
			$args->discount_info = $val->discount_info;
			$args->discounted_price = $val->discounted_price;
			$output = executeQuery('nstore_digital.updateCartItem', $args);
			if (!$output->toBool()) return $output;

		}

		return new Object();
	}

	function procNstore_digitalUpdateOrderDetail()
	{
		
		$cart_srls = Context::get('cart_srls');
		$site_urls = Context::get('site_url');

		foreach ($cart_srls as $key=>$cart_srl) {
			if (!$cart_srl) continue;
			$site_url = $site_urls[$key];

			$args->cart_srl = $cart_srl;
			$args->site_url = $site_url;
			if (!$args->cart_srl&&!$args->site_url) continue;

			// cart info
			$output = executeQuery('nstore_digital.updateSiteUrl', $args);
			if (!$output->toBool()) return $output;
		}

		$this->setMessage('success_saved');

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid',Context::get('mid'),'act', 'dispNstore_digitalDetail','order_srl',Context::get('order_srl'));
			$this->setRedirectUrl($returnUrl);
			return;
		}

	}

	function processCartReview(&$args)
	{
		$oNstore_digitalModel = &getModel('nstore_digital');
		$oModuleModel = &getModel('module');
		$oMemberModel = &getModel('member');
		if (!$args->order_srl) return;

		$logged_info = Context::get('logged_info');
		if (!$logged_info) return new Object(-1, 'msg_login_required');

		$cart = $args->cart;


		$item_count = count($cart->item_list);
		if (!$item_count) return new Object(-1, 'No items to order');

		// calculate total price
		$max_unit_price = -1;
		$title = '';
		foreach ($cart->item_list as $key=>$val) {
			$sum = $val->price * $val->quantity;
			if ($val->price > $max_unit_price) {
				$max_unit_price = $val->price;
				$title = $val->item_name;
			}
		}
		if ($item_count > 1) $title = $title . ' 외 ' . ($item_count-1);

		// delivery fee
		if ($args->delivfee_inadvance=='N') {
			$cart->total_price -= $cart->delivery_fee;
			$cart->delivery_fee = 0;
		}
/*
		// use mileage
		if ($args->use_mileage) {
			$cart->total_price = $cart->total_price - (int)$args->use_mileage;
		}
		// calculate mileage
		$args->mileage = 0;
		$config = $oNstore_digitalModel->getModuleConfig();
		if ($config->mileage_percent)
		{
			$args->mileage = round($cart->total_price * ((float)$config->mileage_percent/100), -1);
		}
*/

		// generate order id
		//$order_srl = getNextSequence();

		// insert into store_order
		//$args->order_srl = $order_srl;
		$args->order_srl = $args->order_srl;
		$args->title = $title;
		$args->item_count = $item_count;

		$args->member_srl = $logged_info->member_srl;
		$args->email_address = $logged_info->email_address;
		$args->user_id = $logged_info->user_id;
		$args->nick_name = $logged_info->nick_name;

		if($args->payment_method == 'MO' && $logged_info->is_admin == 'Y' && $args->manorder_pid)
		{
			$args2->user_id = $args->manorder_pid;
			$output = executeQuery('member.getMemberInfo', $args2);
			if($output->data)
			{
				$args->member_srl = $output->data->member_srl;
				$args->email_address = $output->data->email_address;
				$args->user_id = $output->data->user_id;
				$args->nick_name = $output->data->nick_name;
			}
		}

		$args->total_price = $cart->total_price;
		$args->price = nproductItem::price($cart->total_price);
		$args->order_title = $title;
		$args->sum_price = $cart->sum_price;
		$args->total_discounted_price = $cart->total_discounted_price;
		$args->total_discount_amount = $cart->total_discount_amount;
		$args->taxation_amount = $cart->taxation_amount;
		$args->supply_amount = $cart->supply_amount;
		$args->taxfree_amount = $cart->taxfree_amount;
		$args->vat = $cart->vat;
		if($args->delivdest_info) $args->extra_vars = serialize($args->delivdest_info);

		foreach ($cart->item_list as $key=>$val)
		{
			if($val->module != 'nstore_digital') continue;
			/**
			 * 상품정보 카트에 담기
			 */
			$cartitem_args->cart_srl = $val->cart_srl;
			$cartitem_args->item_srl = $val->item_srl;
			$cartitem_args->member_srl = $args->member_srl;
			$cartitem_args->module_srl = $val->module_srl;
			$cartitem_args->quantity = $val->quantity;
			$cartitem_args->price = $val->price;
			$cartitem_args->taxfree = $val->taxfree;
			$output = executeQuery('nstore_digital.deleteCartItem', $cartitem_args);
			if (!$output->toBool()) return $output;
			$output = executeQuery('nstore_digital.insertCartItem', $cartitem_args);
			if (!$output->toBool()) return $output;
			unset($cartitem_args);
		}
		
		$output = executeQuery('nstore_digital.deleteOrder', $args);
		if (!$output->toBool()) return $output;

		$output = $this->insertOrder($args, $cart);
		if (!$output->toBool()) return $output;
	}

	/**
	 * $obj->return_url 에 URL을 넘겨주면 pay::procEpayDoPayment에서 해당 URL로 Redirect시켜준다.
	 */
	function processCartPayment(&$obj) 
	{
		$oNstore_digitalModel = &getModel('nstore_digital');
		$oModuleModel = &getModel('module');
		$oNdcModel = &getModel('nstore_digital_contents');

		if (!$obj->order_srl) return;

		// get order info by order id
		$args->order_srl = $obj->order_srl;
		$output = executeQuery('nstore_digital.getOrderInfo', $args);

		if (!$output->toBool()) return $output;
		$order_info = $output->data;
		if (!$order_info) return;
		unset($args);

		// update order info for success
		switch ($obj->state) 
		{
			case '1': // not completed
				$order_status = '1';
				break;
			case '2': // completed
				$order_status = '2';
				break;
			case '3': // failure
				$order_status = '1';
				break;
		}

/*
		// complete order
		if ($obj->state == '2') {
			if ($order_info->use_mileage) {
				$this->minusMileage($order_info->member_srl, $order_info->use_mileage, $order_info->title, $order_info->order_srl);
			}
		}
*/
		if ($order_status)
		{
			$output = $this->updateOrderStatus($obj->order_srl, $order_status);
			if (!$output->toBool()) return $output;
		}

		// 입금완료되면 만기일 추가
		if($order_status == '2')
		{
			$vars->order_srl = $obj->order_srl;
			$output = executeQueryArray('nstore_digital.getCartItemsByOrderSrl', $vars);
			if(!$output->toBool()) return $output;

			$cart_list = $output->data;
			if(!$cart_list) $cart_list = array();

			foreach($cart_list as $k => $v)
			{
				if(!$v) continue;
				unset($vars);

				//아이템에 만기일이 있을경우에만 만기일을 넣어준다.
				$item_config = $oNdcModel->getItemConfig($v->item_srl);
				
				if($item_config->period && $item_config->period_type)
				{
					$period = $item_config->period;
					$period_type = $item_config->period_type;

					$d = 0;
					$m = 0;
					$y = 0;

					switch($period_type)
					{
						case 'd' : $d = $period; break;
						case 'm' : $m = $period; break;
						case 'y' : $y = $period; break;
					}

					$period = date("Ymd", mktime(0, 0, 0, date("m")+$m, date("d")+$d, date("Y")+$y));

					$vars->cart_srl = $v->cart_srl;
					$vars->period = $period;
					$output = executeQuery('nstore_digital.updateCartItemPeriod', $vars);
					if(!$output->toBool()) return $output;
				}
			}
		}

		$obj->return_url = getNotEncodedUrl('','act','dispNstore_digitalOrderComplete','order_srl',$obj->order_srl,'mid',$obj->xe_mid);
	}

	function triggerProcessReview(&$args)
	{
		if($args->target_module != 'nstore_digital') return;
		if(!$args->module_srl) return;
		if(!$args->cart_srl) return;

		$oMemberController = &getController('member');
		$oModuleModel = &getModel('module');
		$oNdcModel = &getModel('nstore_digital_contents');

		$module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);
		$logged_info = Context::get('logged_info');


		$item_config = $oNdcModel->getItemConfig($args->item_srl);

		$period = $item_config->period;
		$period_type = $item_config->period_type;

		$d = 0;
		$m = 0;
		$y = 0;

		switch($period_type)
		{
			case 'd' : $d = $period; break;
			case 'm' : $m = $period; break;
			case 'y' : $y = $period; break;
		}

		$vars->cart_srl = $args->cart_srl;
		$output = executeQuery('nstore_digital.getCartItem', $vars);
		if(!$output->toBool()) return $output;

		$dead_line = $output->data->period; // dead_line 은 해당 cart_srl에 설정된 만기일.
		$start_date = $output->data->regdate; // start_date는 해당 상품이 처음 구매된날
		$current_date = date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y")));

		// 설정된 만기일이 현재 날짜보다 작다면, 현재 날짜를 데드라인으로 한다.
		if($dead_line < $current_date) $dead_line = $current_date;

		$year = substr($dead_line, 0, 4);
		$month = substr($dead_line, 4, 2);
		$day = substr($dead_line, 6, 2);
		$end_date = date("Ymd", mktime(0, 0, 0, $month+$m, $day+$d, $year+$y));

		// 같은 cart_srl이 state 1로 되있으면 삭제한다.

		$q_args->order_status = '1';
		$q_args->cart_srl = $args->cart_srl;
		$q_args->member_srl = $logged_info->member_srl;

		$output = executeQueryArray('nstore_digital.getPeriod', $q_args);
		if(!$output->toBool())return $output;

		if($output->data)
		{
			foreach($output->data as $k=>$v)
			{
				$args->period_srl = $v->period_srl;
				$output = executeQuery('nstore_digital.deletePeriod', $args);
				if(!$output->toBool())return $output;
			}
		}

		$price = $args->price;
		$extra_vars = unserialize($item_config->extra_vars);
        if(isset($extra_vars['period_price'])) $price = $extra_vars['period_price'];

		// end

		// 기간연장관리 테이블에 insert 
		$args->order_status = '1';
		$args->price = $price;
		$args->order_title = $args->item_name;
		$args->member_srl = $logged_info->member_srl;
		$args->start_date = $start_date;
		$args->end_date = $end_date;
		$args->period_srl = getNextSequence();

		$_SESSION['period_srl'] = $args->period_srl;
		Context::set('cart_srl', $args->cart_srl);

		$output = executeQuery('nstore_digital.insertPeriod', $args);
		if(!$output->toBool())return $output;
	}

	
	function triggerProcessPayment($obj)
	{
		if($obj->target_module != 'nstore_digital') return;
		if(!$obj->module_srl) return;

		$oModuleModel = &getModel('module');
		$oMemberController = &getController('member');

		$logged_info = Context::get('logged_info');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($obj->module_srl);

		if ($obj->state=='2' && $_SESSION['period_srl'])
		{
			$args->period_srl = $_SESSION['period_srl'];
			$output = executeQuery('nstore_digital.getPeriod', $args);
			if(!$output->toBool())return $output;

			$cart_srl = $output->data->cart_srl;
			$end_date = $output->data->end_date;

			// 결제완료되면 review에서 등록한 기간연장관리 데이터를 업데이트 해준다.

			$args->order_status = '2';
			$output = executeQuery('nstore_digital.updatePeriod', $args);
			if(!$output->toBool())return $output;

			// nstore_digital_cart에서도 업데이트

			$vars->cart_srl = $cart_srl;
			$vars->period = $end_date;
			$output = executeQuery('nstore_digital.updateCartItemPeriod', $vars);
			if(!$output->toBool())return $output;
		}

		$obj->return_url = getNotEncodedUrl('','act','dispNstore_digitalOrderComplete','period_srl',$_SESSION['period_srl'],'order_srl',$obj->order_srl,'mid',$obj->xe_mid);
		$returnUrl = getNotEncodedUrl('','act','dispNstore_digitalOrderComplete','period_srl',$_SESSION['period_srl'],'order_srl',$obj->order_srl,'mid',$obj->xe_mid);
		$this->setRedirectUrl($returnUrl);
	}

	function checkPeriod($purchased_item)
	{
		$oNdcModel = &getModel('nstore_digital_contents');
		$oNdcController = &getController('nstore_digital_contents');
		$oNcartModel = &getModel('ncart');

		if(!$purchased_item->cart_srl) return new Object(-1, 'cart_srl is not defined');


		// 만기일 확인 

		$args->cart_srl = $purchased_item->cart_srl;
		$output = executeQuery('nstore_digital.getCartItem', $args);
		if(!$output->toBool())return $output;

		if($output->data->period)
		{
			$current_date = date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y")));

			// 만기일이 지났으면 다운로드 안되게 함.
			if($output->data->period < $current_date) $_SESSION['nstore_digital_downloadable'] = FALSE;
		}
	}
}
/* End of file nstore_digital.controller.php */
/* Location: ./nstore_digital/nstore_digital.controller.php */
