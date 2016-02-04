<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nstore_digital_contentsAdminController
 * @author hosy(hosy@nurigo.net)
 * @brief  nstore_digital_contentsAdminController
 */
class nstore_digital_contentsAdminController extends nstore_digital_contents
{
	function procNstore_digital_contentsAdminDeleteContent() 
	{
		if(Context::get('file_srl'))
		{
			$oFileController = &getController('file');

			$file_srl = Context::get('file_srl');

			$oFileController->deleteFile($file_srl);

			$args->file_srl = $file_srl;
			$output = executeQuery('nstore_digital_contents.deleteContent', $args);
			if(!$output->toBool()) return $output;
		}
		else return false;
	}

	function procNstore_digital_contentsAdminInsertContent()
	{
		$oFileController = &getController('file');

		$file_srl = Context::get('file_srl');
		$args->item_srl = Context::get('item_srl');
		$args->module_srl = Context::get('module_srl');

		if(!$file_srl)
		{
			$args->contents_file = Context::get('contents_file');
			if($args->contents_file && is_uploaded_file($args->contents_file['tmp_name']))
			{
				$output = $oFileController->insertFile($args->contents_file, $args->module_srl, $args->item_srl);

				if(!$output || !$output->toBool()) return $output;

				$args->file_srl = $output->get('file_srl');
				$output = executeQuery('nstore_digital_contents.insertContent', $args);

				if(!$output->toBool()) return $output;

				$msg_code = 'success_registed';
			}
		}
		else
		{
			$args->contents_file = Context::get('contents_file');
			if($args->contents_file && is_uploaded_file($args->contents_file['tmp_name']))
			{
				$oFileController->deleteFile($file_srl);

				$output = $oFileController->insertFile($args->contents_file, $args->module_srl, $args->item_srl);
				if(!$output || !$output->toBool()) return $output;

				$args->file_srl = $output->get('file_srl');
				$args->old_file_srl = $file_srl;

				$output = executeQuery('nstore_digital_contents.updateContent', $args);

				if(!$output->toBool()) return $output;
			}

			$msg_code = 'success_updated';
		}
		$oFileController->setFilesValid($args->item_srl);

		$this->setRedirectUrl(getNotEncodedUrl('', 'module',Context::get('module'),'act', 'dispNstore_digital_contentsAdminManageContents','module_srl',Context::get('module_srl'),'item_srl',Context::get('item_srl')));

		$this->setMessage($msg_code);
	}

	function procNstore_digital_contentsAdminUpdateContentListOrder() 
	{
		$order = Context::get('order');
		parse_str($order);
		$idx = 1;
		if(is_array($record))
		{
			foreach ($record as $file_srl) {
				$args->file_srl = $file_srl;
				$args->list_order = $idx;
				$output = executeQuery('nstore_digital_contents.updateContentListOrder', $args);
				if(!$output->toBool())
				{
					return $output;
				}
				$idx++;
			}
		} }

/*
	function procNstore_digital_contentsAdminInsertConfig()
	{
		$oModuleController = &getController('module');

		$nstore_digital_contents_config->file_size = Context::get("file_size") + 'M';

		$oModuleController->insertModuleConfig('nstore_digital_contents', $nstore_digital_contents_config);

		$this->setMessage('success');
		$this->setRedirectUrl(getNotEncodedUrl('', 'module',Context::get('module'),'act', 'dispNstore_digital_contentsAdminInsertConfig'));
		
	}	
*/

	function procNstore_digital_contentsAdminInsertConfig()
    {
		if(!Context::get('item_srl')) return new Object(-1, 'no item_srl');

		$args->item_srl = Context::get('item_srl');

		$output = executeQuery('nstore_digital_contents.getConfig', $args);

		if(!$output->toBool()) return $output;

		if($output->data)
		{
			$output = executeQuery('nstore_digital_contents.deleteConfig', $args);
			if(!$output->toBool()) return $output;
		}

		if(Context::get('period_price'))
		{
			$args->extra_vars = serialize(array("period_price"=>Context::get('period_price')));
		}
		$args->period = Context::get('expire_period');
		$args->period_type = Context::get('period_select');

		if($args->period < 0) return new Object(-1, '만기일은 0이하로 넣을 수 없습니다.');

		$output = executeQuery('nstore_digital_contents.insertConfig', $args);
		if(!$output->toBool()) return $output;

		$this->setMessage('success');
		$this->setRedirectUrl(getNotEncodedUrl('', 'module',Context::get('module'),'act', 'dispNstore_digital_contentsAdminManageContents','module_srl',Context::get('module_srl'),'item_srl',Context::get('item_srl')));

	}
}
/* End of file nstore_digital_contents.admin.controller.php */
/* Location: ./modules/nstore_digital_contents/nstore_digital_contents.admin.controller.php */
