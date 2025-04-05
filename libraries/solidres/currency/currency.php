<?php
/**
 ------------------------------------------------------------------------
 SOLIDRES - Accommodation booking extension for Joomla
 ------------------------------------------------------------------------
 * @author    Solidres Team <contact@solidres.com>
 * @website   https://www.solidres.com
 * @copyright Copyright (C) 2013 Solidres. All Rights Reserved.
 * @license   GNU General Public License version 3, or later
 ------------------------------------------------------------------------
 */

defined('_JEXEC') or die;

/**
 * Currency handler class
 *
 * @since 0.3.0
 */
class SRCurrency
{
	protected $id = 0;

	protected $code;

	protected $sign;

	protected $name;

	protected $rate;

	protected $activeId;

	protected $value = 0;

	protected $formatOptions = array();

	protected $fromExchangeRate;

	protected $toExchangeRate;

	protected static $loaded = array();

	protected $formatSets = array();

	/**
	 * Currency Constructor
	 *
	 * @param     $value
	 * @param     $id
	 * @param int $scopeId 0 is Global
	 */
	public function __construct($value = 0, $id = 0, $scopeId = 0, $options = [])
	{
		if ($value > 0)
		{
			$this->value = $value;
		}

		if ($id > 0)
		{
			$this->id = $id;
		}

		// Query for global currency display format
		if ($scopeId == 0)
		{
			$params                                         = \JComponentHelper::getParams('com_solidres');
			$this->formatOptions['currency_format_pattern'] = $params->get('currency_format_pattern', 1);
			$this->formatOptions['number_decimal_points']   = $options['number_decimal_points'] ?? $params->get('number_decimal_points', 2);
			$this->formatOptions['currency_code_symbol']    = $params->get('currency_code_symbol', 'code');
		}
		else // Query for reservation asset currency display format
		{

		}

		$this->activeId = \JFactory::getApplication()->getUserState('current_currency_id', 0);

		$this->getCurrencyDetails();

		$this->fromExchangeRate = $this->toExchangeRate = $this->rate;

		// Exchange the value
		if ($this->activeId > 0 && $this->activeId != $this->id)
		{
			$this->fromExchangeRate = $this->rate;
			$this->id               = $this->activeId;
			$this->getCurrencyDetails();
			$this->toExchangeRate = $this->rate;
			if ($this->value > 0)
			{
				$this->value = $this->value * ($this->toExchangeRate / $this->fromExchangeRate);
				$this->value = round($this->value, $this->formatOptions['number_decimal_points']);
			}
		}

		$this->formatSets = \SRUtilities::getCurrencyFormatSets();
	}

	/**
	 * Format the given number according to Solidres Options
	 *
	 * @param $usePrefix boolean If true, insert prefix into the result
	 *
	 * @return string
	 */
	public function format($usePrefix = true)
	{
		$prefix  = $usePrefix ? $this->{$this->formatOptions['currency_code_symbol']} : '';
		$pattern = $this->formatOptions['currency_format_pattern'];
		switch ($pattern)
		{
			case 1: // X0,000.00
			default:
				$formatted = $prefix . $this->getValue(true);
				break;
			case 2: // 0 000,00X
				$formatted = $this->getValue(true) . $prefix;
				break;
			case 3: // X0.000,00
				$formatted = $prefix . $this->getValue(true);
				break;
			case 4: // 0,000.00X
				$formatted = $this->getValue(true) . $prefix;
				break;
			case 5: // 0 000.00X
				$formatted = $this->getValue(true) . $prefix;
				break;
			case 6: // X 0,000.00
				$formatted = $prefix . ' ' . $this->getValue(true);
				break;
			case 7: // 0 000,00 X
				$formatted = $this->getValue(true) . ' ' . $prefix;
				break;
			case 8: // X 0.000,00
				$formatted = $prefix . ' ' . $this->getValue(true);
				break;
			case 9: // 0,000.00 X
				$formatted = $this->getValue(true) . ' ' . $prefix;
				break;
			case 10: // 0 000.00 X
				$formatted = $this->getValue(true) . ' ' . $prefix;
				break;
		}

		return $formatted;
	}

	public function setValue($value, $exchange = true)
	{
		$this->value = $value;

		if ($exchange)
		{
			$this->value = $this->value * ($this->toExchangeRate / $this->fromExchangeRate);
			$this->value = round($this->value, $this->formatOptions['number_decimal_points']);
		}
	}

	/**
	 * @param bool $formatted Return formatted value or not
	 *
	 * @return float|int
	 *
	 * @since version
	 */
	public function getValue($formatted = false, $calc = false)
	{
		if ($formatted)
		{
			$pattern = $this->formatOptions['currency_format_pattern'];

			if ($calc)
			{
				return number_format($this->value, $this->formatSets[$pattern]['decimals'], '.', '');
			}
			else
			{
				return number_format($this->value, $this->formatSets[$pattern]['decimals'], $this->formatSets[$pattern]['dec_points'], $this->formatSets[$pattern]['thousands_sep']);
			}

		}
		else
		{
			return $this->value;
		}
	}

	public function setId($id)
	{
		$this->id = $id;
	}

	public function getId()
	{
		return $this->id;
	}

	public function setCode($code)
	{
		$this->code = $code;
	}

	public function getCode()
	{
		return $this->code;
	}

	public function setActiveId($activeId)
	{
		$this->activeId = $activeId;
	}

	public function getActiveId()
	{
		return $this->activeId;
	}

	public function setRate($rate)
	{
		$this->rate = $rate;
	}

	public function getRate()
	{
		return $this->rate;
	}

	public function setSign($sign)
	{
		$this->sign = $sign;
	}

	public function getSign()
	{
		return $this->sign;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setFormatOptions($formatOptions)
	{
		$this->formatOptions = $formatOptions;
	}

	public function getFormatOptions()
	{
		return $this->formatOptions;
	}

	/**
	 * Query for currency details
	 *
	 * @return void
	 */
	public function getCurrencyDetails()
	{
		if (!isset(self::$loaded[$this->id]))
		{
			$db    = \JFactory::getDbo();
			$query = $db->getQuery(true);

			$query->select('*')
				->from($db->quoteName('#__sr_currencies'))
				->where($db->quoteName('id') . ' = ' . (int) $this->id);

			$details = $db->setQuery($query)->loadObject();

			self::$loaded[$details->id] = $details;
		}
		else
		{
			$details = self::$loaded[$this->id];
		}

		$this->id   = $details->id;
		$this->code = $details->currency_code;
		$this->sign = $details->sign;
		$this->name = $details->currency_name;
		$this->rate = $details->exchange_rate;
	}
}