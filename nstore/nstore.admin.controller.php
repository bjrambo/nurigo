<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nstoreAdminController
 * @author NURIGO(contact@nurigo.net)
 * @brief  nstoreAdminController
 */
class nstoreAdminController extends nstore
{
	/**
	 * @brief 모듈 환경설정값 쓰기
	 **/
	function procNstoreAdminConfig() 
	{
		$args = Context::getRequestVars();
		
		// save module configuration.
		$oModuleControll = getController('module');
		$output = $oModuleControll->insertModuleConfig('nstore', $args);

		$this->setMessage('success_updated');

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) 
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispNstoreAdminConfig','module_srl',Context::get('module_srl'));
			$this->setRedirectUrl($returnUrl);
		}
	}

	/**
	 * @brief 모듈 환경설정값 쓰기
	 **/
	function procNstoreAdminInsertModInst() 
	{
		// module 모듈의 model/controller 객체 생성
		$oModuleController = &getController('module');
		$oModuleModel = &getModel('module');

		// 게시판 모듈의 정보 설정
		$args = Context::getRequestVars();
		$args->module = 'nstore';

		// module_srl이 넘어오면 원 모듈이 있는지 확인
		if($args->module_srl) 
		{
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);
			if($module_info->module_srl != $args->module_srl)
			{
				unset($args->module_srl);
			}
		}

		// module_srl의 값에 따라 insert/update
		if(!$args->module_srl) 
		{
			$output = $oModuleController->insertModule($args);
			$msg_code = 'success_registed';
		}
		else
		{
			$output = $oModuleController->updateModule($args);
			$msg_code = 'success_updated';
		}

		if(!$output->toBool())
		{
			return $output;
		}

		$this->add('module_srl',$output->get('module_srl'));
		$this->setMessage($msg_code);

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) 
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispNstoreAdminInsertModInst','module_srl',$output->get('module_srl'));
			$this->setRedirectUrl($returnUrl);
			return;
		}
	}

	function procNstoreAdminDeleteModInst()
	{
		$module_srl = Context::get('module_srl');
		$oModuleController = &getController('module');
		$output = $oModuleController->deleteModule($module_srl);
		if(!$output->toBool()) return $output;
		$this->add('module', 'nstore');
		$this->add('page', Context::get('page'));
		$this->setMessage('success_deleted');
		$returnUrl = getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispNstoreAdminModInstList');
		$this->setRedirectUrl($returnUrl);
	}

	function procNstoreAdminUpdateStatus() 
	{
		$oNstoreController = &getController('nstore');

		$carts = Context::get('cart');
		if(!is_array($carts)) $carts = array();
		$order_srls = Context::get('order_srls');
		$order_status = Context::get('order_status');

		if(!$carts)  // check box 선택한 주문이 없을때 뒤로가기
		{
			return new Object(-1, '선택한 주문이 없습니다.');
		}

		$message = array();
		foreach ($order_srls as $key=>$order_srl)
		{
			$args->order_srl = $order_srl;
			$args->order_status = $order_status;

			// 체크되지 않은 주문일 경우 상태를 변경하지 않는다.
			if(!in_array($order_srl, $carts))
			{
				unset($args->order_status);
			}

			// 상태값변경, 배송회사, 운송장번호 데이터가 없으면 업데이트 필요치 않는다.
			if(!$args->order_status)
			{
				continue;
			}

			$output = $oNstoreController->updateStock($order_srl, $order_status);
			if($output->getError() == 2)  $message[] = $output->getMessage();

			// express_id값은 항상 넘어 오므로 이 루틴을 타게된다.
			$output = $oNstoreController->updateOrderStatus($order_srl, $args);
			if(!$output->toBool()) return $output;
		}

		$this->setMessage('success_saved');

		if(count($message) > 0)
		{
			$message = '처리되었지만 재고가 없는 상품 : ' . implode($message, ',');
			$this->setMessage($message, 'error');
		}

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON')))
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module',Context::get('module'),'act', 'dispNstoreAdminOrderManagement','status',Context::get('status'));
			$this->setRedirectUrl($returnUrl);
		}
	}

	function procNstoreAdminUpdateDeliveryInfo()
	{
		$oNstoreController = &getController('nstore');

		$carts = Context::get('cart');
		if(!is_array($carts)) $carts = array();
		$order_srls = Context::get('order_srls');
		$express_ids = Context::get('express_id');
		$invoice_nos = Context::get('invoice_no');
		

		/*
		if(!$carts)  // check box 선택한 주문이 없을때 뒤로가기
		{
			return new Object(-1, '선택한 주문이 없습니다.');
		}
		 */

		foreach ($order_srls as $key=>$order_srl)
		{
			$express_id = $express_ids[$key];
			$invoice_no = $invoice_nos[$key];

			$args->order_srl = $order_srl;
			$args->express_id = $express_id;
			$args->invoice_no = $invoice_no;

			// 상태값변경, 배송회사, 운송장번호 데이터가 없으면 업데이트 필요치 않는다.
			if(!$args->express_id&&!$args->invoice_no)
			{
				continue;
			}

			// express_id값은 항상 넘어 오므로 이 루틴을 타게된다.
			$output = $oNstoreController->updateOrderStatus($order_srl, $args);
			if(!$output->toBool()) return $output;
		}

		$this->setMessage('success_saved');

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON')))
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module',Context::get('module'),'act', 'dispNstoreAdminOrderManagement','status',Context::get('status'));
			$this->setRedirectUrl($returnUrl);
		}
	}


	function procNstoreAdminUpdateOrderDetail() 
	{
		$order_srl = Context::get('order_srl');
		$primary_express_id = Context::get('primary_express_id');
		$primary_invoice_no = Context::get('primary_invoice_no');

		if($primary_express_id || $primary_invoice_no)
		{
			// order info
			$args->order_srl = $order_srl;
			$args->express_id = $primary_express_id;
			$args->invoice_no = $primary_invoice_no;
			$output = executeQuery('nstore.updateOrderStatus', $args);
			if(!$output->toBool()) return $output;
		}
	

		$cart_srls = Context::get('cart_srls');
		$express_ids = Context::get('express_id');
		$invoice_nos = Context::get('invoice_no');

		foreach($cart_srls as $key=>$cart_srl)
		{
			$express_id = $express_ids[$key];
			$invoice_no = $invoice_nos[$key];

			$args->cart_srl = $cart_srl;
			$args->express_id = $express_id;
			$args->invoice_no = $invoice_no;

			if(!$args->express_id&&!$args->invoice_no)
			{
				continue;
			}

			// cart info
			$output = executeQuery('nstore.updateCartOrderStatus', $args);
			if(!$output->toBool()) return $output;
		}

		$this->setMessage('success_saved');

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) 
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module',Context::get('module'),'act', 'dispNstoreAdminOrderDetail','status',Context::get('status'),'order_srl',Context::get('order_srl'));
			$this->setRedirectUrl($returnUrl);
		}
	}

	function procNstoreAdminDeleteOrders()
	{
		$order_srls = Context::get('order_srl');
		$order_srls = explode(',',$order_srls);

		foreach ($order_srls as $order_srl)
		{
			if(!$order_srl)
			{
				continue;
			}
			// delete cart items.
			$args->order_srl = $order_srl;
			$output = executeQuery('nstore.deleteCartItemsByOrderSrl', $args);
			if(!$output->toBool()) return $output;

			// delete order info.
			$args->order_srl = $order_srl;
			$output = executeQuery('nstore.deleteOrder', $args);
			if(!$output->toBool()) return $output;
		}

		$this->setMessage('success_deleted');
		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) 
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'disp'.$this->getExtModCap().'AdminOrderManagement','status',Context::get('status'));
			$this->setRedirectUrl($returnUrl);
		}
	}

	function procNstoreAdminOrderExcelDownload() 
	{
		if(!Context::get('status')) Context::set('status','1');
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
		$output = executeQueryArray('nstore.getOrderItemsByStatus', $args);
		if(!$output->toBool()) return $output;
		$data = $output->data;

		$representative = NULL;
		if(count($data))
		{
			$list_keys = array_keys($data);
			$first_order = $data[$list_keys[0]];
			$extra_vars = unserialize($first_order->extra_vars);
			if($extra_vars)
			{
				$keys = array_keys($extra_vars);
				$representative = $keys[0];
			}
		}

		header("Content-Type: Application/octet-stream; charset=UTF-8");
		header("Content-Disposition: attachment; filename=\"ORDERITEMS-" . date('Ymd') . ".csv\"");

		echo chr(hexdec("EF"));
		echo chr(hexdec("BB"));
		echo chr(hexdec("BF"));
		echo Context::getLang('csv_header')."\r\n";
		foreach($data as $no=>$rec)
		{
			debugPrint('$rec');
			debugPrint($rec);
			echo sprintf("%s,%s,%s,%s,%s\r\n"
				, zdate($rec->regdate,'Y-m-d')
				, $rec->item_name
				, $rec->quantity
				, $rec->price
				, $rec->purchaser_name
			);
		}
		exit(0);
	}

	function procNstoreAdminCSVDownloadByOrder() 
	{
		$oEpayModel = &getModel('epay');

		if(!Context::get('status')) Context::set('status','1');
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
		if(!Context::get('s_year')) Context::set('s_year', date('Y'));
		$args->regdate = Context::get('s_year');
	   	if(Context::get('s_month')) $args->regdate = $args->regdate . Context::get('s_month');
		$args->list_count = 99999;
		$output = executeQueryArray('nstore.getOrderListByStatus', $args);
		if(!$output->toBool()) return $output;
		$data = $output->data;


		header("Content-Type: Application/octet-stream; charset=UTF-8");
		header("Content-Disposition: attachment; filename=\"ORDERITEMS-" . date('Ymd') . ".csv\"");

		echo chr(hexdec("EF"));
		echo chr(hexdec("BB"));
		echo chr(hexdec("BF"));
		echo Context::getLang('order_csv_header');
		if(count($data))
		{
			$list_keys = array_keys($data);
			$first_order = $data[$list_keys[0]];
			$extra_vars = unserialize($first_order->extra_vars);
			foreach($extra_vars as $key=>$val)
			{
				echo ','.$key;
			}
		}
		echo "\r\n";
		foreach($data as $no=>$rec)
		{
			debugPrint('$rec');
			debugPrint($rec);
			echo sprintf("%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s"
				, zdate($rec->regdate,'Y-m-d')
				, $rec->order_srl
				, $rec->title
				, $rec->item_count
				, $rec->user_id
				, $rec->nick_name
				, Context::getLang($this->payment_method[$rec->payment_method])
				, $rec->mileage
				, $rec->mileage_save
				, $rec->use_mileage
				, $rec->total_price
				, $rec->sum_price
				, $rec->delivery_fee
				, $rec->total_discounted_price
				, $rec->total_discount_amount
				, $rec->taxation_amount
				, $rec->supply_amount
				, $rec->vat
				, $rec->taxfree_amount
				, $rec->invoice_no
				, $this->delivery_companies[$rec->express_id]
				, $rec->delivfee_inadvance
			);
			$extra_vars = unserialize($rec->extra_vars);
			if($extra_vars)
			{
				foreach($extra_vars as $key=>$val)
				{
					if(is_array($val))
						echo ','.implode(' ', $val);
					else
						echo ','.$val;
				}
			}
			echo "\r\n";
		}
		exit(0);
	}
}

/* End of file nstore.admin.controller.php */
/* Location: ./modules/nstore/nstore.admin.controller.php */
