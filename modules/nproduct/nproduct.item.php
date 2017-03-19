<?php

/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf-8:
 * @class  nproductItem
 * @author NURIGO(contact@nurigo.net)
 * @brief  nproductItem class
 */
class nproductItem extends Object
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
	var $extra_vars = NULL;
	var $extra_var_objs = null;
	var $regdate = NULL;
	var $quantity = 0;
	var $order_status = NULL;
	var $invoice_no = NULL;
	var $express_id = NULL;
	var $cart_srl = 0;
	var $currency = "KRW";
	var $as_sign = "N";
	var $decimals = 0;
	var $thumb_file_srl = 0;
	public static $price_cart = 0;

	/**
	 * @brief constructor
	 *
	 */
	// HACK : This method is restored to its original state.
	// HACK : And to prevent this class from running if there are no items in the shopping cart.
	function nproductItem($info, $currency = "KRW", $as_sign = "N", $decimals = 0)
	{
		if(is_object($info))
		{
			$this->setAttributes($info);
		}
		if(is_numeric($info))
		{
			$oStoreModel = getModel('nproduct');
			$item_info = $oStoreModel->getItemInfo($info);
			if($item_info)
			{
				$this->setAttributes($item_info);
			}
		}
		$this->currency = $currency;
		$this->as_sign = $as_sign;
		$this->decimals = $decimals;

		//if($this->module_srl) 에서 아래와 같이 바꿈
		if($this->module_srl && $this->extra_vars)
		{
			$oNstoreModel = getModel('nproduct');
			$extra_vars = $oNstoreModel->getCombineItemExtras($this);

			if(is_object($extra_vars) || is_array($extra_vars))
			{
				$this->extra_var_objs = new stdClass();
				foreach($extra_vars as $key => $val)
				{
					$this->extra_var_objs->{$val->column_name} = $val;
				}
			}
		}
	}

	/**
	 * @brief print price
	 *
	 */
	function printPrice($price = null)
	{
		$oCurrencyModel = getModel('currency');

		if(!$price && $this->price)
		{
			$price = $this->price;
		}
		return $oCurrencyModel->printPrice($price);
	}

	function printMileage($mileage)
	{
		$oCurrencyModel = getModel('currency');

		return $oCurrencyModel->printPrice($mileage);
	}

	/**
	 * @param null $price
	 * @return mixed
	 * @HACK : Fixed to use this method exclusively for shopping carts.
	 */
	public static function cartPrintPrice($price = null)
	{
		$oCurrencyModel = getModel('currency');

		if(!$price && self::$price_cart)
		{
			$price = self::$price_cart;
		}
		return $oCurrencyModel->printPrice($price);
	}

	/**
	 * @brief set currency config
	 *
	 */
	function setCurrency($currency = "KRW", $as_sign = "N")
	{
		$oCurrencyModel = getModel('currency');
		$oCurrencyModel->setCurrency($currency, $as_sign);
	}

	/**
	 * @brief get price
	 *
	 */
	function price($price)
	{
		$oCurrencyModel = getModel('currency');
		return $oCurrencyModel->price($price);
	}

	/**
	 * @brief print formatted price
	 *
	 */
	function formatMoney($number)
	{
		$oCurrencyModel = getModel('currency');
		return $oCurrencyModel->formatMoney($number);
	}

	/**
	 * @brief get price
	 *
	 */
	function getPrice($price = null)
	{
		$oCurrencyModel = getModel('currency');
		if($price === NULL)
		{
			$price = $this->price;
		}

		return $oCurrencyModel->getPrice($price);
	}

	/**
	 * @brief get discounted price
	 *
	 */
	function getDiscountedPrice()
	{
		if($this->discounted_price)
		{
			return $this->discounted_price;
		}
		return $this->price;
	}

	/**
	 * @brief print discounted price
	 *
	 */
	function printDiscountedPrice($price = null)
	{
		return $this->printPrice($price->discounted_price);
	}

	/**
	 * @brief set attributes
	 *
	 */
	function setAttributes($info)
	{
		foreach($info as $key => $val)
		{
			$this->{$key} = $val;
			if($key == 'price')
			{
				self::$price_cart = $val;
			}
		}
	}

	/**
	 * @brief get item name
	 *
	 */
	function getItemName($cut_size = 0)
	{
		return cut_str($this->item_name, $cut_size, '..');
	}

	/**
	 * @brief
	 *
	 */
	function getFileSrl()
	{
		$file_srl = $this->thumb_file_srl;
		return $file_srl;
	}

	/**
	 * @brief
	 *
	 */
	function getExtraVarTitle($key)
	{
		if(isset($this->extra_var_objs->{$key}->column_title))
		{
			return $this->extra_var_objs->{$key}->column_title;
		}

		return NULL;
	}

	/**
	 * @brief
	 *
	 */
	function getExtraVarValue($key)
	{
		$value = NULL;
		if(isset($this->extra_var_objs->{$key}->value))
		{
			$value = $this->extra_var_objs->{$key}->value;
		}
		if(is_array($value))
		{
			$value = implode(',', $value);
		}
		return $value;
	}

	/**
	 * @brief
	 *
	 */
	function thumbnailExists($width = 80, $height = 0, $type = 'crop')
	{
		if(!$this->getThumbnail($width, $height, $type))
		{
			return false;
		}
		return true;
	}

	/**
	 * @brief get thumbnail
	 *
	 */
	function getThumbnail($width = 80, $height = 0, $thumbnail_type = 'crop')
	{
		$oFileModel = getModel('file');

		$file_srl = $this->getFileSrl();
		if(!$file_srl)
		{
			return NULL;
		}
		if(!$height)
		{
			$height = $width;
		}

		// Define thumbnail information
		$thumbnail_path = sprintf('files/cache/thumbnails/%s', getNumberingPath($file_srl, 3));
		$thumbnail_file = sprintf('%s%dx%d.%s.jpg', $thumbnail_path, $width, $height, $thumbnail_type);
		$thumbnail_url = Context::getRequestUri() . $thumbnail_file;
		// Return false if thumbnail file exists and its size is 0. Otherwise, return its path
		if(file_exists($thumbnail_file))
		{
			if(filesize($thumbnail_file) < 1)
			{
				return NULL;
			}
			else
			{
				return $thumbnail_url;
			}
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
		if($output)
		{
			return $thumbnail_url;
		}
		// Create an empty file not to re-generate the thumbnail
		else
		{
			FileHandler::writeFile($thumbnail_file, '', 'w');
		}

		return NULL;
	}

	/**
	 * @brief get document
	 *
	 */
	function getDocument()
	{
		$oDocumentModel = getModel('document');
		return $oDocumentModel->getDocument($this->document_srl);
	}
}
/* End of file nproduct.item.php */
/* Location: ./modules/nproduct/nproduct.item.php */
