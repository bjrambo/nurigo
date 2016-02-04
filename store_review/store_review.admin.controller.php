<?php
/**
 * store_reviewAdminController class
 * admin controller class of the store_review module
 * @author NURIGO(contact@nurigo.net)
 * @package /modules/store_review
 */
class store_reviewAdminController extends store_review
{
	/**
	 * Initialization
	 * @return void
	 */
	function init()
	{
	}

	function deleteReviewList($item_srl)
	{
		$args->item_srl = $item_srl;
		$output = executeQuery('store_review.deleteReviewsByItemSrl', $args);	
		if(!$output->toBool()) return $output;

		$args->item_srl = $item_srl;
		$output = executeQuery('store_review.deleteReviewListByItemSrl', $args);	
		if(!$output->toBool()) return $output;

		return new Object();
	}
}
/* End of file store_review.admin.controller.php */
/* Location: ./modules/store_review/store_review.admin.controller.php */
