<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nstore_digital_contentsModel
 * @author NURIGO(contact@nurigo.net)
 * @brief  nstore_digital_contentsModel
 */
class nstore_digital_contentsModel extends nstore_digital_contents
{
	function getModuleConfig()
	{
		$oModuleModel = &getModel('module');
		$config = $oModuleModel->getModuleConfig('nstore_digital_contents');
		return $config;
	}

	function triggerGetContentList(&$item_info)
	{
        $args->item_srl = $item_info->item_srl;
        $output = executeQueryArray('nstore_digital_contents.getContentList', $args);
        if(!$output->toBool()) return $output;
        Context::set('content_list', $output->data);
	}

	function getThumbnail($file_srl = null, $width = 80, $height = 0, $thumbnail_type = 'crop') 
	{
		$oFileModel = &getModel('file');

		if(!$file_srl) return;
		if(!$height) $height = $width;


		// Define thumbnail information
		$thumbnail_path = sprintf('files/cache/thumbnails/%s',getNumberingPath($file_srl, 3));
		$thumbnail_file = sprintf('%s%dx%d.%s.jpg', $thumbnail_path, $width, $height, $thumbnail_type);
		$thumbnail_url  = Context::getRequestUri().$thumbnail_file;
		// Return false if thumbnail file exists and its size is 0. Otherwise, return its path
		if(file_exists($thumbnail_file)) 
		{
			if(filesize($thumbnail_file)<1)
			{
				return false;
			}
			else return $thumbnail_url;
		}
		// Target File
		$source_file = NULL;
		$file = $oFileModel->getFile($file_srl);
		if($file) 
		{
			$source_file = $file->uploaded_filename;
		}

		if($source_file)
		{
			$output = FileHandler::createImageFile($source_file, $thumbnail_file, $width, $height, 'jpg', $thumbnail_type);
		}
		// Return its path if a thumbnail is successfully genetated
		if($output) return $thumbnail_url;
		// Create an empty file not to re-generate the thumbnail
		else FileHandler::writeFile($thumbnail_file, '','w');

		return;
	}

	function getContents($item_srl)
	{
		if(!$item_srl) return;

		$oFileModel = &getModel('file');

		$args->item_srl = $item_srl;
		$output = executeQueryArray('nstore_digital_contents.getContentList', $args);

		$content_list = $output->data;


		foreach($content_list as $k => $v)
		{
			if($v->file_srl)
			{
				$file = $oFileModel->getFile($v->file_srl);
				if($file) $v->download_file = $file;
			}
		}

		return $content_list;
	}

	function getPeriod($cart_srl)
	{
		$args->cart_srl = $cart_srl;
		$output = executeQuery('nstore_digital_contents.getPeriod', $args);
		if(!$output->toBool()) return 0;

		if(!$output->data) return new Object();
		return $output->data;
	}

	function getItemConfig($item_srl)
	{
		$args->item_srl = $item_srl;
		$output = executeQuery('nstore_digital_contents.getConfig', $args);
		if(!$output->toBool()) return $output;

		if(!$output->data) return new Object();
		return $output->data;
	}

}
/* End of file nstore_digital_contents.model.php */
/* Location: ./modules/nstore_digital_contents/nstore_digital_contents.model.php */
