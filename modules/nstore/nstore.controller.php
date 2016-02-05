<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nstoreController
 * @author NURIGO(contact@nurigo.net)
 * @brief  nstoreController
 */
class nstoreController extends nstore
{
	// 비회원 주문정보 보기
	function procNstoreGuestLogin()
	{
		$args = Context::getRequestVars();
		if(!$args->order_srl)
		{
			return new Object(-1, 'msg_input_order_number_password');
		}
		else if(!$args->non_password)
		{
			return new Object(-1, 'msg_input_order_number_password');
		}

		$args->non_password = base64_encode($args->non_password);

		$obj->return_url = getNotEncodedUrl('','act','dispNstoreOrderDetail','order_srl',$args->order_srl,'mid',Context::get('mid'),'non_password',$args->non_password,'module',Context::get('module'));
		$this->setRedirectUrl($obj->return_url);
	}

	function updateSalesCount($item_srl, $quantity) 
	{
		$oNproductController = &getController('nproduct');
		$oNproductController->updateSalesCount($item_srl, $quantity);
	}

	function updateOrderStatus($order_srl, $in_args) 
	{
		$oNstoreModel = &getModel('nstore');

		// if the order is completed, give mileage to the member.
		if ($in_args->order_status==nstore::ORDER_STATE_COMPLETE)
		{
			$order_info = $oNstoreModel->getOrderInfo($order_srl);
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
		}

		$order_info = $oNstoreModel->getOrderInfo($order_srl);

		// for order table
		$args->order_srl = $order_srl;
		$args->order_status = $in_args->order_status;
		$args->express_id = $in_args->express_id;
		$args->invoice_no = $in_args->invoice_no;
		$args->purdate = "YmdHiS";
		$args->payment_method = $in_args->payment_method;
		$output = executeQuery('nstore.updateOrderStatus', $args);
		if (!$output->toBool()) return $output;

		// for cart table
		$args->order_srl = $order_srl;
		$args->order_status = $in_args->order_status;
		$args->express_id = $in_args->express_id;
		$args->invoice_no = $in_args->invoice_no;
		$args->purdate = "YmdHiS";
		$output = executeQuery('nstore.updateCartOrderStatus', $args);
		if (!$output->toBool()) return $output;

		return new Object();
	}

	function triggerEscrowDelivery($in_args)
	{
		$args->order_srl = $in_args->get('order_srl');
		$args->pg_tid = $in_args->get('pg_tid');
		$args->pg_oid = $in_args->get('pg_oid');
		$args->invoice_no = $in_args->get('invoice_no');
		$args->registrant = $in_args->get('registrant');
		$args->deliverer_code = $in_args->get('deliverer_code');
		$args->deliverer_name = $in_args->get('deliverer_name');
		$args->delivery_type = $in_args->get('delivery_type');
		$args->delivery_date = $in_args->get('delivery_date');
		$args->sender_name = $in_args->get('sender_name');
		$args->sender_postcode = $in_args->get('sender_postcode');
		$args->sender_address1 = $in_args->get('sender_address1');
		$args->sender_address2 = $in_args->get('sender_address2');
		$args->sender_telnum = $in_args->get('sender_telnum');
		$args->recipient_name = $in_args->get('recipient_name');
		$args->recipient_postcode = $in_args->get('recipient_postcode');
		$args->recipient_address = $in_args->get('recipient_address');
		$args->recipient_telnum = $in_args->get('recipient_telnum');
		$args->product_code = $in_args->get('product_code');
		$args->product_name = $in_args->get('product_name');
		$args->quantity = $in_args->get('quantity');
		$args->result_code = $in_args->get('result_code');
		$args->result_message = $in_args->get('result_message');

		$output = executeQuery('nstore.deleteEscrowDelivery', $args);
		if(!$output->toBool()) return $output;

		$output = executeQuery('nstore.insertEscrowDelivery', $args);
		if(!$output->toBool()) return $output;
	}

