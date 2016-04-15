<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nstore
 * @author NURIGO(contact@nurigo.net)
 * @brief  nstore
 */
define('WAIT_FOR_DEPOSIT', '1');
define('PREPARE_DELIVERY', '2');

require_once(_XE_PATH_ . 'modules/nproduct/nproduct.item.php');

class nstore extends ModuleObject
{
	const ORDER_STATE_COMPLETE = '6';
	var $order_status = array(
		'0' => 'cart_keep',
		'1' => 'wait_deposit',
		'2' => 'deposit_done',
		'3' => 'prepare_delivery',
		'4' => 'on_delivery',
		'5' => 'delivery_done',
		'6' => 'transaction_done',
		'A' => 'cancelled',
		'B' => 'returns',
		'C' => 'exchanges',
		'D' => 'refund'
	);
	var $delivery_companies = array(
		'00' => '직배송',
		'16' => '경동택배',
		'18' => '대신택배',
		'20' => '대한통운',
		'22' => '동부택배',
		'24' => '로젠택배',
		'26' => '우체국택배',
		'28' => '이노지스택배',
		'30' => '일양로지스택배',
		'32' => '한덱스',
		'34' => '한의사랑택배',
		'36' => '한진택배',
		'38' => '현대택배',
		'40' => '호남택배',
		'42' => 'CJ GLS',
		'44' => 'CVSnet 편의점택배',
		'46' => 'DHL',
		'48' => 'EMS',
		'50' => 'FedEx',
		'52' => 'GTX',
		'54' => 'KG옐로우캡택배',
		'56' => 'TNT Express',
		'58' => 'UPS',
		'60' => 'KGB택배'
	);
	var $delivery_inquiry_urls = array(
		'16' => 'http://www.kdexp.com/sub3_shipping.asp?stype=1&p_item=',
		'18' => 'http://home.daesinlogistics.co.kr/daesin/jsp/d_freight_chase/d_general_process2.jsp?billno1=',
		'20' => 'https://www.doortodoor.co.kr/parcel/doortodoor.do?fsp_action=PARC_ACT_002&fsp_cmd=retrieveInvNoACT&invc_no=',
		'22' => 'http://www.dongbups.com/newHtml/delivery/dvsearch_View.jsp?item_no=',
		'24' => 'http://www.ilogen.com/iLOGEN.Web.New/TRACE/TraceView.aspx?gubun=slipno&slipno=',
		'26' => 'http://service.epost.go.kr/trace.RetrieveRegiPrclDeliv.postal?sid1=',
		'28' => 'http://www.innogis.net/trace02.asp?invoice=',
		'30' => 'http://www.ilyanglogis.com/functionality/tracking_result.asp?hawb_no=',
		'32' => 'http://btob.sedex.co.kr/work/app/tm/tmtr01/tmtr01_s4.jsp?IC_INV_NO=',
		'34' => 'http://www.hanips.com/html/sub03_03_1.html?logicnum=',
		'36' => 'http://www.hanjin.co.kr/Delivery_html/inquiry/result_waybill.jsp?wbl_num=',
		'38' => 'http://www.hlc.co.kr/personalService/tracking/06/tracking_goods_result.jsp?InvNo=',
		'40' => 'http://honam.enfrom.com/YYSearch/YYSearch.jsp?&Slip01=',
		'42' => 'http://nexs.cjgls.com/web/service02_01.jsp?slipno=',
		'44' => 'http://was.cvsnet.co.kr/_ver2/board/ctod_status.jsp?invoice_no=',
		'46' => 'http://www.dhl.co.kr/ko/express/tracking.shtml?pageToInclude=RESULTS&type=fasttrack&AWB=',
		'48' => 'http://service.epost.go.kr/trace.RetrieveEmsTrace.postal?ems_gubun=E&POST_CODE=',
		'50' => 'http://www.fedex.com/Tracking?ascend_header=1&clienttype=dotcomreg&cntry_code=kr&language=korean&tracknumbers=',
		'52' => 'http://www.gtx2010.co.kr/del_inquiry_result.html?s_gbn=1&awblno=',
		'54' => 'http://www.yellowcap.co.kr/custom/inquiry_result.asp?invoice_no=',
		'56' => 'http://www.tnt.com/webtracker/tracking.do?respCountry=kr&respLang=ko&searchType=CON&cons=',
		'58' => 'http://www.ups.com/WebTracking/track?loc=ko_KR&InquiryNumber1=',
		'60' => 'http://www.kgbls.co.kr/sub5/trace.asp?f_slipno='
	);

