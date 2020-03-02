<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  ncartItem
 * @author NURIGO(contact@nurigo.net)
 * @brief  ncartItem class
 */
class ncartItem extends BaseObject
{
	var $ExtMod = NULL;
	var $mid = NULL;
	var $item_srl = 0;
	var $item_code = NULL;
	var $item_name = NULL;
	var $module_srl = 0;
	var $category_id = 0;
	var $node_route = NULL;
	var $document_srl = 0;
	var $file_srl = 0;
	var $price = 0;
	var $display = NULL;
	var $delivery_info = NULL;
	var $extra_vars;
	var $extra_var_objs = null;
	var $regdate = NULL;
	var $quantity = 0;
	var $order_status = NULL;
	var $invoice_no = NULL;
	var $module_info;
	var $express_id = NULL;
	var $cart_srl = 0;
	var $currency = "KRW";
	var $as_sign = "N";
	var $decimals = 0;
	var $thumb_file_srl = 0;

	function ncartItem($info, $currency="KRW", $as_sign="N", $decimals=0)
	{
		if(is_object($info))
		{
			$this->setAttributes($info);
		}
		if(is_numeric($info))
		{
			$oStoreModel = &getModel('ncart');
			$item_info = $oStoreModel->getItemInfo($info);
			if ($item_info) $this->setAttributes($item_info);
		}
		$this->currency = $currency;
		$this->as_sign = $as_sign;
		$this->decimals = $decimals;

		if($this->module_srl && $this->extra_vars)
		{
			$oNstoreModel = &getModel('ncart');
			$extra_vars = $oNstoreModel->getCombineItemExtras($this);

			if(is_object($extra_vars) || is_array($extra_vars))
			{
				foreach ($extra_vars as $key => $val)
				{
					$this->extra_var_objs->{$val->column_name} = $val;
				}
			}
		}
	}

	function setAttributes($info)
	{
		foreach ($info as $key=>$val) {
			$this->{$key} = $val;
		}
	}

	function getFileSrl()
	{
		$file_srl = $this->thumb_file_srl;
		return $file_srl;
	}

	function getExtraVarTitle($key)
	{
		if(isset($this->extra_var_objs->{$key}->column_title))
		{
			return $this->extra_var_objs->{$key}->column_title;
		}
		return NULL;
	}

	function getExtraVarValue($key)
	{
		$extra_vars = unserialize($this->extra_vars);
		if(isset($extra_vars->{$key}))
		{
			return $extra_vars->{$key};
		}
		return NULL;
	}

	function thumbnailExists($width = 80, $height = 0, $type = '')
	{
		$file_srl = $this->getFileSrl();

		if(!$file_srl) return false;
		if(!$this->getThumbnail($file_srl, $width, $height, $type)) return false;
		return true;
	}

	function getThumbnail($width = 80, $height = 0, $thumbnail_type = 'crop')
	{
		$oFileModel = &getModel('file');

		$file_srl = $this->getFileSrl();

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

	function setCurrency($currency="KRW", $as_sign="N")
	{
		$this->currency  = $currency;
		$this->as_sign = $as_sign;
	}

	function price($price)
	{
		$oNstoreModel = &getModel('ncart');
		$config = $oNstoreModel->getModuleConfig();
		$division = pow(10, $config->decimals);
		if(!$division) return $price;
		return $price / $division;
	}

	function formatMoney($number)
	{
		$oNstoreModel = &getModel('ncart');
		$config = $oNstoreModel->getModuleConfig();
		$division = pow(10, $config->decimals);
		$number = $number / $division;
		return number_format($number, $config->decimals);
	}

	function getPrice($price=null)
	{
		if ($price===NULL)
		{
			$price = $this->price;
		}
		$division = pow(10, $this->decimals);
		return $price / $division;
	}

	function printPrice($price=NULL)
	{
		global $lang;
		$price = $this->getPrice($price);
		
		return getModel('currency')->printPrice($price, Context::get('module_info'));
	}

	function printDiscountedPrice($price=NULL)
	{
		if ($price!==NULL)
		{
			return $this->printPrice($price);
		}
		return $this->printPrice($this->discounted_price) ;
	}
}
/* End of file ncart.item.php */
/* Location: ./ncart/ncart.item.php */