	function triggerEscrowConfirm($in_args)
	{
		$args->order_srl = $in_args->get('order_srl');
		$args->confirm_code = $in_args->get('confirm_code');
		$args->confirm_message = $in_args->get('confirm_message');
		$args->confirm_date = $in_args->get('confirm_date');
		$output = executeQuery('nstore.updateEscrow', $args);
		if(!$output->toBool()) return $output;
	}

	function triggerEscrowDenyConfirm($in_args)
	{
		$args->order_srl = $in_args->get('order_srl');
		$args->denyconfirm_code = $in_args->get('denyconfirm_code');
		$args->denyconfirm_message = $in_args->get('denyconfirm_message');
		$args->denyconfirm_date = $in_args->get('denyconfirm_date');
		$output = executeQuery('nstore.updateEscrow', $args);
		if(!$output->toBool()) return $output;
	}


	function insertOrder($in_args, &$cart) 
	{
		$oNstoreModel = &getModel('nstore');

		$args = $in_args;
		if (is_array($args->purchaser_cellphone)) $args->purchaser_cellphone = implode('-',$in_args->purchaser_cellphone);
		if (is_array($args->purchaser_telnum)) $args->purchaser_telnum = implode('-',$in_args->purchaser_telnum);
		$args->purchaser_address = serialize($in_args->purchaser_address);
		$args->recipient_name = $in_args->recipient_name;
		$args->recipient_cellphone = $in_args->recipient_cellphone;
		$args->recipient_telnum = $in_args->recipient_telnum;
		$args->recipient_address = serialize($in_args->recipient_address);
		$args->non_password = $in_args->non_password;
		$output = executeQuery('nstore.insertOrder', $args);
		if (!$output->toBool()) return $output;
		unset($args);

		// update cart items.
		$args->order_srl = $in_args->order_srl;
		$args->member_srl = $in_args->member_srl;
		$args->module_srl = $in_args->module_srl;
		foreach ($cart->item_list as $key=>$val) {
			if($val->module != 'nstore') continue;
			$args->cart_srl = $val->cart_srl;
			$args->discount_amount = $val->discount_amount;
			$args->discount_info = $val->discount_info;
			$args->discounted_price = $val->discounted_price;
			$output = executeQuery('nstore.updateCartItem', $args);
			if (!$output->toBool()) return $output;

		}

		return new Object();
	}

