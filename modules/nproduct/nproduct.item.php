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
	var $uploadedFiles = array();
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

		if($price == 0)
		{
			return $oCurrencyModel->printPrice(0);
		}

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
	 * get cover image in nproduct list
	 * @param int $width
	 * @param int $height
	 * @param string $thumbnail_type
	 * @return string|void
	 */
	function getCoverImage($width = 80, $height = 0, $thumbnail_type = 'crop')
	{
		// Return false if the document doesn't exist
		if(!$this->document_srl) return;

		$config = $GLOBALS['__document_config__'];
		if(!$config)
		{
			$config = $GLOBALS['__document_config__'] = getModel('document')->getDocumentConfig();
		}
		if($config->thumbnail_type === 'none')
		{
			return;
		}
		if(!in_array($thumbnail_type, array('crop', 'ratio', 'none')))
		{
			$thumbnail_type = $config->thumbnail_type ?: 'crop';
		}

		// If not specify its height, create a square
		if(!$height) $height = $width;
		if($this->get('content'))
		{
			$content = $this->get('content');
		}
		else
		{
			$args = new stdClass();
			$args->document_srl = $this->document_srl;
			$output = executeQuery('document.getDocument', $args);
			$content = $output->data->content;
		}

		$oFileModel = getModel('file');
		$fileCount = $oFileModel->getFilesCount($this->document_srl);
		// Return false if neither attachement nor image files in the document
		if(!$fileCount && !preg_match("!<img!is", $content)) return;

		// Define thumbnail information
		$thumbnail_path = sprintf('files/thumbnails/%s',getNumberingPath($this->document_srl, 3));
		$thumbnail_file = sprintf('%s%dx%d.%s.jpg', $thumbnail_path, $width, $height, $thumbnail_type);
		$thumbnail_lockfile = sprintf('%s%dx%d.%s.lock', $thumbnail_path, $width, $height, $thumbnail_type);
		$thumbnail_url  = Context::getRequestUri().$thumbnail_file;

		// Return false if thumbnail file exists and its size is 0. Otherwise, return its path
		if(file_exists($thumbnail_file) || file_exists($thumbnail_lockfile))
		{
			if(filesize($thumbnail_file) < 1)
			{
				return FALSE;
			}
			else
			{
				return $thumbnail_url . '?' . date('YmdHis', filemtime($thumbnail_file));
			}
		}
		// Create lockfile to prevent race condition
		FileHandler::writeFile($thumbnail_lockfile, '', 'w');

		// Target File
		$source_file = null;
		$is_tmp_file = false;

		// Find an image file among attached files if exists
		if($this->hasUploadedFiles())
		{
			$file_list = $this->getUploadedFiles();
			$first_image = null;
			foreach($file_list as $file)
			{
				if($file->direct_download !== 'Y') continue;

				if($file->cover_image === 'Y' && file_exists($file->uploaded_filename))
				{
					$source_file = $file->uploaded_filename;
					break;
				}

				if($first_image) continue;

				if(preg_match("/\.(jpe?g|png|gif|bmp)$/i", $file->source_filename))
				{
					if(file_exists($file->uploaded_filename))
					{
						$first_image = $file->uploaded_filename;
					}
				}
			}

			if(!$source_file && $first_image)
			{
				$source_file = $first_image;
			}
		}

		// If not exists, file an image file from the content
		if(!$source_file)
		{
			preg_match_all("!<img\s[^>]*?src=(\"|')([^\"' ]*?)(\"|')!is", $content, $matches, PREG_SET_ORDER);
			foreach($matches as $match)
			{
				$target_src = htmlspecialchars_decode(trim($match[2]));
				if(preg_match('/\/(common|modules|widgets|addons|layouts)\//i', $target_src))
				{
					continue;
				}
				else
				{
					if(!preg_match('/^https?:\/\//i',$target_src))
					{
						$target_src = Context::getRequestUri().$target_src;
					}

					$tmp_file = sprintf('./files/cache/tmp/%d', md5(rand(111111,999999).$this->document_srl));
					if(!is_dir('./files/cache/tmp'))
					{
						FileHandler::makeDir('./files/cache/tmp');
					}
					FileHandler::getRemoteFile($target_src, $tmp_file);
					if(!file_exists($tmp_file))
					{
						continue;
					}
					else
					{
						if($is_img = @getimagesize($tmp_file))
						{
							list($_w, $_h, $_t, $_a) = $is_img;
							if($_w < ($width * 0.3) && $_h < ($height * 0.3))
							{
								continue;
							}
						}
						else
						{
							continue;
						}
						$source_file = $tmp_file;
						$is_tmp_file = true;
						break;
					}
				}
			}
		}

		if($source_file)
		{
			$output_file = FileHandler::createImageFile($source_file, $thumbnail_file, $width, $height, 'jpg', $thumbnail_type);
		}

		// Remove source file if it was temporary
		if($is_tmp_file)
		{
			FileHandler::removeFile($source_file);
		}

		// Remove lockfile
		FileHandler::removeFile($thumbnail_lockfile);

		// Return the thumbnail path if it was successfully generated
		if($output_file)
		{
			return $thumbnail_url . '?' . date('YmdHis');
		}
		// Create an empty file if thumbnail generation failed
		else
		{
			FileHandler::writeFile($thumbnail_file, '','w');
		}

		return;
	}

	function hasUploadedFiles()
	{
		if(!$this->document_srl) return;

		$oFileModel = getModel('file');
		$fileCount = $oFileModel->getFilesCount($this->document_srl);

		return $fileCount ? true : false;
	}

	function getUploadedFiles($sortIndex = 'file_srl')
	{
		if(!$this->document_srl) return;

		if(!$this->uploadedFiles[$sortIndex])
		{
			$oFileModel = getModel('file');
			$this->uploadedFiles[$sortIndex] = $oFileModel->getFiles($this->document_srl, array(), $sortIndex, true);
		}

		return $this->uploadedFiles[$sortIndex];
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
