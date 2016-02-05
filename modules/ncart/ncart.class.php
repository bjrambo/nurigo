<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  ncart
 * @author NURIGO(contact@nurigo.net)
 * @brief  ncart
 */

define('WAIT_FOR_DEPOSIT', '1');
define('PREPARE_DELIVERY', '2');

require_once(_XE_PATH_.'modules/nproduct/nproduct.item.php');
class ncart extends ModuleObject 
{
		var $order_status = array('0'=>'cart_keep', '1'=>'wait_deposit', '2'=>'deposit_done', '3'=>'prepare_delivery', '4'=>'on_delivery', '5'=>'delivery_done', '6'=>'transaction_done', 'A'=>'cancelled','B'=>'returns','C'=>'exchanges','D'=>'refund');

		var $delivery_companies = array(
			'00'=>'직배송'
			,'16'=>'경동택배'
			,'18'=>'대신택배'
			,'20'=>'대한통운'
			,'22'=>'동부익스프레스'
			,'24'=>'로젠택배'
			,'26'=>'우체국택배'
			,'28'=>'이노지스택배'
			,'30'=>'일양로지스택배'
			,'32'=>'한덱스'
			,'34'=>'한의사랑택배'
			,'36'=>'한진택배'
			,'38'=>'현대택배'
			,'40'=>'호남택배'
			,'42'=>'CJ GLS'
			,'44'=>'CVSnet 편의점택배'
			,'46'=>'DHL'
			,'48'=>'EMS'
			,'50'=>'FedEx'
			,'52'=>'GTX'
			,'54'=>'KG옐로우캡택배'
			,'56'=>'TNT Express'
			,'58'=>'UPS'
		);
		var $delivery_inquiry_urls = array(
			'16'=>'http://www.kdexp.com/sub4_1.asp?stype=1&p_item='
			,'18'=>'http://home.daesinlogistics.co.kr/daesin/jsp/d_freight_chase/d_general_process2.jsp?billno1='
			,'20'=>'https://www.doortodoor.co.kr/parcel/doortodoor.do?fsp_action=PARC_ACT_002&fsp_cmd=retrieveInvNoACT&invc_no='
			,'22'=>'http://www.dongbuexpress.co.kr/Html/Delivery/DeliveryCheckView.jsp?item_no='
			,'24'=>'http://www.ilogen.com/iLOGEN.Web.New/TRACE/TraceNoView.aspx?gubun=slipno&slipno='
			,'26'=>'http://service.epost.go.kr/trace.RetrieveRegiPrclDeliv.postal?sid1='
			,'28'=>'http://www.innogis.net/trace02.asp?invoice='
			,'30'=>'http://www.ilyanglogis.com/functionality/tracking_result.asp?hawb_no='
			,'32'=>'http://btob.sedex.co.kr/work/app/tm/tmtr01/tmtr01_s4.jsp?IC_INV_NO='
			,'34'=>'http://www.hanips.com/html/sub03_03_1.html?logicnum='
			,'36'=>'http://www.hanjin.co.kr/Delivery_html/inquiry/result_waybill.jsp?wbl_num='
			,'38'=>'http://www.hlc.co.kr/personalService/tracking/06/tracking_goods_result.jsp?InvNo='
			,'40'=>'http://honam.enfrom.com/YYSearch/YYSearch.jsp?&Slip01='
			,'42'=>'http://nexs.cjgls.com/web/service02_01.jsp?slipno='
			,'44'=>'http://was.cvsnet.co.kr/_ver2/board/ctod_status.jsp?invoice_no='
			,'46'=>'http://www.dhl.co.kr/ko/express/tracking.shtml?pageToInclude=RESULTS&type=fasttrack&AWB='
			,'48'=>'http://service.epost.go.kr/trace.RetrieveEmsTrace.postal?ems_gubun=E&POST_CODE='
			,'50'=>'http://www.fedex.com/Tracking?ascend_header=1&clienttype=dotcomreg&cntry_code=kr&language=korean&tracknumbers='
			,'52'=>'http://www.gtx2010.co.kr/del_inquiry_result.html?s_gbn=1&awblno='
			,'54'=>'http://www.yellowcap.co.kr/custom/inquiry_result.asp?invoice_no='
			,'56'=>'http://www.tnt.com/webtracker/tracking.do?respCountry=kr&respLang=ko&searchType=CON&cons='
			,'58'=>'http://www.ups.com/WebTracking/track?loc=ko_KR&InquiryNumber1='
		);

		var $payment_method = array(
			'CC'=>'credit_card'
			,'BT'=>'bank_transfer'
			,'IB'=>'internet_banking'
			,'VA'=>'virtual_account'
			,'MP'=>'mobile_phone'
			,'MI'=>'mileage'
		);

