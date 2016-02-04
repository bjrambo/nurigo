<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nstore_digital
 * @author NURIGO(contact@nurigo.net)
 * @brief  nstore_digital
 */

define('WAIT_FOR_DEPOSIT', '1');
define('PREPARE_DELIVERY', '2');

require_once(_XE_PATH_.'modules/nproduct/nproduct.item.php');
class nstore_digital extends ModuleObject
{
	const ORDER_STATE_PAID = '2';
	const ORDER_STATE_COMPLETE = '3';
	var $order_status = array('0'=>'cart_keep', '1'=>'wait_deposit', '2'=>'deposit_done', '3'=>'transaction_done','A'=>'cancelled','B'=>'return_exchange','C'=>'refund');
	var $payment_method = array(
		'CC'=>'credit_card'
		,'BT'=>'bank_transfer'
		,'IB'=>'internet_banking'
		,'VA'=>'virtual_account'
		,'MP'=>'mobile_phone'
		,'MI'=>'mileage'
		,'MO'=>'manual_order'
	);


	/**
	 * @brief 모듈 설치 실행
	 **/
	function moduleInstall()
	{
		$oModuleModel = &getModel('module');
		$oModuleController = &getController('module');

		// 다운로드 권한 체크
		if (!$oModuleModel->getTrigger('file.downloadFile', 'nstore_digital', 'controller', 'triggerCheckPermission', 'before'))
		{
			$oModuleController->insertTrigger('file.downloadFile', 'nstore_digital', 'controller', 'triggerCheckPermission', 'before');
		}
		if (!$oModuleModel->getTrigger('file.downloadFile', 'nstore_digital', 'controller', 'triggerUpdateDownloadedCount', 'after'))
		{
			$oModuleController->insertTrigger('file.downloadFile', 'nstore_digital', 'controller', 'triggerUpdateDownloadedCount', 'after');
		}

		// 만기일 결제 부분.
		if (!$oModuleModel->getTrigger('epay.processPayment', 'nstore_digital', 'controller', 'triggerProcessPayment', 'after'))
		{
			$oModuleController->insertTrigger('epay.processPayment', 'nstore_digital', 'controller', 'triggerProcessPayment', 'after');
		}
		if (!$oModuleModel->getTrigger('epay.processReview', 'nstore_digital', 'controller', 'triggerProcessReview', 'before'))
		{
			$oModuleController->insertTrigger('epay.processReview', 'nstore_digital', 'controller', 'triggerProcessReview', 'before');
		}

		// nproduct 상품등록, 수정 할 때 처리모듈 목록 취합
		if (!$oModuleModel->getTrigger('nproduct.getProcModules', 'nstore_digital', 'model', 'triggerGetProcModules', 'before')) {
			$oModuleController->insertTrigger('nproduct.getProcModules', 'nstore_digital', 'model', 'triggerGetProcModules', 'before');
		}

		return new Object();
	}

	/**
	 * @brief 설치가 이상없는지 체크
	 **/
	function checkUpdate()
	{
		$oModuleModel = &getModel('module');
		$oDB = &DB::getInstance();

		if (!$oModuleModel->getTrigger('file.downloadFile', 'nstore_digital', 'controller', 'triggerCheckPermission', 'before')) return true;
		if (!$oModuleModel->getTrigger('file.downloadFile', 'nstore_digital', 'controller', 'triggerUpdateDownloadedCount', 'after')) return true;
		if (!$oModuleModel->getTrigger('nproduct.getProcModules', 'nstore_digital', 'model', 'triggerGetProcModules', 'before')) return true;

		if (!$oModuleModel->getTrigger('epay.processPayment', 'nstore_digital', 'controller', 'triggerProcessPayment', 'after'))
		{
			return true;
		}
		if (!$oModuleModel->getTrigger('epay.processReview', 'nstore_digital', 'controller', 'triggerProcessReview', 'before'))
		{
			return true;
		}

		if(!$oDB->isColumnExists('nstore_digital_cart', 'period')) return true;

		return false;
	}

	/**
	 * @brief 업데이트(업그레이드)
	 **/
	function moduleUpdate()
	{
		$oDB = &DB::getInstance();
		$oModuleModel = &getModel('module');
		$oModuleController = &getController('module');

		if(!$oModuleModel->getTrigger('file.downloadFile', 'nstore_digital', 'controller', 'triggerCheckPermission', 'before')) {
			$oModuleController->insertTrigger('file.downloadFile', 'nstore_digital', 'controller', 'triggerCheckPermission', 'before');
		}
		if(!$oModuleModel->getTrigger('file.downloadFile', 'nstore_digital', 'controller', 'triggerUpdateDownloadedCount', 'after')) {
			$oModuleController->insertTrigger('file.downloadFile', 'nstore_digital', 'controller', 'triggerUpdateDownloadedCount', 'after');
		}
		if (!$oModuleModel->getTrigger('nproduct.getProcModules', 'nstore_digital', 'model', 'triggerGetProcModules', 'before')) {
			$oModuleController->insertTrigger('nproduct.getProcModules', 'nstore_digital', 'model', 'triggerGetProcModules', 'before');
		}

		if (!$oModuleModel->getTrigger('epay.processPayment', 'nstore_digital', 'controller', 'triggerProcessPayment', 'after'))
		{
			$oModuleController->insertTrigger('epay.processPayment', 'nstore_digital', 'controller', 'triggerProcessPayment', 'after');
		}
		if (!$oModuleModel->getTrigger('epay.processReview', 'nstore_digital', 'controller', 'triggerProcessReview', 'before'))
		{
			$oModuleController->insertTrigger('epay.processReview', 'nstore_digital', 'controller', 'triggerProcessReview', 'before');
		}

		if(!$oDB->isColumnExists('nstore_digital_cart', 'period'))
		{
			$oDB->addColumn('nstore_digital_cart', 'period', 'number', 11);
			$oDB->addIndex('nstore_digital_cart', 'idx_period', 'period');
		}
	}

	function moduleUninstall()
	{
		$oModuleController = &getController('module');
		$oModuleController->deleteTrigger('file.downloadFile', 'nstore_digital', 'controller', 'triggerCheckPermission', 'before');
		$oModuleController->deleteTrigger('file.downloadFile', 'nstore_digital', 'controller', 'triggerUpdateDownloadedCount', 'after');
		$oModuleController->deleteTrigger('nproduct.getProcModules', 'nstore_digital', 'model', 'triggerGetProcModules', 'before');
	}

	/**
	 * @brief 캐시파일 재생성
	 **/
	function recompileCache()
	{
	}

	function getOrderStatus()
	{
		static $trans_flag = FALSE;

		if ($trans_flag) return $this->order_status;
		foreach ($this->order_status as $key => $val)
		{
			if (Context::getLang($val)) $this->order_status[$key] = Context::getLang($val);
		}
		$trans_flag = TRUE;
		return $this->order_status;
	}

	function getPaymentMethods()
	{
		static $trans_flag = FALSE;

		if ($trans_flag) return $this->payment_method;
		foreach ($this->payment_method as $key => $val)
		{
			if (Context::getLang($val)) $this->payment_method[$key] = Context::getLang($val);
		}
		$trans_flag = TRUE;
		return $this->payment_method;
	}


}
/* End of file nstore_digital.class.php */
/* Location: ./modules/nstore_digital/nstore_digital.class.php */