	var $payment_method = array(
		'CC' => 'credit_card',
		'BT' => 'bank_transfer',
		'IB' => 'internet_banking',
		'VA' => 'virtual_account',
		'MP' => 'mobile_phone',
		'MI' => 'mileage'
	);

	var $soldout_process = array(
		'P' => '포인트로 환불',
		'C' => '현금으로 환불',
		'H' => '전화요망',
		'R' => '대체상품으로 배송'
	);


	function installTriggers()
	{
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');
		/*
		if (!$oModuleModel->getTrigger('epay.processPayment', 'nstore', 'controller', 'triggerProcessPayment', 'after')) {
			$oModuleController->insertTrigger('epay.processPayment', 'nstore', 'controller', 'triggerProcessPayment', 'after');
		}
		if (!$oModuleModel->getTrigger('epay.processReview', 'nstore', 'controller', 'triggerProcessReview', 'before')) {
			$oModuleController->insertTrigger('epay.processReview', 'nstore', 'controller', 'triggerProcessReview', 'before');
		}
		 */
		if(!$oModuleModel->getTrigger('epay.escrowDelivery', 'nstore', 'controller', 'triggerEscrowDelivery', 'after'))
		{
			$oModuleController->insertTrigger('epay.escrowDelivery', 'nstore', 'controller', 'triggerEscrowDelivery', 'after');
		}
		if(!$oModuleModel->getTrigger('epay.escrowConfirm', 'nstore', 'controller', 'triggerEscrowConfirm', 'after'))
		{
			$oModuleController->insertTrigger('epay.escrowConfirm', 'nstore', 'controller', 'triggerEscrowConfirm', 'after');
		}
		if(!$oModuleModel->getTrigger('epay.escrowDenyConfirm', 'nstore', 'controller', 'triggerEscrowDenyConfirm', 'after'))
		{
			$oModuleController->insertTrigger('epay.escrowDenyConfirm', 'nstore', 'controller', 'triggerEscrowDenyConfirm', 'after');
		}
		// nproduct 상품등록, 수정 할 때 처리모듈 목록 취합
		if(!$oModuleModel->getTrigger('nproduct.getProcModules', 'nstore', 'model', 'triggerGetProcModules', 'before'))
		{
			$oModuleController->insertTrigger('nproduct.getProcModules', 'nstore', 'model', 'triggerGetProcModules', 'before');
		}

		if(!$oModuleModel->getTrigger('member.getMemberMenu', 'nstore', 'model', 'triggerMemberMenu', 'before'))
		{
			$oModuleController->insertTrigger('member.getMemberMenu', 'nstore', 'model', 'triggerMemberMenu', 'before');
		}
		// 2013. 09. 25 when add new menu in sitemap, custom menu add
		if(!$oModuleModel->getTrigger('menu.getModuleListInSitemap', 'nstore', 'model', 'triggerModuleListInSitemap', 'after'))
		{
			$oModuleController->insertTrigger('menu.getModuleListInSitemap', 'nstore', 'model', 'triggerModuleListInSitemap', 'after');
		}
	}

	/**
	 * @brief 모듈 설치 실행
	 **/
	function moduleInstall()
	{
		$this->installTriggers();
	}

