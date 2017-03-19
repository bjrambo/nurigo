<?php

/**
 * @class  currencyModel
 */
class currencyModel extends currency
{
	var $decimals = 0;
	var $currency = "KRW";
	var $as_sign = "N";
	var $price = 0;

	/**
	 * @brief Initialization
	 */
	function currencyModel()
	{
		$config = $this->getModuleConfig();
		if($config->decimals)
		{
			$this->decimals = $config->decimals;
		}
		if($config->currency)
		{
			$this->currency = $config->currency;
		}
		if($config->as_sign)
		{
			$this->as_sign = $config->as_sign;
		}
	}

	/**
	 * @brief get currency config
	 */
	function getModuleConfig()
	{
		$oModuleModel = getModel('module');
		$output = $oModuleModel->getModuleConfig('currency');
		return $output;
	}

	function setCurrency($currency, $as_sign)
	{
		$this->currency = $currency;
		$this->as_sign = $as_sign;
	}

	function price($price)
	{
		$config = $this->getModuleConfig();
		$division = pow(10, $config->decimals);

		if(!$division)
		{
			return $price;
		}
		return $price / $division;
	}

	function getPriceByJquery()
	{
		$config = $this->getModuleConfig();
		$division = pow(10, $config->decimals);
		$price = Context::get('price');

		if(!$division)
		{
			return $this->add('price', 0);
		}
		$printPrice = $this->printPrice($price);
		$this->add('price', $printPrice);
		$this->add('division', $division);
	}

	function formatMoney($number)
	{
		$division = pow(10, $this->decimals);
		$number = $number / $division;
		return number_format($number, $this->decimals);
	}

	function getPrice($price)
	{
		$division = pow(10, $this->decimals);
		return (int)$price / $division;
	}

	function printPrice($price)
	{
		global $lang;
		$price = $this->getPrice($price);

		if($this->as_sign == "Y")
		{
			$currency = $lang->currency_sign[$this->currency];
			return sprintf("<span class=\"currency_symbol\">%s</span>%s", $currency, number_format($price, $this->decimals));
		}
		else
		{
			$currency = $lang->currency_code[$this->currency];
			return sprintf("%s<span class=\"currency_code\">%s</span>", number_format($price, $this->decimals), $currency);
		}
	}

	/*
	 * @brief display discounted price
	 */
	function printDiscountedPrice($price)
	{
		if($price !== NULL)
		{
			return $this->printPrice($price);
		}
		return $this->printPrice($this->discounted_price);
	}
}
/* End of file currency.model.php */
/* Location: ./modules/currency/currency.model.php */
