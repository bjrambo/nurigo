<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nstore_digitalView
 * @author NURUIGO(contact@nurigo.net)
 * @brief  nstore_digitalView
 */
if (Context::get('act')=='dispNstore_digitalCertificate')
{
	require_once('tcpdf/config/lang/kor.php');
	require_once('tcpdf/tcpdf.php');

	class MYPDF extends TCPDF 
	{
		//Page header
		public function Header() 
		{
			// full background image
			// store current auto-page-break status
			$bMargin = $this->getBreakMargin();
			$auto_page_break = $this->AutoPageBreak;
			$this->SetAutoPageBreak(false, 0);
			$img_file = _XE_PATH_.'modules/nstore_digital/tpl/img/certificate.jpg';
			$this->Image($img_file, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
			// restore auto-page-break status
			$this->SetAutoPageBreak($auto_page_break, $bMargin);
		}
	}
}

class nstore_digitalView extends nstore_digital
{
	function init()
	{
		// 템플릿 경로 설정
		if($this->module_info->module != 'nstore_digital') $this->module_info->skin = 'default';
		if(!$this->module_info->skin) $this->module_info->skin = 'default';
		if(!$this->module_info->display_caution) $this->module_info->display_caution = 'Y';
		$this->setTemplatePath($this->module_path."skins/{$this->module_info->skin}");
		Context::set('module_info',$this->module_info);

		$oLicenseModel = &getModel('license');
		if(!$oLicenseModel || ($oLicenseModel && !$oLicenseModel->getLicenseConfirm('nstore_digital')))
		{
			Context::addHtmlHeader("<script>jQuery(document).ready(function() { jQuery('<div style=\"background:#fff; padding:6px; position:fixed; right:6px; bottom:6px; z-index:999999; \">Powered by <a href=\"http://www.xeshoppingmall.com\">NURIGO</a></div>').appendTo('body'); });</script>");
		}
	}

	function dispNstore_digitalIndex() 
	{
		$oFileModel = &getModel('file');
		$oNstore_digital_contentsModel = &getModel('nstore_digital_contents');
		$oNstore_digitalModel = &getModel('nstore_digital');
		$oNdcModel = &getModel('nstore_digital_contents');

		$logged_info = Context::get('logged_info');
		if(!$logged_info) return new Object(-1, 'msg_login_required');
		$config = $oNstore_digitalModel->getModuleConfig();
		Context::set('config',$config);

		$args->member_srl = $logged_info->member_srl;
		$args->module = 'nstore_digital';
		$output = executeQueryArray('nstore_digital.getPurchasedItems', $args);
		$item_list = $output->data;
		$order_list = array();
		if ($item_list) {
			foreach ($item_list as $key=>$val) {
				$item = new nproductItem($val, $config->currency, $config->as_sign, $config->decimals);
				if ($item->option_srl)
				{
					$item->price += ($item->option_price);
				}

				// get content_file

				if($val->file_srl != '0') 
				{
					$file = $oFileModel->getFile($val->file_srl);
					if($file) $item->download_file = $file;
				}

				$content_list = $oNstore_digital_contentsModel->getContents($val->item_srl);
				if($content_list) $item->content_list = $content_list;

				// end 

				$item_list[$key] = $item;

				if (!isset($order_list[$val->order_srl])) $order_list[$val->order_srl] = array();

				$order_list[$val->order_srl][] = $item;

				$vars->cart_srl = $val->cart_srl;
				$period = executeQuery('nstore_digital.getCartItem', $vars);

				if($period->data->period)
				{
					$item->period = $period->data->period;

					$current_date = date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y")));

					// 만기일이 지났으면
					if($period->data->period < $current_date) $item->exceed_date = 'Y';

					// 아이템에 만기일 설정이 되있으면  
					$period_config = $oNdcModel->getItemConfig($val->item_srl);
					if($period_config->period) $item->period_config = 'Y';
				}
			}
		}

		Context::set('list', $item_list);
		Context::set('order_list', $order_list);
		Context::set('order_status', $this->getOrderStatus());
		Context::set('delivery_inquiry_urls', $this->delivery_inquiry_urls);

		$this->setTemplateFile('index');
	}

	function dispNstore_digitalFrontPage() 
	{
		$oNstore_digitalModel = &getModel('nstore_digital');

		$this->getCategoryTree($this->module_info->module_srl);

		$display_categories = $oNstore_digitalModel->getFrontDisplayItems();

		Context::set('display_categories', $display_categories);

		$this->setTemplateFile('frontpage');
	}

	function dispNstore_digitalDetail() 
	{
		$oFileModel = &getModel('file');
		$oEpayModel = &getModel('epay');
		$oNstore_digitalModel = &getModel('nstore_digital');

		$config = $oNstore_digitalModel->getModuleConfig();

		$logged_info = Context::get('logged_info');

		$order_srl = Context::get('order_srl');
		$order_info = $oNstore_digitalModel->getOrderInfo($order_srl);

		Context::set('order_info', $order_info);
		Context::set('order_status', $this->getOrderStatus());

		$payment_info = $oEpayModel->getTransactionByOrderSrl($order_srl);
		Context::set('payment_info',$payment_info);
		Context::set('payment_method',$this->getPaymentMethods());

		$this->setTemplateFile('detail');
	}

	function dispNstore_digitalCertificate() 
	{
		$oNstore_digitalModel = &getModel('nstore_digital');
		$logged_info = Context::get('logged_info');
		$cart_srl = Context::get('cart_srl');
		if(!$logged_info) return new Object(-1, 'msg_login_required');

		$config = $oNstore_digitalModel->getModuleConfig();
		$item_info = $oNstore_digitalModel->getPurchasedItem($logged_info->member_srl, $cart_srl);
		Context::set('item_info', $item_info);
		if (!in_array($item_info->order_status, array('3'))) 
		{
			return new Object(-1,'구매완료된 상품이 아닙니다.');
		}

		// create new PDF document
		$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

		// set document information
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('Nicola Asuni');
		$pdf->SetTitle('TCPDF Example 006');
		$pdf->SetSubject('TCPDF Tutorial');
		$pdf->SetKeywords('TCPDF, PDF, example, test, guide');
		/*
		// set default header data
		$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 006', PDF_HEADER_STRING);
		 */

		// set header and footer fonts
		$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		//$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

		// remove default header/footer
		//$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);

		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

		//set margins
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
		//set auto page breaks
		//$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		//set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

		//set some language-dependent strings
		$pdf->setLanguageArray($l);

		// ---------------------------------------------------------

		// set font
		//$pdf->SetFont('dejavusans', '', 10);
		$pdf->SetFont('cid0kr', '', 16);
		// table line height
		//$pdf->setCellHeightRatio(1.9);
		$pdf->setCellHeightRatio(1.7);
		// add a page
		$pdf->AddPage();
		// output the HTML content
		$this->setTemplatePath($this->module_path."tpl");
		$oTemplate = &TemplateHandler::getInstance();
		$output = $oTemplate->compile($this->module_path.'tpl','certificate');
		$pdf->writeHTML($output, true, false, true, false, '');
		// reset pointer to the last page
		$pdf->lastPage();
		//Close and output PDF document
		$pdf->Output('certificate.pdf', 'I');

		exit();
	}

	function dispNstore_digitalPeriodPayment() 
	{
		$oNstore_digitalModel = &getModel('nstore_digital');
		$oNdc_model = &getModel('nstore_digital_contents');
		$oNproduct_model = &getModel('nproduct');

		$vars = Context::getRequestVars();
		$module_info = Context::get('module_info');

		$logged_info = Context::get('logged_info');
		$item_config = $oNdc_model->getItemConfig($vars->item_srl);
		$item_info = $oNproduct_model->getItemInfo($item_config->item_srl);

		$price = $item_info->price;
		$extra_vars = unserialize($item_config->extra_vars);
		if(isset($extra_vars['period_price'])) $price = $extra_vars['period_price'];

		//$cart_info = $oNdc_model->getPeriod(Context::get('cart_srl'));

		$vars->cart_srl = Context::get('cart_srl');
		$output = executeQuery('nstore_digital.getCartItem', $vars);
		$cart_info = $output->data;
		$current_date = date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y")));

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

		if($cart_info->period > $current_date)
		{
			Context::set('before_period', $cart_info->period);

			$dead_line = $cart_info->period;
			$year = substr($dead_line, 0, 4);
			$month = substr($dead_line, 4, 2);
			$day = substr($dead_line, 6, 2);
			$period = date("Ymd", mktime(0, 0, 0, $month+$m, $day+$d, $year+$y));
		}
		else
		{
			$period = date("Ymd", mktime(0, 0, 0, date("m")+$m, date("d")+$d, date("Y")+$y));
		}

		// pass payment amount, item name, etc.. to epay module.

		$args->module_srl = $module_info->module_srl;
		$args->epay_module_srl = $module_info->epay_module_srl;
		$args->price = (int)$price;
		$args->item_name = $item_info->item_name;
		$args->purchaser_name = $logged_info->user_name;
		$args->purchaser_email = $logged_info->email_address;
		$args->purchaser_telnum = '010-0000-0000';
		$args->join_form = 'fo_period';

		$oEpayView = &getView('epay');
		$output = $oEpayView->getPaymentForm($args);

		if (!$output->toBool()) return $output;
		$epay_form = $output->data;

		Context::set('cart_srl', Context::get('cart_srl'));
		Context::set('epay_form', $epay_form);
		Context::set('item_info', $item_info);
		Context::set('price', $price);
		Context::set('period', $period);

		$this->setTemplateFile('period_payment');
	}
	
	function dispNstore_digitalOrderComplete()
	{
		$oNstore_digitalModel = &getModel('nstore_digital');
		$oEpayModel = &getModel('epay');
		$logged_info = Context::get('logged_info');

		$order_srl = Context::get('order_srl');
		if (!$order_srl) return new Object(-1, 'msg_invalid_request');

		$payment_info = $oEpayModel->getTransactionByOrderSrl($order_srl);
		Context::set('payment_info',$payment_info);

		$period_srl = Context::get('period_srl');
		$period_info = $oNstore_digitalModel->getPeriodInfo($period_srl);
		//$item_list = $oNcartModel->getPaidOrderItems($order_srl);
		Context::set('period_info', $period_info);

/*
		// fieldset
		$fieldset_list = $oNcartModel->getFieldSetList($this->module_info->module_srl);
		foreach($fieldset_list as $key=>&$val)
		{
			foreach($val->fields as $key2=>&$field)
			{
				if(isset($extra_vars->{$field->column_name}))
				{
					$field->value = $extra_vars->{$field->column_name};
				}
			}
		}

		Context::set('fieldset_list', $fieldset_list);
*/
		Context::set('order_status', $this->getOrderStatus());

		$this->setTemplateFile('ordercomplete');
	}
}
/* End of file nstore_digital.view.php */
/* Location: ./modules/nstore_digital/nstore_digital.view.php */