	/**
	 * @brief this function will be called by ncart module when users complete to pay for buying products.
	 */
	function processCartReview(&$args)
	{
		// get objects to be used below.
		$oNstoreModel = &getModel('nstore');
		$oModuleModel = &getModel('module');
		$oMemberModel = &getModel('member');
		$oNproductModel = &getModel('nproduct');
		$oNcartModel = &getModel('ncart');

		// get the member information.
		$logged_info = Context::get('logged_info');

		//비회원일경우
		if(!$logged_info)
		{
			$non_password1 = Context::get('non_password1');
			$non_password2 = Context::get('non_password2');

			$non_password1 = trim($non_password1);
			$non_password2 = trim($non_password2);

			if(!$non_password1 || !$non_password2) return new Object(-1, '비밀번호를 입력해주세요.');

			if($non_password1 == $non_password2)
			{	
				$non_password = $non_password1;
				
				$non_password = crypt($non_password);

				$args->non_password = $non_password;
			}
			else return new Object(-1, '비밀번호가 다릅니다.');
		}

		// nstore상품의 cart_srl만 추출하여 다시 카트정보를 가져온다.
		$origin_cart = $args->cart;
		$cartnos = array();
		foreach ($origin_cart->item_list as $key=>$val)
		{
			if($val->module != 'nstore') continue;
			$cartnos[] = $val->cart_srl;
		}
		$cart = $oNcartModel->getCartInfo($cartnos);

		// from ncart db-table
		$item_list = $cart->item_list;
		
		// check number of items
		$item_count = count($cart->item_list);
		if (!$item_count) return new Object(-1, 'No items to order');

		// get title
		$title = $oNstoreModel->getOrderTitle($cart->item_list);

		// cart 테이블에 insert
		foreach ($cart->item_list as $key=>$val)
		{
			if($val->module != 'nstore') continue;

			/**
			 * 현재 상품정보와 장보구니에 담긴 정보를 비교하여 수정된 사항이 있으면 결제가 진행되지 않도록 한다.
			 */
			// 상품정보 읽어오기
			$item_info = $oNproductModel->getItemInfo($val->item_srl);
			// 체크1) 해당 상품이 삭제되었는지 확인
			if(!$item_info) return new Object(-1, sprintf(Context::getLang('msg_item_not_found'), $item_info->item_name));
			// 체크2) 진열상태 체크
			if($item_info->display == 'N') return new Object(-1, sprintf(Context::getLang('msg_not_displayed_item'), $item_info->item_name));
			$group_list = NULL;
			if($args->member_srl) $group_list = $oMemberModel->getMemberGroups($args->member_srl);
			$output = $oNproductModel->discountItem($item_info, $group_list);
			// 체크3) 가격 변동 체크
			if($val->discounted_price != $output->discounted_price) return new Object(-1, sprintf(Context::getLang('msg_price_changed'), $item_info->item_name));

			/**
			 * 상품정보 카트에 담기
			 */
			$cartitem_args->cart_srl = $val->cart_srl;
			$cartitem_args->item_srl = $val->item_srl;
			$cartitem_args->member_srl = $val->member_srl;
			$cartitem_args->module_srl = $val->module_srl;
			$cartitem_args->quantity = $val->quantity;
			$cartitem_args->price = $val->price;
			$cartitem_args->taxfree = $val->taxfree;
			$cartitem_args->option_srl = $val->option_srl;
			$cartitem_args->option_price = $val->option_price;
			$cartitem_args->option_title = $val->option_title;
			$output = executeQuery('nstore.deleteCartItem', $cartitem_args);
			if (!$output->toBool()) return $output;
			$output = executeQuery('nstore.insertCartItem', $cartitem_args);
			if (!$output->toBool()) return $output;
			unset($cartitem_args);
		}

		// insert into store_order
		//$args->order_srl = $order_srl;
		$args->order_srl = $args->order_srl;
		$args->title = $title;
		$args->item_count = $item_count;
		if($logged_info)
		{
			$args->member_srl = $logged_info->member_srl;
			$args->purchaser_email = $logged_info->email_address;
			$args->purchaser_name = $logged_info->nick_name;
			if (isset($logged_info->{$config->purchaser_cellphone})) $args->purchaser_cellphone = $logged_info->{$config->purchaser_cellphone};
			if (isset($logged_info->{$config->purchaser_telnum})) $args->purchaser_telnum = $logged_info->{$config->purchaser_telnum};
		}
		else
		{
			$args->purchaser_name = Context::getLang('guest');
			$args->purchaser_cellphone = Context::get('cellphone');
			$args->purchaser_telnum = Context::get('telnum');
			$args->purchaser_email = Context::get('email_address');
			$args->member_srl = 0;
		}
		$args->purchaser_address = array(Context::get('paddress1'), Context::get('paddress2'));
		$args->purchaser_postcode = Context::get('ppostcode');
		$args->recipient_name = Context::get('recipient_name');
		$args->recipient_cellphone = Context::get('recipient_cellphone');
		$args->recipient_telnum = Context::get('recipient_telnum');
		$args->recipient_postcode = Context::get('postcode');
		$args->recipient_address = array(Context::get('address1'), Context::get('address2'));
		$args->total_price = $cart->total_price;
		$args->sum_price = $cart->sum_price;
		$args->delivery_fee = $cart->delivery_fee;
		$args->total_discounted_price = $cart->total_discounted_price;
		$args->total_discount_amount = $cart->total_discount_amount;
		$args->taxation_amount = $cart->taxation_amount;
		$args->supply_amount = $cart->supply_amount;
		$args->taxfree_amount = $cart->taxfree_amount;
		$args->vat = $cart->vat;
		$args->extra_vars = NULL;
		if($args->delivdest_info) $args->extra_vars = serialize($args->delivdest_info);
		// delivery fee
		if ($args->delivfee_inadvance=='N') {
			$cart->total_price -= $cart->delivery_fee;
			$cart->delivery_fee = 0;
		}
		if($cart->delivery_fee) $total_price += $cart->delivery_fee;

		// delete exist order info.
		$output = executeQuery('nstore.deleteOrder', $args);
		if (!$output->toBool()) return $output;

		// insert order info.
		$output = $this->insertOrder($args, $cart);
		if (!$output->toBool()) return $output;
		unset($args);
	}