	/**
	 * @brief 설치가 이상없는지 체크
	 **/
	function checkUpdate()
	{
		$oModuleModel = getModel('module');
		$oNproductModel = getModel('nproduct');

		$oDB = &DB::getInstance();

		if(!$oModuleModel->getTrigger('epay.escrowDelivery', 'nstore', 'controller', 'triggerEscrowDelivery', 'after'))
		{
			return true;
		}
		if(!$oModuleModel->getTrigger('epay.escrowConfirm', 'nstore', 'controller', 'triggerEscrowConfirm', 'after'))
		{
			return true;
		}
		if(!$oModuleModel->getTrigger('epay.escrowDenyConfirm', 'nstore', 'controller', 'triggerEscrowDenyConfirm', 'after'))
		{
			return true;
		}
		if(!$oModuleModel->getTrigger('nproduct.getProcModules', 'nstore', 'model', 'triggerGetProcModules', 'before'))
		{
			return true;
		}
		if(!$oModuleModel->getTrigger('member.getMemberMenu', 'nstore', 'model', 'triggerMemberMenu', 'before'))
		{
			return true;
		}
		// 2013. 09. 25 when add new menu in sitemap, custom menu add
		if(!$oModuleModel->getTrigger('menu.getModuleListInSitemap', 'nstore', 'model', 'triggerModuleListInSitemap', 'after'))
		{
			return true;
		}
		if(!$oModuleModel->getTrigger('cympusadmin.getManagerMenu', 'nstore', 'model', 'triggerGetManagerMenu', 'before'))
		{
			return true;
		}

		// extra_vars field added - 2012/11/27
		if(!$oDB->isColumnExists('nstore_order', 'extra_vars'))
		{
			return true;
		}

		return FALSE;
	}

	/**
	 * @brief 업데이트(업그레이드)
	 **/
	function moduleUpdate()
	{
		$oDB = &DB::getInstance();
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');

		$this->installTriggers();

		// extra_vars field added - 2012/11/27
		if(!$oDB->isColumnExists('nstore_order', 'extra_vars'))
		{
			$oDB->addColumn('nstore_order', 'extra_vars', 'text');
		}

		if(!$oModuleModel->getTrigger('cympusadmin.getManagerMenu', 'nstore', 'model', 'triggerGetManagerMenu', 'before'))
		{
			$oModuleController->insertTrigger('cympusadmin.getManagerMenu', 'nstore', 'model', 'triggerGetManagerMenu', 'before');
		}

		return new Object(0, 'success_updated');
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

		if($trans_flag)
		{
			return $this->order_status;
		}
		foreach($this->order_status as $key => $val)
		{
			if(Context::getLang($val))
			{
				$this->order_status[$key] = Context::getLang($val);
			}
		}
		$trans_flag = TRUE;
		return $this->order_status;
	}


	function getPaymentMethods()
	{
		static $trans_flag = FALSE;

		if($trans_flag)
		{
			return $this->payment_method;
		}
		foreach($this->payment_method as $key => $val)
		{
			if(Context::getLang($val))
			{
				$this->payment_method[$key] = Context::getLang($val);
			}
		}
		$trans_flag = TRUE;
		return $this->payment_method;
	}


	function getNstoreModules()
	{
		$oModuleModel = getModel('module');
		$oNstoreAdminModel = getAdminModel('nstore');
		$oNproductModel = getModel('nproduct');

		$args = new stdClass();
		$args->module = 'nproduct';
		$output = $oNstoreAdminModel->getModuleMidList($args); // module_list get

		$modules = array();

		if($output->data)
		{
			foreach($output->data as $k => $v)
			{
				$extra_output = $oModuleModel->getModuleExtraVars($v->module_srl);

				if($extra_output[$v->module_srl]->proc_module == 'nstore') // proc_module == 'nstore' get
				{
					$modules[] = $v;
				}
			}
		}
		return $modules;
	}
}

/* End of file nstore.class.php */
/* Location: ./modules/nstore/nstore.class.php */
