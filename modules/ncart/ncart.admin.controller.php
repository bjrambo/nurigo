<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  ncartAdminController
 * @author NURIGO(contact@nurigo.net)
 * @brief  ncartAdminController
 */
class ncartAdminController extends ncart
{
	function init() 
	{
	}

	function _createInsertAddressRuleset($fieldset_list)
	{
		$xml_file = './files/ruleset/ncart_insertAddress.xml';
		$buff = '<?xml version="1.0" encoding="utf-8"?>'
				.'<ruleset version="1.5.0">'
				.'<customrules>'
				.'</customrules>'
				.'<fields>%s</fields>'						
				.'</ruleset>';

		$fields = array();
		$fields[] = '<field name="member_srl" required="true" rule="number" />';
		$fields[] = '<field name="opt" required="true" />';
		$fields[] = '<field name="title" required="true" />';
		$fields[] = '<field name="address" required="true" />';
		
		if(count($fieldset_list))
		{
			foreach($fieldset_list as $fieldset)
			{
				foreach($fieldset->fields as $field)
				{
					if($field->required=='Y')
					{
						switch($field->column_type)
						{
							case 'tel':
							case 'kr_zip':
								$fields[] = sprintf('<field name="%s[]" required="true" />', $field->column_name);
								break;
							case 'email_address':
								$fields[] = sprintf('<field name="%s" required="true" rule="email"/>', $field->column_name);
								break;
							case 'user_id':
								$fields[] = sprintf('<field name="%s" required="true" rule="userid" length="3:20" />', $field->column_name);
								break;
							default:
								$fields[] = sprintf('<field name="%s" required="true" />', $field->column_name);
								break;
						}
					}
				}
			}
		}

		$xml_buff = sprintf($buff, implode('', $fields));
		FileHandler::writeFile($xml_file, $xml_buff);
		unset($xml_buff);

		$validator   = new Validator($xml_file);
		$validator->setCacheDir('files/cache');
		$validator->getJsPath();
	}