	/**
	 * $obj->return_url 에 URL을 넘겨주면 pay::procEpayDoPayment에서 해당 URL로 Redirect시켜준다.
	 */
	function processCartPayment(&$obj) {
		if ($obj->target_module != 'ncart') return;

		$oNstoreModel = &getModel('nstore');
		$oModuleModel = &getModel('module');

		$logged_info = Context::get('logged_info');

		// get order info by order id
		$args->order_srl = $obj->order_srl;
		$order_srl = $args->order_srl;
		$output = executeQuery('nstore.getOrderInfo', $args);
		if (!$output->toBool()) return $output;
		$order_info = $output->data;
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


		if ($order_status)
		{
			$args->order_status = $order_status;
			$args->payment_method = $obj->payment_method;
			$output = $this->updateOrderStatus($obj->order_srl, $args);
			if (!$output->toBool()) return $output;

			// 결제가 성공일 때 재고 차감, $order_status는 2가 되므로 차감 되야함.
			if ($obj->state == '2') $output = $this->updateStock($obj->order_srl, $order_status);
		}

		$orders_info = $oNstoreModel->getOrderInfo($order_srl);
		$item_srls = array();
		$count_list = count($orders_info->item_list);

		for($i = 0; $count_list != $i; $i++)
		{
			foreach($orders_info->item_list[$i] as $k => $v)
			{
				if($k == 'item_srl') $item_srls[] = $v;
			}
		}
	}

	/**
	 * @brief 재고 차감
	 */
	function updateStock($order_srl, $order_status)
	{
		$oNstoreModel = &getModel('nstore');
		$oNproductModel = &getModel('nproduct');
		$oNproductController = &getController('nproduct');

		// 1(입금대기) ~ 6(거래완료) 상태일 때만 처리, 그외 카트대기, 상품취소, 반품 이런 것들은 처리하면 안됨.
		if($order_status > 7) return new Object();
		if($order_status < 1) return new Object();

		// 주문정보 읽어오기
		$order_info = $oNstoreModel->getOrderInfo($order_srl);

		$nostocklist = array();

		foreach ($order_info->item_list as $k=>$val)
		{
			if($val->order_status != '1') continue;

			$base_stock = $oNproductModel->getItemExtraVarValue($val->item_srl, 'stock');
			if($base_stock == null) continue;

			$stock = $base_stock - $val->quantity;
			$output = $oNproductController->updateExtraVars($val->item_srl, 'stock', $stock);

			if(!$output->toBool()) return $output; 

			if($base_stock < $val->quantity) $nostocklist[] = $val->item_name . '(' . $stock . ')';
		}

		if(count($nostocklist) > 0)
		{	
			$message = implode($nostocklist, ',');
			return new Object(2, $message);
		}

		return new Object();
	}

	// 주문정보 페이지 권한 체크 
	function checkOrderPermission($compare_password, $non_password)
	{
		// 주문정보에 비밀번호가 없다면
		if(!$compare_password) return new Object(-1, 'msg_not_permitted');

		// 사용자로부터 넘겨받은 비밀번호가 없다면
		if(!$non_password) return new Object(-1, 'msg_input_password');

		$non_password = base64_decode($non_password);

		// 주문정보에 저장된 암호와 비교를 위해서 사용자로 부터 받은 암호를 crypt
		$password_salt = substr($compare_password, 0, 12); 
		$non_password = crypt($non_password, $password_salt); 

		// crypt한 비밀번호와 order_info에 저장된 비밀번호가 같지 않다면
		if($non_password != $compare_password) return new Object(-1,'msg_invalid_password');
	}

}

/* End of file nstore.controller.php */
/* Location: ./modules/nstore/nstore.controller.php */
