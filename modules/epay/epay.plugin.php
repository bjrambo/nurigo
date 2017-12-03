<?php

/**
 * @class EpayPlugin
 * @author NURIGO(contact@nurigo.net)
 * @brief plugin abstract class
 **/
class EpayPlugin
{
	function EpayPlugin()
	{
	}

	function getFormData()
	{
	}

	function processPayment()
	{
	}

	function processReview()
	{
	}

	function processReport()
	{
	}

	function getReceipt($pg_tid, $paymethod = NULL)
	{
		return '발행불가';
	}

	function getReport()
	{
	}

	function dispExtra1(&$epayObj)
	{
	}

	function dispExtra2(&$epayObj)
	{
	}

	function dispExtra3(&$epayObj)
	{
	}

	function dispExtra4(&$epayObj)
	{
	}

	function procExtra1()
	{
	}

	function procExtra2()
	{
	}

	function procExtra3()
	{
	}

	function procExtra4()
	{
	}

	function dispEscrowDelivery()
	{
		return "<script>alert('에스크로를 지원하지 않는 결제건 입니다.');window.close();</script>";
	}

	function dispEscrowConfirm()
	{
		return "<script>alert('에스크로를 지원하지 않는 결제건 입니다.');window.close();</script>";
	}

	function procEscrowDelivery()
	{
	}

	function procEscrowConfirm()
	{
	}

	/**
	 * Create new Object for php7.2
	 * @param int $code
	 * @param string $msg
	 * @return BaseObject|Object
	 */
	public function makeObject($code = 0, $msg = 'success')
	{
		return class_exists('BaseObject') ? new BaseObject($code, $msg) : new Object($code, $msg);
	}
}
/* End of file epay.plugin.php */
/* Location: ./modules/epay/epay.plugin.php */
