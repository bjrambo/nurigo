<?php

/**
 * @class  paynotyController
 * @author contact@nurigo.net
 * @brief  paynotyController
 */
class paynotyController extends paynoty
{
	function init()
	{

	}

	function triggerCompletePayment(&$obj)
	{
		$oPaynotyModel = getModel('paynoty');
		$oTextmessageController = getController('textmessage');
		$config = $oPaynotyModel->getConfig();
		debugPrint($config);
/*
		$order_info = getModel('ncart')->getOrderInfo($obj->order_srl);
		$extra_vars = unserialize($order_info->extra_vars);
		$product_name = $order_info->title;

		$args = new stdClass();
		$args->template_code = 'C001';
		$args->nick_name = $obj->vact_name;
		$args->product_name = $product_name;
		$args->content = $oPaynotyModel->getNotifyMessage($args);
		if($args->content == 'ERROR001')
		{
			return new Object();
		}
		$args->recipient_no = $extra_vars->tel1[0].$extra_vars->tel1[1].$extra_vars->tel1[2];
		$args->sender_no = $config->sender_no;
		$output = $oTextmessageController->sendmessage($args);
		if(!$output->toBool())
		{
			return $output;
		}
*/
	}
}
