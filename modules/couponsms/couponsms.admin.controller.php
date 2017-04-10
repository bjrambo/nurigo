<?php

class couponsmsAdminController extends couponsms
{
	function init()
	{
	}

	function procCouponsmsAdminCouponInsert()
	{
		$couponsms_srl = Context::get('couponsms_srl');

		$obj = Context::getRequestVars();
		$args = new stdClass();
		$args->title = $obj->title;
		if(is_numeric($obj->term_regdate) != TRUE)
		{
			return new Object(-1, '유효기간은 숫자로 표기해야합니다.');
		}
		$args->term_regdate = $obj->term_regdate;
		$args->phone_number = $obj->phone_number;
		$args->use = $obj->use;
		$args->use_boon = $obj->use_boon;
		if($obj->group_srl)
		{
			$args->group_srl = serialize($obj->group_srl);
		}
		else
		{
			$args->group_srl = NULL;
		}

		if($couponsms_srl)
		{
			$args->couponsms_srl = $couponsms_srl;
			$output = executeQuery('couponsms.updateCoupon', $args);
			if(!$output->toBool())
			{
				return $output;
			}
		}
		else
		{
			$args->couponsms_srl = getNextSequence();
			$output = executeQuery('couponsms.insertCoupon', $args);
			if(!$output->toBool())
			{
				return $output;
			}
		}

		$this->setMessage('success_saved');

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON')))
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispCouponsmsAdminCouponInsert', 'couponsms_srl', $args->couponsms_srl);
			header('location: ' . $returnUrl);
			return;
		}
	}

	function procCouponsmsAdminSetting()
	{
		$oModuleController = getController('module');
		$obj = Context::getRequestVars();

		$config = new stdClass();
		$config->layout_srl = $obj->layout_srl;
		$config->skin = $obj->skin;
		$config->sending_method = $obj->sending_method;
		$config->sender_key = $obj->sender_key;
		$config->variable_name = $obj->variable_name;
		$this->setMessage('success_updated');

		$oModuleController->updateModuleConfig('couponsms', $config);
		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON')))
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispCouponsmsAdminSetting');
			header('location: ' . $returnUrl);
			return;
		}
	}

	function procCouponsmsAdminCouponDelete()
	{
		$old_date = Context::get('old_date');

		if($old_date)
		{
			$args = new stdClass;
			$args->old_date = $old_date;
			$output = executeQuery('couponsms.deleteCouponUse', $args);
			if(!$output->toBool())
			{
				return $output;
			}
		}
		else
		{
			return new Object(-1, '한달 이후의 데이터만 삭제할 수 있습니다.');
		}

		if($old_date)
		{
			$this->setMessage('1달 이전의 쿠폰정보를 삭제하였습니다.');
		}

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON')))
		{
			$returnUrl = Context::get('success_return_url') ?  Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispCouponsmsAdminCouponList');
			header('location: ' .$returnUrl);
			return;
		}
	}
}
/* End of file */
