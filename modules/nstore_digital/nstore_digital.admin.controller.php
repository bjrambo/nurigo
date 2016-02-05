<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nstore_digitalAdminController
 * @author NURIGO(contact@nurigo.net)
 * @brief  nstore_digitalAdminController
 */
class nstore_digitalAdminController extends nstore_digital
{
	/**
	 * @brief 모듈 환경설정값 쓰기
	 **/
	function procNstore_digitalAdminConfig() 
	{
		$args = Context::getRequestVars();
		
		// save module configuration.
		$oModuleControll = getController('module');
		$output = $oModuleControll->insertModuleConfig('nstore_digital', $args);

		$this->setMessage('success_updated');

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) 
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispNstore_digitalAdminConfig','module_srl',Context::get('module_srl'));
			$this->setRedirectUrl($returnUrl);
			return;
		}
	}


	/**
	 * @brief 모듈 환경설정값 쓰기
	 **/
	function procNstore_digitalAdminInsertModInst() 
	{
		// module 모듈의 model/controller 객체 생성
		$oModuleController = &getController('module');
		$oModuleModel = &getModel('module');

		// 게시판 모듈의 정보 설정
		$args = Context::getRequestVars();
		$args->module = 'nstore_digital';

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
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispNstore_digitalAdminInsertModInst','module_srl',$output->get('module_srl'));
			$this->setRedirectUrl($returnUrl);
			return;
		}
	}

	function procNstore_digitalAdminDeleteModInst()
	{
		$module_srl = Context::get('module_srl');

		$oModuleController = &getController('module');
		$output = $oModuleController->deleteModule($module_srl);
		if(!$output->toBool()) return $output;

		$this->add('module','nstore_digital');
		$this->add('page',Context::get('page'));
		$this->setMessage('success_deleted');

		$returnUrl = getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispNstore_digitalAdminModInstList');
		$this->setRedirectUrl($returnUrl);
	}

	function procNstore_digitalAdminUpdateStatus() 
	{
		$oNstore_digitalController = &getController('nstore_digital');
		$oNstore_digitalModel = &getModel('nstore_digital');
		$config = $oNstore_digitalModel->getModuleConfig();

		$carts = Context::get('cart');
		if(!is_array($carts))
		{
			$carts = array();
		}
		$order_srls = Context::get('order_srls');
		$express_ids = Context::get('express_id');
		$invoice_nos = Context::get('invoice_no');
		$order_status = Context::get('order_status');

		
		/*
		if(!$carts)  // check box 선택한 주문이 없을때 뒤로가기
		{
			return new Object(-1, '선택한 주문이 없습니다.');
		}
		 */
		foreach ($order_srls as $key=>$order_srl) {
			if (!in_array($order_srl, $carts)) continue;

			if (!$order_status || !$order_srl) continue;

			$output = $oNstore_digitalController->updateOrderStatus($order_srl, $order_status);
			if (!$output->toBool()) return $output;

			$this->updatePeriodByStatus($order_srl, $order_status, Context::get('status'));
		}

		$this->setMessage('success_saved');

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON')))
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module',Context::get('module'),'act', 'dispNstore_digitalAdminOrderManagement','status',Context::get('status'));
			$this->setRedirectUrl($returnUrl);
			return;
		}
	}

	function procNstore_digitalAdminUpdatePeriodStatus() 
	{
		$oNstore_digitalController = &getController('nstore_digital');
		$oNdc_Controller = &getController('nstore_digital_contents');
		$oNstore_digitalModel = &getModel('nstore_digital');
		$config = $oNstore_digitalModel->getModuleConfig();

		if(!Context::get('cart')) return new Object(-1, '상품을 체크해주세요');

		$carts = Context::get('cart');
		if(!is_array($carts))
		{
			$carts = array();
		}
		$order_srls = Context::get('order_srls');
		$invoice_nos = Context::get('invoice_no');
		$order_status = Context::get('order_status');
		$before_status = Context::get('status');
		
		foreach ($carts as $key=>$val) {

			if (!$order_status || !$val) continue;

			$p_args->period_srl = $val;

			$output = executeQuery('nstore_digital.getPeriod', $p_args);
			if(!$output->toBool()) return $output;

			$member_srl = $output->data->member_srl;
			$end_date = $output->data->end_date;
			$cart_srl = $output->data->cart_srl;
			

			if($before_status == '1' && $order_status == '2')
			{
				$q_args->cart_srl = $cart_srl;
				$q_args->member_srl = $member_srl;

				$output = executeQuery('nstore_digital.getPeriod', $q_args);
				if(!$output->toBool()) return $output;

				/*
				// 같은상품이 status 2면 만료로 옮긴다.
				if($output->data)
				{
					foreach($output->data as $k => $v)
					{
						if($v->order_status == '2' || $v->order_status == '3')
						{
							$vars->period_srl = $v->period_srl;
							$vars->order_status = '9';
							$output = executeQuery('nstore_digital.updatePeriod', $vars);
						}
					}
				}
				*/

				//$oNdc_Controller->setPeriod($cart_srl, $end_date);

				$vars->cart_srl = $cart_srl;
				$vars->period = $end_date;

				$output = executeQuery('nstore_digital.updateCartItemPeriod', $vars);
				if(!$output->toBool()) return $output;

				unset($vars);
			}

			$args->period_srl = $val;
			$args->order_status = $order_status;
			$output = executeQuery('nstore_digital.updatePeriod', $args);

			if (!$output->toBool()) return $output;
		}

		$this->setMessage('success_saved');

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON')))
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module',Context::get('module'),'act', 'dispNstore_digitalAdminPeriodManagement','status',Context::get('status'));
			$this->setRedirectUrl($returnUrl);
			return;
		}
	}

	function procNstore_digitalAdminUpdateOrderDetail() 
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
			$output = executeQuery('nstore_digital.updateOrderStatus', $args);
			if(!$output->toBool())
			{
				return $output;
			}
		}
	

		$cart_srls = Context::get('cart_srls');
		$express_ids = Context::get('express_id');
		$invoice_nos = Context::get('invoice_no');

		foreach ($cart_srls as $key=>$cart_srl) {
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
			$output = executeQuery('nstore_digital.updateCartOrderStatus', $args);
			if(!$output->toBool())
			{
				return $output;
			}
		}

		$this->setMessage('success_saved');

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) 
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module',Context::get('module'),'act', 'disp'.$this->getExtModCap().'AdminOrderDetail','status',Context::get('status'),'order_srl',Context::get('order_srl'));
			$this->setRedirectUrl($returnUrl);
			return;
		}
	}
	
	function procNstore_digitalAdminDeleteOrders()
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
			$output = executeQuery('nstore_digital.deleteCartItemsByOrderSrl', $args);
			if(!$output->toBool()) return $output;

			// delete order info.
			$args->order_srl = $order_srl;
			$output = executeQuery('nstore_digital.deleteOrder', $args);
			if(!$output->toBool()) return $output;

			$args->order_srl = $order_srl;
			$args->status = Context::get('status');

			$output = executeQuery('nstore_digital.getPeriod', $args);
			if(!$output->toBool()) return $output;
		}

		$this->setMessage('success_deleted');
		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) 
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'disp'.$this->getExtModCap().'AdminOrderManagement','status',Context::get('status'));
			$this->setRedirectUrl($returnUrl);
			return;
		}
	}

	function procNstore_digitalAdminDeletePeriod()
	{
		$oNdc_Controller = &getController('nstore_digital_contents');

		$period_srls = Context::get('period_srl');
		$period_srls = explode(',',$period_srls);

		foreach ($period_srls as $period_srl)
		{
			if(!$period_srl) continue;

			if(Context::get('status') == '2' || Context::get('status') == '3')
			{
				$args->period_srl = $period_srl;
				$output = executeQuery('nstore_digital.getPeriod', $args);
				if(!$output->toBool()) return $output;

				$cart_srl = $output->data->cart_srl;
				$oNdc_Controller->deletePeriod($cart_srl);
			}
			// delete nstore_digital_period.
			$args->period_srl = $period_srl;
			$output = executeQuery('nstore_digital.deletePeriod', $args);
			if(!$output->toBool()) return $output;
		}

		$this->setMessage('success_deleted');
		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) 
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'disp'.$this->getExtModCap().'AdminPeriodManagement','status',Context::get('status'));
			$this->setRedirectUrl($returnUrl);
			return;
		}
	}

	function procNstore_digitalAdminUpdatePeriod() 
	{
		$state = Context::get('state');
		$args->cart_srl = Context::get('cart_srl');
		$args->period = Context::get('period');

		if(!$args->cart_srl || !$args->period || !$state) return new Object(-1, '빈칸을 채워주세요.');

		$output = executeQuery('nstore_digital.updateCartItemPeriod', $args);
		if(!$output->toBool()) return $output;

		$this->setMessage('success_update');
		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) 
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'disp'.$this->getExtModCap().'AdminIndividualOrderManagement','status',$state);
			$this->setRedirectUrl($returnUrl);
			return;
		}
	}

	function updatePeriodByStatus($order_srl, $order_status, $before_status)
	{
		if(!$order_srl || !$order_status || !$before_status) return;
		
		// 입금대기에서 넘어온게 아니라면 리턴
		if(!$before_status == '1') return;
		// 입금완료나 구매완료로 가는게 아니라면 리턴
		if(3 < $order_status || 2 > $order_status) return;


		$oNdcModel = &getModel('nstore_digital_contents');

		$args->order_srl = $order_srl;
		$output = executeQueryArray('nstore_digital.getCartItemsByOrderSrl', $args);
		if(!$output->toBool()) return $output;

		if(!$output->data) $items = array();
		else $items = $output->data;

		foreach($items as $k => $v)
		{
			$item_config = $oNdcModel->getItemConfig($v->item_srl);
			if(!$item_config->period) continue;
			if($v->period) continue;

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
			unset($vars);
		}
	}
}
/* End of file nstore_digital.admin.controller.php */
/* Location: ./modules/nstore_digital/nstore_digital.admin.controller.php */