		var $soldout_process = array(
			'P' => '포인트로 환불'
			,'C' => '현금으로 환불'
			,'H' => '전화요망'
			,'R' => '대체상품으로 배송'
		);

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


		/**
		 * @brief Object를 텍스트의 %...% 와 치환.
		 **/
		function mergeKeywords($text, &$obj)
		{
			if (!is_object($obj)) return $text;
			foreach ($obj as $key => $val) {
				if (is_array($val)) $val = join($val);
				if (is_string($key) && is_string($val)) {
					if (substr($key,0,10)=='extra_vars') $val = str_replace('|@|', '-', $val);
					$text = preg_replace("/%" . preg_quote($key) . "%/", $val, $text);
				}
			}
			return $text;
		}

		function installTriggers()
		{
            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');
			if (!$oModuleModel->getTrigger('epay.processPayment', 'ncart', 'controller', 'triggerProcessPayment', 'after')) {
				$oModuleController->insertTrigger('epay.processPayment', 'ncart', 'controller', 'triggerProcessPayment', 'after');
			}
			if (!$oModuleModel->getTrigger('epay.processReview', 'ncart', 'controller', 'triggerProcessReview', 'before')) {
				$oModuleController->insertTrigger('epay.processReview', 'ncart', 'controller', 'triggerProcessReview', 'before');
			}

			// 2013. 09. 25 when add new menu in sitemap, custom menu add
			if(!$oModuleModel->getTrigger('menu.getModuleListInSitemap', 'ncart', 'model', 'triggerModuleListInSitemap', 'after'))
				$oModuleController->insertTrigger('menu.getModuleListInSitemap', 'ncart', 'model', 'triggerModuleListInSitemap', 'after');

			// added on 2014-06-13
			if (!$oModuleModel->getTrigger('epay.processReview', 'ncart', 'view', 'triggerReviewForm', 'after')) {
				$oModuleController->insertTrigger('epay.processReview', 'ncart', 'view', 'triggerReviewForm', 'after');
			}

			// added on 2014-09-22
			if (!$oModuleModel->getTrigger('epay.getTransactionList', 'ncart', 'model', 'triggerTransactionList', 'after')) {
				$oModuleController->insertTrigger('epay.getTransactionList', 'ncart', 'model', 'triggerTransactionList', 'after');
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
			$oDB = &DB::getInstance();
            $oModuleModel = &getModel('module');
			if(!$oModuleModel->getTrigger('epay.processPayment', 'ncart', 'controller', 'triggerProcessPayment', 'after')) return TRUE;
			if(!$oModuleModel->getTrigger('epay.processReview', 'ncart', 'controller', 'triggerProcessReview', 'before')) return TRUE;
			// 2013. 09. 25 when add new menu in sitemap, custom menu add
			if(!$oModuleModel->getTrigger('menu.getModuleListInSitemap', 'ncart', 'model', 'triggerModuleListInSitemap', 'after')) return true;
			// added on 2014-06-13
			if(!$oModuleModel->getTrigger('epay.processReview', 'ncart', 'view', 'triggerReviewForm', 'after')) return TRUE;
			// added on 2014-09-22
			if(!$oModuleModel->getTrigger('epay.getTransactionList', 'ncart', 'model', 'triggerTransactionList', 'after')) return TRUE;
            if(!$oDB->isColumnExists('ncart', 'document_srl')) return TRUE;
            if(!$oDB->isColumnExists('ncart', 'file_srl')) return TRUE;
            if(!$oDB->isColumnExists('ncart', 'item_code')) return TRUE; 
            if(!$oDB->isColumnExists('ncart_orderform_fieldsets', 'proc_modules')) return TRUE; 

			return FALSE;
        }

        /**
         * @brief 업데이트(업그레이드)
         **/
        function moduleUpdate()
        {
			$oDB = &DB::getInstance();
			$this->installTriggers();
  
            if(!$oDB->isColumnExists('ncart', 'document_srl'))
            {
                $oDB->addColumn('ncart', 'document_srl', 'number', 11, 0, TRUE);
            }
            if(!$oDB->isColumnExists('ncart', 'file_srl'))
            {
                $oDB->addColumn('ncart', 'file_srl', 'number', 11, 0, TRUE);
            }
            if(!$oDB->isColumnExists('ncart', 'item_code'))
            {
                $oDB->addColumn('ncart', 'item_code', 'varchar', 250);
            }
            if(!$oDB->isColumnExists('ncart_orderform_fieldsets', 'proc_modules'))
			{
                $oDB->addColumn('ncart_orderform_fieldsets', 'proc_modules', 'varchar', 250);
			}
        }

        /**
         * @brief 캐시파일 재생성
         **/
        function recompileCache()
        {
        }
}

/* End of file ncart.class.php */
/* Location: ./modules/ncart/ncart.class.php */
