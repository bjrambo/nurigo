<?php

/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nmileageController
 * @author NURIGO(contact@nurigo.net)
 * @brief  nmileageController
 */
class nmileageController extends nmileage
{

	function giveMileage($member_srl, $item_srl, $review_srl, $amount)
	{
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$args->item_srl = $item_srl;
		$item_list = $this->executeQuery('getNonReviewedPurchasedItems', $args);
		if($item_list->toBool() && count($item_list->data))
		{
			$item = $item_list->data[0];
			$args->cart_srl = $item->cart_srl;
			$args->review_srl = $review_srl;
			$output = $this->executeQuery('updateReviewSrl', $args);
			if(!$output->toBool())
			{
				return $output;
			}

			$title = '상품평 등록';
			$this->plusMileage($member_srl, $amount, $title, $item->order_srl);
		}
	}

	function insertMileage($member_srl, $amount)
	{
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$args->mileage = $amount;
		return executeQuery('nmileage.insertMileage', $args);
	}

	/*
		$args->member_srl = $member_srl;
		$args->amount = $amount;
		$args->action = $action; // 1: plus, 2: minus
		$args->title = $title;
		$args->balance = $balance;
	*/
	function insertMileageHistory($args, $order_srl = 0)
	{
		$args->history_srl = getNextSequence();
		$args->order_srl = $order_srl;
		return executeQuery('nmileage.insertMileageHistory', $args);
	}

	function plusMileage($member_srl, $amount, $title, $order_srl = 0)
	{
		$oNmileageModel = getModel('nmileage');
		$config = $oNmileageModel->getModuleConfig();
		switch($config->mileage_method)
		{
			case 'nmileage':
				$output = $oNmileageModel->getMileageInfo($member_srl);
				if($output->getError() == -2)
				{
					$output = $this->insertMileage($member_srl, 0);
					$output->mileage = 0;
				}
				if(!$output->toBool())
				{
					return $output;
				}

				$current_mileage = $output->mileage;
				$balance = $current_mileage + $amount;

				$args->member_srl = $member_srl;
				$args->mileage = $balance;
				$output = executeQuery('nmileage.updateMileage', $args);
				if(!$output->toBool())
				{
					return $output;
				}
				unset($args);
				break;

			case 'point':
				$oPointModel = getModel('point');
				$oPointController = getController('point');
				$point = $oPointModel->getPoint($member_srl, TRUE);
				$oPointController->setPoint($member_srl, $amount, 'add');
				$balance = $point + $amount;
				break;
		}

		$args = new stdClass();
		$args->member_srl = $member_srl;
		$args->amount = $amount;
		$args->action = '1';
		$args->title = $title;
		$args->balance = $balance;
		$this->insertMileageHistory($args, $order_srl);

		return $output;
	}

	function minusMileage($member_srl, $amount, $title, $order_srl = 0)
	{
		$oNmileageModel = getModel('nmileage');
		$config = $oNmileageModel->getModuleConfig();
		switch($config->mileage_method)
		{
			case 'nmileage':
				$output = $oNmileageModel->getMileageInfo($member_srl);
				if($output->getError() == -2)
				{
					$output = $this->insertMileage($member_srl, 0);
					$output->mileage = 0;
				}
				if(!$output->toBool())
				{
					return $output;
				}

				$current_mileage = $output->mileage;
				$balance = $current_mileage - $amount;

				$args = new stdClass();
				$args->member_srl = $member_srl;
				$args->mileage = $balance;
				$output = executeQuery('nmileage.updateMileage', $args);
				if(!$output->toBool())
				{
					return $output;
				}
				unset($args);
				break;

			case 'point':
				$oPointModel = getModel('point');
				$oPointController = getController('point');
				$point = $oPointModel->getPoint($member_srl, TRUE);
				$oPointController->setPoint($member_srl, $amount, 'minus');
				$balance = $point - $amount;
				break;
		}
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$args->amount = $amount;
		$args->action = '2';
		$args->title = $title;
		$args->balance = $balance;
		$this->insertMileageHistory($args, $order_srl);

		return $this->makeObject();
	}

	function triggerMemberInsertAfter($obj)
	{
		$oNmileageModel = getModel('nmileage');
		$config = $oNmileageModel->getModuleConfig();

		if(!$config->variable_name)
		{
			return $this->makeObject();
		}

		$extra_obj = unserialize($obj->extra_vars);
		$vote_id = $extra_obj->{$config->variable_name};

		if(!$vote_id)
		{
			return $this->makeObject();
		}

		$member_srl = getModel('member')->getMemberSrlByUserID($vote_id);

		if(!$member_srl)
		{
			$member_srl = getModel('member')->getMemberSrlByEmailAddress($vote_id);
			if(!$member_srl)
			{
				return $this->makeObject();
			}
		}

		if($member_srl)
		{
			if($obj->member_srl === $member_srl && !in_array($config->member_mothod, array('all', 'voteuser')))
			{
				return $this->makeObject();
			}
		}

		$point = $config->vote_point;
		$vote_text = $config->vote_text;

		// 모두에게
		if($config->member_mothod == 'all')
		{
			if(!$vote_text)
			{
				$output = $this->plusMileage($member_srl, $point, 'vote member');
				$output = $this->plusMileage($obj->member_srl, $point, 'vote member');
			}
			else
			{
				$output = $this->plusMileage($member_srl, $point, $vote_text);
				$output = $this->plusMileage($obj->member_srl, $point, $vote_text);
			}
		}
		//추천 한 사람
		else if($config->member_mothod == 'voteuser')
		{
			if(!$vote_text)
			{
				$output = $this->plusMileage($obj->member_srl, $point, 'vote member');
			}
			else
			{
				$output = $this->plusMileage($obj->member_srl, $point, $vote_text);
			}
		}
		//추천 받은 사람만
		else
		{
			if(!$vote_text)
			{
				$output = $this->plusMileage($member_srl, $point, 'vote member');
			}
			else
			{
				$output = $this->plusMileage($member_srl, $point, $vote_text);
			}
		}
		return $this->makeObject();
	}
}
/* End of file nmileage.controller.php */
/* Location: ./modules/nmileage/nmileage.controller.php */
