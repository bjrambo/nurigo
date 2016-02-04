<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nstore_digital_contentsController
 * @author NURIGO(contact@nurigo.net)
 * @brief  nstore_digital_contentsController
 */
class nstore_digital_contentsController extends nstore_digital_contents
{
	function insertPeriod($order_srl = null)
	{
		$oModuleModel = &getModel('module');
		$nstore_digital_contents_config = $oModuleModel->getModuleConfig('nstore_digital_contents');

		if(!$nstore_digital_contents_config->period) return new Object(-1, '설정된 만기일이 없습니다.');

		$args->order_srl = $order_srl;
		$args->period = date("Ymd", mktime(0, 0, 0, date("m"), date("d")+$nstore_digital_contents_config->period, date("Y")));

		//$output = executeQuery('nstore_digital_contents.insertPeriod', $args):
		if(!$output->toBool()) return $output;
	}

	function checkPeriod($cart_srl = null)
	{
		if(!$cart_srl) return new Object(-1, 'no cart_srl');

		$pass = 'Y';

		$args->cart_srl = $cart_srl;
		$output = executeQuery('nstore_digital_contents.getPeriod', $args);
		if(!$output->toBool()) return $output;

		// period 가 없을 경우.
		if(!$output->data)
		{
			$this->setPeriod($cart_srl);
			return $pass;
		}
		else
		{
			$current_date = date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
			$period = $output->data->period;

			if($period < $current_date)
			{
				$pass = 'N';
				return $pass;
			}
		}
		return $pass;
	}
	
	function setPeriod($cart_srl = null, $set_date = null)
	{
		if(!$cart_srl) return new Object(-1, 'no cart_srl');

		$args->cart_srl = $cart_srl;
		$output = executeQuery('nstore_digital_contents.getPeriod', $args);
		if(!$output->toBool()) return $output;

		$is_set_period = null;
		if($output->data) $is_set_period = $output->data;

		$oNcartModel = &getModel('ncart');
		$cart_item = $oNcartModel->getCartItem($cart_srl);

		if($cart_item)
		{
			$args->item_srl = $cart_item->item_srl;
			$output = executeQuery('nstore_digital_contents.getConfig', $args);
			if(!$output->toBool()) return $output;

			if(!$output->data) return;

			$period = $output->data->period;
			$period_type = $output->data->period_type;

			$d = 0;
			$m = 0;
			$y = 0;

			switch($period_type)
			{
				case 'd' : $d = $period; break;
				case 'm' : $m = $period; break;
				case 'y' : $y = $period; break;
			}

			$end_date = date("Ymd", mktime(0, 0, 0, date("m")+$m, date("d")+$d, date("Y")+$y));
			if($set_date) $end_date = $set_date;
		}

		if(!$is_set_period)
		{
			$args->period = $end_date;
			$output = executeQuery('nstore_digital_contents.insertPeriod', $args);
			if(!$output->toBool()) return $output;
		}
		else
		{
			$args->period = $end_date;
			$output = executeQuery('nstore_digital_contents.updatePeriod', $args);
			if(!$output->toBool()) return $output;
		}
	}

	function deletePeriod($cart_srl = null)
	{
		if(!$cart_srl) return new Object(-1, 'no cart_srl');
		$args->cart_srl = $cart_srl;
		$output = executeQuery('nstore_digital_contents.deletePeriod', $args);
		if(!$output->toBool()) return $output;
	}

}
/* End of file nstore_digital_contents.controller.php */
/* Location: ./modules/nstore_digital_contents/nstore_digital_contents.controller.php */