	/**
	 * @brief 모듈 환경설정값 쓰기
	 **/
	function procNcartAdminConfig() 
	{

		$args = Context::getRequestVars();
		
		// save module configuration.
		$oModuleControll = getController('module');
		$output = $oModuleControll->insertModuleConfig('ncart', $args);

		$this->setMessage('success_updated');

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) 
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispNcartAdminConfig','module_srl',Context::get('module_srl'));
			$this->setRedirectUrl($returnUrl);
			return;
		}

	}


	/**
	 * @brief 모듈 환경설정값 쓰기
	 **/
	function procNcartAdminInsertModInst() 
	{
		// module 모듈의 model/controller 객체 생성
		$oModuleController = &getController('module');
		$oModuleModel = &getModel('module');

		// 게시판 모듈의 정보 설정
		$args = Context::getRequestVars();
		$args->module = 'ncart';

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

		$returnUrl = getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispNcartAdminInsertModInst','module_srl',$this->get('module_srl'));
		$this->setRedirectUrl($returnUrl);
	}

	function procNcartAdminDeleteModInst() 
	{
		$module_srl = Context::get('module_srl');

		$oModuleController = &getController('module');
		$output = $oModuleController->deleteModule($module_srl);
		if(!$output->toBool())
		{
			return $output;
		}

		$this->add('module','ncart');
		$this->add('page',Context::get('page'));
		$this->setMessage('success_deleted');

		$returnUrl = getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispNcartAdminModInstList');
		$this->setRedirectUrl($returnUrl);
	}


	function procNcartAdminUpdateStatus() 
	{
		$oNcartController = &getController('ncart');

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

			$express_id = $express_ids[$key];
			$invoice_no = $invoice_nos[$key];

			$args->order_srl = $order_srl;
			$args->order_status = $order_status;
			$args->express_id = $express_id;
			$args->invoice_no = $invoice_no;

			// 체크되지 않은 주문일 경우 상태를 변경하지 않는다.
			if(!in_array($order_srl, $carts))
			{
				unset($args->order_status);
			}

			// 상태값변경, 배송회사, 운송장번호 데이터가 없으면 업데이트 필요치 않는다.
			if(!$args->order_status&&!$args->express_id&&!$args->invoice_no)
			{
				continue;
			}

			// express_id값은 항상 넘어 오므로 이 루틴을 타게된다.
			$output = $oNcartController->updateOrderStatus($order_srl, $args);
			if(!$output->toBool())
			{
				return $output;
			}
		}

		$this->setMessage('success_saved');

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON')))
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module',Context::get('module'),'act', 'dispNcartAdminOrderManagement','status',Context::get('status'));
			$this->setRedirectUrl($returnUrl);
			return;
		}
	}

	
	function procNcartAdminDeleteOrders()
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
			$output = $this->executeQuery('deleteCartItemsByOrderSrl', $args);
			if(!$output->toBool())
			{
				return $output;
			}

			// delete order info.
			$args->order_srl = $order_srl;
			$output = $this->executeQuery('deleteOrder', $args);
			if(!$output->toBool())
			{
				return $output;
			}
		}

		$this->setMessage('success_deleted');
		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) 
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispNcartAdminOrderManagement','status',Context::get('status'));
			$this->setRedirectUrl($returnUrl);
			return;
		}
	}

	function procNcartAdminInsertFieldset()
	{
		$args->fieldset_srl = getNextSequence();
		$args->module_srl = Context::get('module_srl');
		$args->fieldset_title = Context::get('fieldset_title');
		$output = executeQuery('ncart.insertFieldset', $args);
		if(!$output->toBool()) return $output;

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispNcartAdminOrderForm');
		$this->setRedirectUrl($returnUrl);
	}

	function procNcartAdminDeleteFieldset()
	{
		$args->module_srl = Context::get('module_srl');
		$args->fieldset_srl = Context::get('fieldset_srl');
		// deleting fields
		$output = executeQuery('ncart.deleteFieldsByFieldsetSrl', $args);
		if(!$output->toBool()) return $output;
		// deleting a fieldset
		$output = executeQuery('ncart.deleteFieldset', $args);
		if(!$output->toBool()) return $output;

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispNcartAdminOrderForm');
		$this->setRedirectUrl($returnUrl);
	}

	function procNcartAdminArrangeItem()
	{
		$args->module_srl = Context::get('module_srl');
		$args->fieldset_srl = Context::get('fieldset_srl');
		$args->fieldset_title = Context::get('fieldset_title');
		$args->proc_modules = implode(',', Context::get('proc_modules'));
		$output = executeQuery('ncart.updateFieldset', $args);
		debugPrint('updateFieldset');
		debugPrint($args->proc_modules);
		debugPrint($output);
		if(!$output->toBool()) return $output;


		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispNcartAdminOrderForm');
		$this->setRedirectUrl($returnUrl);

		$this->setMessage('success_saved');
	}

	function procNcartAdminInsertField()
	{
		$oNcartModel = &getModel('ncart');

		$args = Context::gets('module_srl', 'fieldset_srl', 'field_srl', 'column_type', 'column_name', 'column_title', 'required', 'is_head', 'default_value', 'description');
		if($args->field_srl)
		{
			$output = executeQuery('ncart.updateField', $args);
		}
		else
		{
			$output = executeQuery('ncart.insertField', $args);
		}
		if(!$output->toBool()) return $output;

		$fieldset_list = $oNcartModel->getFieldSetList($args->module_srl);

		$this->_createInsertAddressRuleset($fieldset_list);

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispNcartAdminOrderForm');
		$this->setRedirectUrl($returnUrl);
	}

	function procNcartAdminDeleteField()
	{
		$args->field_srl = Context::get('field_srl');
		// deleting a field
		$output = executeQuery('ncart.deleteField', $args);
		if(!$output->toBool()) return $output;

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispNcartAdminOrderForm');
		$this->setRedirectUrl($returnUrl);
	}

	function procNcartAdminUpdateFieldListOrder() 
	{
		$order = Context::get('order');
		parse_str($order);
		$idx = 1;
		if(is_array($record))
		{
			foreach ($record as $field_srl) {
				$args->field_srl = $field_srl;
				$args->list_order = $idx;
				$output = executeQuery('ncart.updateFieldListOrder', $args);
				if(!$output->toBool()) return $output;
				$idx++;
			}
		}
	}
}

/* End of file ncart.admin.controller.php */
/* Location: ./modules/ncart/ncart.admin.controller.php */
